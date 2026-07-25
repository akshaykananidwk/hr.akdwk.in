<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = Leave::where('user_id', auth()->id())->latest()->paginate(12);
        $balance = [
            'casual' => 12 - Leave::where('user_id', auth()->id())->where('type', 'casual')->where('status', 'approved')->whereYear('from_date', now()->year)->count(),
            'sick' => 8 - Leave::where('user_id', auth()->id())->where('type', 'sick')->where('status', 'approved')->whereYear('from_date', now()->year)->count(),
        ];

        return view('leaves.index', compact('leaves', 'balance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:casual,sick,medical,emergency,earned,unpaid'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'half_day' => ['nullable', 'boolean'],
            'reason' => ['required', 'string'],
        ]);
        $data['user_id'] = auth()->id();
        $leave = Leave::create($data);
        ActivityLogger::log('leave.apply', $leave, 'Applied for leave');

        return back()->with('success', 'Leave request submitted.');
    }

    public function approvals()
    {
        abort_unless(auth()->user()->can('approve leave'), 403);
        $leaves = Leave::with('user')->where('status', 'pending')->latest()->paginate(15);

        return view('leaves.approvals', compact('leaves'));
    }

    public function action(Request $request, Leave $leave)
    {
        abort_unless(auth()->user()->can('approve leave'), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_remarks' => ['nullable', 'string'],
        ]);
        $leave->update([
            'status' => $data['decision'],
            'approved_by' => auth()->id(),
            'actioned_at' => now(),
            'admin_remarks' => $data['admin_remarks'] ?? null,
        ]);
        ActivityLogger::log('leave.'.$data['decision'], $leave, 'Leave '.$data['decision']);

        return back()->with('success', 'Leave '.$data['decision'].'.');
    }
}
