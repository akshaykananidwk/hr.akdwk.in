@extends('layouts.app')
@section('title','Daily Reports')
@section('page-title','Daily Reports')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5 class="fw-bold mb-0">Daily Reports</h5>
    <a href="{{ route('reports.create') }}" class="btn btn-ak btn-sm"><i class="bi bi-plus-lg me-1"></i>Submit Report</a>
</div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr>@if($isManager)<th>Employee</th>@endif<th>Date</th><th>Visits</th><th>Calls</th><th>Demos</th><th>Closings</th><th>Collection</th><th></th></tr></thead>
    <tbody>
    @forelse($reports as $r)
        <tr>
            @if($isManager)<td class="small fw-semibold">{{ $r->user->name }}</td>@endif
            <td class="small">{{ $r->date->format('d M Y') }}</td>
            <td class="small">{{ $r->total_visits }} <small class="text-muted">({{ $r->visits_count }})</small></td>
            <td class="small">{{ $r->total_calls }}</td>
            <td class="small">{{ $r->total_demos }}</td>
            <td class="small">{{ $r->total_closings }}</td>
            <td class="small">{{ money($r->total_collection) }}</td>
            <td><a href="{{ route('reports.show',$r) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted small">No reports yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $reports->links() }}
</div></div>
@endsection
