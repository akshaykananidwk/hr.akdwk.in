@extends('layouts.app')
@section('title','Commission')
@section('page-title','Commission')
@section('content')
<div class="card mb-3 stat-card"><div class="card-body d-flex justify-content-between align-items-center">
    <div><div class="text-muted small">My Commission Wallet (approved)</div><div class="fs-4 fw-bold">{{ money($wallet) }}</div></div>
    <i class="bi bi-wallet2 fs-1 text-info opacity-25"></i>
</div></div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr>@if($isManager)<th>Employee</th>@endif<th>Type</th><th>Sale</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    @forelse($commissions as $c)
        <tr>
            @if($isManager)<td class="small fw-semibold">{{ $c->user?->name }}</td>@endif
            <td class="text-capitalize small">{{ str_replace('_',' ',$c->type) }}</td>
            <td class="small">{{ $c->sale?->invoice_number ?? '—' }}</td>
            <td class="small fw-semibold">{{ money($c->amount) }}</td>
            <td><span class="badge bg-{{ $c->status==='paid'?'success':($c->status==='approved'?'info':'secondary') }} text-capitalize">{{ $c->status }}</span></td>
            <td class="small text-muted">{{ $c->created_at->format('d M Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No commissions yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $commissions->links() }}
</div></div>
@endsection
