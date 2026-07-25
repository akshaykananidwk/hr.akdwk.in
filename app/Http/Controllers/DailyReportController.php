<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader']);

        $query = DailyReport::with('user')->withCount('visits')->latest('date');
        if (! $isManager) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('reports.index', compact('reports', 'isManager'));
    }

    public function create()
    {
        $today = DailyReport::where('user_id', auth()->id())->whereDate('date', today())->first();

        return view('reports.create', compact('today'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'work_summary' => ['nullable', 'string'],
            'total_visits' => ['nullable', 'integer', 'min:0'],
            'total_calls' => ['nullable', 'integer', 'min:0'],
            'total_followups' => ['nullable', 'integer', 'min:0'],
            'total_demos' => ['nullable', 'integer', 'min:0'],
            'total_closings' => ['nullable', 'integer', 'min:0'],
            'total_collection' => ['nullable', 'numeric', 'min:0'],
            'expenses' => ['nullable', 'numeric', 'min:0'],
            'petrol_expense' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            // Visits
            'visits' => ['nullable', 'array'],
            'visits.*.business_name' => ['nullable', 'string'],
            'visits.*.owner_name' => ['nullable', 'string'],
            'visits.*.phone' => ['nullable', 'string'],
            'visits.*.outcome' => ['nullable', 'in:interested,not_interested,follow_up,closed'],
            'visits.*.reason' => ['nullable', 'string'],
            'visits.*.remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $report = DailyReport::updateOrCreate(
                ['user_id' => auth()->id(), 'date' => $data['date']],
                collect($data)->except('visits')->merge(['status' => 'submitted'])->toArray()
            );

            $report->visits()->delete();
            foreach ($request->input('visits', []) as $visit) {
                if (empty($visit['business_name'])) {
                    continue;
                }
                $report->visits()->create($visit);
            }

            ActivityLogger::log('report.submit', $report, 'Submitted daily report');
        });

        return redirect()->route('reports.index')->with('success', 'Daily report submitted successfully.');
    }

    public function show(DailyReport $report)
    {
        abort_unless(
            $report->user_id === auth()->id() || auth()->user()->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader']),
            403
        );
        $report->load('visits', 'user');

        return view('reports.show', compact('report'));
    }
}
