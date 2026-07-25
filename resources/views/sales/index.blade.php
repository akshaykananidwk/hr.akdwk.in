@extends('layouts.app')
@section('title','Sales')
@section('page-title','Sales')
@section('content')
<div class="card mb-3 stat-card"><div class="card-body d-flex justify-content-between align-items-center">
    <div><div class="text-muted small">Total Revenue</div><div class="fs-4 fw-bold">{{ money($totalRevenue) }}</div></div>
    <i class="bi bi-receipt-cutoff fs-1 text-primary opacity-25"></i>
</div></div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Invoice</th><th>Customer</th><th>Product</th><th>Executive</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
    <tbody>
    @forelse($sales as $s)
        <tr>
            <td><code class="small">{{ $s->invoice_number }}</code></td>
            <td class="small fw-semibold">{{ $s->customer_name }}</td>
            <td class="small">{{ $s->product?->name }}</td>
            <td class="small">{{ $s->user?->name }}</td>
            <td class="small fw-semibold">{{ money($s->amount) }}</td>
            <td><span class="badge bg-{{ $s->payment_status==='paid'?'success':($s->payment_status==='partial'?'warning':'secondary') }} text-capitalize">{{ $s->payment_status }}</span></td>
            <td class="small text-muted">{{ $s->created_at->format('d M Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted small">No sales recorded.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $sales->links() }}
</div></div>
@endsection
