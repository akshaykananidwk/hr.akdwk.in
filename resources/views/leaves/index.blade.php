@extends('layouts.app')
@section('title','Leave')
@section('page-title','Leave Management')
@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Casual Balance</div><div class="fs-4 fw-bold">{{ $balance['casual'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Sick Balance</div><div class="fs-4 fw-bold">{{ $balance['sick'] }}</div></div></div></div>
    <div class="col-md-6 d-flex align-items-center justify-content-end"><button class="btn btn-ak btn-sm" data-bs-toggle="modal" data-bs-target="#leaveModal"><i class="bi bi-plus-lg me-1"></i>Apply Leave</button></div>
</div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($leaves as $l)
        <tr>
            <td class="text-capitalize small fw-semibold">{{ $l->type }}</td>
            <td class="small">{{ $l->from_date->format('d M') }}</td>
            <td class="small">{{ $l->to_date->format('d M') }}</td>
            <td class="small">{{ $l->days }}</td>
            <td class="small text-muted">{{ Str::limit($l->reason,40) }}</td>
            <td><span class="badge bg-{{ $l->status==='approved'?'success':($l->status==='rejected'?'danger':'warning') }} text-capitalize">{{ $l->status }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No leave requests.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $leaves->links() }}
</div></div>

<div class="modal fade" id="leaveModal"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('leaves.store') }}">@csrf
        <div class="modal-header"><h6 class="modal-title">Apply for Leave</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label small">Type</label><select name="type" class="form-select">@foreach(['casual','sick','medical','emergency','earned','unpaid'] as $t)<option value="{{ $t }}" class="text-capitalize">{{ $t }}</option>@endforeach</select></div>
            <div class="row g-2"><div class="col"><label class="form-label small">From</label><input type="date" name="from_date" class="form-control" required></div><div class="col"><label class="form-label small">To</label><input type="date" name="to_date" class="form-control" required></div></div>
            <div class="form-check mt-2"><input type="checkbox" name="half_day" value="1" class="form-check-input" id="hd"><label for="hd" class="form-check-label small">Half day</label></div>
            <div class="mt-2"><label class="form-label small">Reason</label><textarea name="reason" class="form-control" rows="2" required></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-ak btn-sm">Submit</button></div>
    </form>
</div></div></div>
@endsection
