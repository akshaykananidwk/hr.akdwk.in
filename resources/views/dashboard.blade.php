@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $statusColors = ['new'=>'secondary','contacted'=>'info','demo'=>'primary','negotiation'=>'warning','won'=>'success','lost'=>'danger','follow_up'=>'dark'];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0">{{ __('Welcome back') }}, {{ auth()->user()->name }} 👋</h5>
        <small class="text-muted">{{ now()->format('l, d M Y') }} · {{ auth()->user()->getRoleNames()->implode(', ') }}</small>
    </div>
    <div class="d-flex gap-2">
        @unless($attendanceToday && $attendanceToday->check_in_at)
            <form method="POST" action="{{ route('attendance.checkin') }}" id="checkinForm">@csrf
                <input type="hidden" name="latitude" id="ci_lat"><input type="hidden" name="longitude" id="ci_lng">
                <button class="btn btn-ak btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Check In</button>
            </form>
        @elseif(!$attendanceToday->check_out_at)
            <form method="POST" action="{{ route('attendance.checkout') }}">@csrf
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Check Out</button>
            </form>
        @else
            <span class="badge bg-success align-self-center"><i class="bi bi-check-circle me-1"></i>Attendance done</span>
        @endunless
        <a href="{{ route('reports.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Daily Report</a>
    </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-3">
    @php
        $cards = [
            ['label'=>"Today's Sales",'value'=>money($stats['today_sales']),'icon'=>'bi-currency-rupee','bg'=>'#e0e7ff','fg'=>'#4f46e5'],
            ['label'=>'Month Sales','value'=>money($stats['month_sales']),'icon'=>'bi-graph-up-arrow','bg'=>'#dcfce7','fg'=>'#16a34a'],
            ['label'=>"Today's Visits",'value'=>$stats['today_visits'],'icon'=>'bi-geo-alt','bg'=>'#fef9c3','fg'=>'#ca8a04'],
            ['label'=>'Pending Follow-ups','value'=>$stats['pending_followups'],'icon'=>'bi-alarm','bg'=>'#fee2e2','fg'=>'#dc2626'],
            ['label'=>'Open Tasks','value'=>$stats['open_tasks'],'icon'=>'bi-check2-square','bg'=>'#f3e8ff','fg'=>'#9333ea'],
            ['label'=>'Commission Wallet','value'=>money($stats['commission_wallet']),'icon'=>'bi-wallet2','bg'=>'#cffafe','fg'=>'#0891b2'],
        ];
    @endphp
    @foreach($cards as $c)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card stat-card h-100"><div class="card-body p-3">
            <div class="stat-icon mb-2" style="background:{{ $c['bg'] }};color:{{ $c['fg'] }}"><i class="bi {{ $c['icon'] }}"></i></div>
            <div class="text-muted small">{{ $c['label'] }}</div>
            <div class="fw-bold fs-5">{{ $c['value'] }}</div>
        </div></div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <!-- Monthly target -->
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-bullseye me-1 text-danger"></i>Monthly Target</h6>
            @if($target)
                @php($pct = $target->progress)
                <div class="text-center mb-2">
                    <div class="fs-3 fw-bold">{{ $pct }}%</div>
                    <small class="text-muted">{{ money($target->achieved_value) }} of {{ money($target->target_value) }}</small>
                </div>
                <div class="progress" style="height:10px"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>
            @else
                <p class="text-muted small mb-0">No target assigned for this month.</p>
            @endif
        </div></div>
    </div>

    <!-- Lead pipeline -->
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-funnel me-1 text-primary"></i>Lead Pipeline</h6>
            @forelse(['new','contacted','demo','negotiation','won','lost'] as $s)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-{{ $statusColors[$s] ?? 'secondary' }} text-capitalize">{{ str_replace('_',' ',$s) }}</span>
                    <span class="fw-semibold">{{ $pipeline[$s] ?? 0 }}</span>
                </div>
            @empty
            @endforelse
        </div></div>
    </div>

    <!-- Leaderboard -->
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-trophy me-1 text-warning"></i>Sales Leaderboard</h6>
            @forelse($leaderboard as $i => $emp)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="fw-bold text-muted">#{{ $i+1 }}</span>
                    <img src="{{ $emp->avatar_url }}" class="avatar-sm" width="28" height="28">
                    <span class="small flex-grow-1">{{ $emp->name }}</span>
                    <span class="fw-semibold small">{{ money($emp->month_sales) }}</span>
                </div>
            @empty
                <p class="text-muted small mb-0">No sales yet this month.</p>
            @endforelse
        </div></div>
    </div>

    <!-- Follow-ups -->
    <div class="col-lg-7">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between mb-3"><h6 class="fw-semibold mb-0"><i class="bi bi-alarm me-1 text-danger"></i>Upcoming Follow-ups</h6><a href="{{ route('leads.index') }}" class="small">View all</a></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <tbody>
                @forelse($followups as $lead)
                    <tr>
                        <td><a href="{{ route('leads.show',$lead) }}" class="text-decoration-none fw-semibold">{{ $lead->business_name ?: $lead->name }}</a><br><small class="text-muted">{{ $lead->product?->name }}</small></td>
                        <td><span class="badge bg-{{ $statusColors[$lead->status] ?? 'secondary' }} text-capitalize">{{ str_replace('_',' ',$lead->status) }}</span></td>
                        <td class="text-end small text-muted">{{ $lead->next_followup_at?->format('d M, h:i A') }}</td>
                    </tr>
                @empty
                    <tr><td class="text-muted small">No pending follow-ups. 🎉</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div></div>
    </div>

    <!-- My tasks -->
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between mb-3"><h6 class="fw-semibold mb-0"><i class="bi bi-check2-square me-1 text-purple"></i>My Tasks</h6><a href="{{ route('tasks.index') }}" class="small">View all</a></div>
            @forelse($myTasks as $task)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-circle text-muted"></i>
                    <span class="small flex-grow-1">{{ $task->title }}</span>
                    <span class="badge bg-{{ $task->priority==='urgent'?'danger':($task->priority==='high'?'warning':'secondary') }} text-capitalize">{{ $task->priority }}</span>
                </div>
            @empty
                <p class="text-muted small mb-0">No open tasks. 🎉</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Capture GPS for check-in if available.
    const ciForm = document.getElementById('checkinForm');
    if (ciForm && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(p => {
            document.getElementById('ci_lat').value = p.coords.latitude;
            document.getElementById('ci_lng').value = p.coords.longitude;
        });
    }
</script>
@endpush
