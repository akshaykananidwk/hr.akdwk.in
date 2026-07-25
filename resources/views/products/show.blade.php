@extends('layouts.app')
@section('title',$product->name)
@section('page-title',$product->name)
@section('content')
<div class="card mb-3"><div class="card-body">
    <h4 class="fw-bold">{{ $product->name }}</h4>
    <p class="text-muted">{{ $product->tagline }}</p>
    <p>{{ $product->description }}</p>
    <div class="d-flex flex-wrap gap-1">@foreach(($product->features ?? []) as $f)<span class="badge bg-primary-subtle text-primary">{{ $f }}</span>@endforeach</div>
</div></div>
<div class="row g-3">
@foreach(($product->plans ?? []) as $plan)
    <div class="col-md-4">
        <div class="card h-100 text-center"><div class="card-body">
            <h6 class="fw-bold">{{ $plan['name'] ?? 'Plan' }}</h6>
            <div class="fs-3 fw-bold text-primary">{{ money($plan['price'] ?? 0) }}</div>
            <small class="text-muted">/ {{ $plan['period'] ?? 'month' }}</small>
        </div></div>
    </div>
@endforeach
</div>
@endsection
