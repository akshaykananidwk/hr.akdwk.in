@extends('layouts.app')
@section('title','Products')
@section('page-title','Products')
@section('content')
<div class="row g-3">
@forelse($products as $p)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <h6 class="fw-bold mb-1">{{ $p->name }}</h6>
                @if(!$p->is_active)<span class="badge bg-secondary">Inactive</span>@endif
            </div>
            <p class="text-muted small">{{ $p->tagline }}</p>
            <div class="mb-2">
                @foreach(($p->features ?? []) as $f)<span class="badge bg-light text-dark border me-1 mb-1">{{ $f }}</span>@endforeach
            </div>
            <div class="small text-muted mb-2">Commission: {{ $p->commission_type==='percentage' ? $p->commission_value.'%' : money($p->commission_value) }}</div>
            <a href="{{ route('products.show',$p) }}" class="btn btn-outline-primary btn-sm w-100">View Plans</a>
        </div></div>
    </div>
@empty
    <div class="col-12"><div class="alert alert-light">No products configured.</div></div>
@endforelse
</div>
@endsection
