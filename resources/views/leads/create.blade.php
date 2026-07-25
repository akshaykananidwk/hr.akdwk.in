@extends('layouts.app')
@section('title','New Lead')
@section('page-title','New Lead')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('leads.store') }}">@csrf
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Contact Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Business Name</label><input name="business_name" value="{{ old('business_name') }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Source</label><select name="source" class="form-select">@foreach(['manual','website','whatsapp','facebook','instagram','reference','cold_visit'] as $s)<option value="{{ $s }}" class="text-capitalize">{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Product</label><select name="product_id" class="form-select"><option value="">—</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select">@foreach(\App\Models\Lead::STATUSES as $s)<option value="{{ $s }}" class="text-capitalize">{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Assign To</label><select name="assigned_to" class="form-select"><option value="">Me</option>@foreach($executives as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Expected Value</label><input type="number" step="0.01" name="expected_value" value="{{ old('expected_value') }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Next Follow-up</label><input type="datetime-local" name="next_followup_at" class="form-control"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-ak"><i class="bi bi-check-lg me-1"></i>Create Lead</button><a href="{{ route('leads.index') }}" class="btn btn-link">Cancel</a></div>
</form>
</div></div>
@endsection
