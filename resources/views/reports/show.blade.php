@extends('layouts.app')
@section('title','Report')
@section('page-title','Daily Report')
@section('content')
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between">
        <h5 class="fw-bold mb-0">{{ $report->user->name }}</h5>
        <span class="text-muted">{{ $report->date->format('d M Y') }}</span>
    </div>
    <p class="text-muted small">{{ $report->start_time }} – {{ $report->end_time }}</p>
    @if($report->work_summary)<p>{{ $report->work_summary }}</p>@endif
    <div class="row g-2 text-center">
        @foreach([['Visits',$report->total_visits],['Calls',$report->total_calls],['Follow-ups',$report->total_followups],['Demos',$report->total_demos],['Closings',$report->total_closings]] as $s)
        <div class="col"><div class="border rounded p-2"><div class="fs-5 fw-bold">{{ $s[1] }}</div><small class="text-muted">{{ $s[0] }}</small></div></div>
        @endforeach
    </div>
    <div class="row g-2 mt-2 small">
        <div class="col-md-4">Collection: <strong>{{ money($report->total_collection) }}</strong></div>
        <div class="col-md-4">Expenses: <strong>{{ money($report->expenses) }}</strong></div>
        <div class="col-md-4">Petrol: <strong>{{ money($report->petrol_expense) }}</strong></div>
    </div>
</div></div>
<div class="card"><div class="card-body">
    <h6 class="fw-semibold mb-3">Businesses Visited ({{ $report->visits->count() }})</h6>
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Business</th><th>Owner</th><th>Phone</th><th>Outcome</th><th>Remarks</th></tr></thead>
        <tbody>
        @forelse($report->visits as $v)
            <tr><td class="small fw-semibold">{{ $v->business_name }}</td><td class="small">{{ $v->owner_name }}</td><td class="small">{{ $v->phone }}</td>
            <td><span class="badge bg-{{ $v->outcome==='interested'?'success':($v->outcome==='not_interested'?'danger':'secondary') }} text-capitalize">{{ str_replace('_',' ',$v->outcome) }}</span></td>
            <td class="small text-muted">{{ $v->remarks }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-muted small">No businesses recorded.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
