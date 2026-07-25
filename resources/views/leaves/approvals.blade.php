@extends('layouts.app')
@section('title','Leave Approvals')
@section('page-title','Leave Approvals')
@section('content')
<div class="card"><div class="card-body">
<h5 class="fw-bold mb-3">Pending Leave Requests</h5>
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th class="text-end">Action</th></tr></thead>
    <tbody>
    @forelse($leaves as $l)
        <tr>
            <td class="small fw-semibold">{{ $l->user->name }}</td>
            <td class="text-capitalize small">{{ $l->type }}</td>
            <td class="small">{{ $l->from_date->format('d M') }} – {{ $l->to_date->format('d M') }}</td>
            <td class="small">{{ $l->days }}</td>
            <td class="small text-muted">{{ Str::limit($l->reason,40) }}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('leaves.action',$l) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="approved"><button class="btn btn-success btn-sm"><i class="bi bi-check"></i></button></form>
                <form method="POST" action="{{ route('leaves.action',$l) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="rejected"><button class="btn btn-outline-danger btn-sm"><i class="bi bi-x"></i></button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No pending requests. 🎉</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $leaves->links() }}
</div></div>
@endsection
