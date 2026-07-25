<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\Target;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $isManager = $user->hasAnyRole(['Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader', 'Accountant']);

        // Scope: managers see everyone, executives see their own data.
        $leadScope = $isManager ? Lead::query() : Lead::where('assigned_to', $user->id);
        $saleScope = $isManager ? Sale::query() : Sale::where('user_id', $user->id);

        $stats = [
            'today_visits' => (int) (DailyReport::when(! $isManager, fn ($q) => $q->where('user_id', $user->id))
                ->whereDate('date', $today)->sum('total_visits')),
            'today_leads' => (clone $leadScope)->whereDate('created_at', $today)->count(),
            'today_sales' => (float) (clone $saleScope)->whereDate('created_at', $today)->sum('amount'),
            'month_sales' => (float) (clone $saleScope)->where('created_at', '>=', $monthStart)->sum('amount'),
            'pending_followups' => (clone $leadScope)->whereNotNull('next_followup_at')
                ->whereDate('next_followup_at', '<=', $today)
                ->whereNotIn('status', ['won', 'lost'])->count(),
            'open_tasks' => Task::where('assigned_to', $user->id)->whereIn('status', ['pending', 'in_progress'])->count(),
            'commission_wallet' => $user->commissionWallet(),
        ];

        // Monthly target for the user
        $target = Target::where('user_id', $user->id)
            ->where('period_type', 'monthly')
            ->where('period_start', '<=', $today)
            ->where('period_end', '>=', $today)
            ->first();

        // Attendance today
        $attendanceToday = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

        // Lead pipeline breakdown
        $pipeline = (clone $leadScope)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        // Leaderboard (top sales executives this month)
        $leaderboard = User::query()
            ->withSum(['sales as month_sales' => fn ($q) => $q->where('created_at', '>=', $monthStart)], 'amount')
            ->whereHas('sales', fn ($q) => $q->where('created_at', '>=', $monthStart))
            ->orderByDesc('month_sales')
            ->limit(5)->get();

        // Recent leads to follow up
        $followups = (clone $leadScope)
            ->whereNotNull('next_followup_at')
            ->whereNotIn('status', ['won', 'lost'])
            ->orderBy('next_followup_at')
            ->with('product')
            ->limit(6)->get();

        $myTasks = Task::where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date')->limit(6)->get();

        return view('dashboard', compact(
            'stats', 'target', 'attendanceToday', 'pipeline', 'leaderboard', 'followups', 'myTasks', 'isManager'
        ));
    }
}
