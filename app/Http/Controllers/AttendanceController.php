<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $user->can('view attendance') && $user->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager']);

        $query = Attendance::with('user')->latest('date');
        if (! $isManager) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->paginate(20)->withQueryString();
        $today = Attendance::where('user_id', $user->id)->whereDate('date', Carbon::today())->first();

        return view('attendance.index', compact('records', 'today', 'isManager'));
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $user = auth()->user();
        $today = Carbon::today();

        $attendance = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $today->toDateString()]);
        if ($attendance->check_in_at) {
            return back()->with('error', 'You have already checked in today.');
        }

        $lateAfter = setting('late_after_time', '09:45');
        $attendance->fill([
            'check_in_at' => now(),
            'check_in_lat' => $data['latitude'] ?? null,
            'check_in_lng' => $data['longitude'] ?? null,
            'method' => 'gps',
            'status' => 'present',
            'is_late' => now()->format('H:i') > $lateAfter,
        ])->save();

        ActivityLogger::log('attendance.check_in', $attendance, 'Checked in');

        return back()->with('success', 'Checked in successfully at '.now()->format('h:i A'));
    }

    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $user = auth()->user();
        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', Carbon::today())->first();

        if (! $attendance || ! $attendance->check_in_at) {
            return back()->with('error', 'You need to check in first.');
        }
        if ($attendance->check_out_at) {
            return back()->with('error', 'You have already checked out today.');
        }

        $hours = round($attendance->check_in_at->diffInMinutes(now()) / 60, 2);
        $fullDay = (float) setting('full_day_hours', 8);

        $attendance->fill([
            'check_out_at' => now(),
            'check_out_lat' => $data['latitude'] ?? null,
            'check_out_lng' => $data['longitude'] ?? null,
            'working_hours' => $hours,
            'overtime_hours' => max(0, round($hours - $fullDay, 2)),
            'status' => $hours < ($fullDay / 2) ? 'half_day' : 'present',
        ])->save();

        ActivityLogger::log('attendance.check_out', $attendance, 'Checked out');

        return back()->with('success', 'Checked out. Worked '.$hours.' hours today.');
    }
}
