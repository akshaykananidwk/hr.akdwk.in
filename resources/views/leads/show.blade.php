@extends('layouts.app')
@section('title',$lead->name)
@section('page-title','Lead Detail')
@php($sc=['new'=>'secondary','contacted'=>'info','demo'=>'primary','negotiation'=>'warning','won'=>'success','lost'=>'danger','follow_up'=>'dark'])
@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between">
                <h5 class="fw-bold mb-0">{{ $lead->business_name ?: $lead->name }}</h5>
                <span class="badge bg-{{ $sc[$lead->status] ?? 'secondary' }} text-capitalize">{{ str_replace('_',' ',$lead->status) }}</span>
            </div>
            <p class="text-muted small mb-3">{{ $lead->name }}</p>
            <ul class="list-unstyled small">
                <li class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i>{{ $lead->phone ?: '—' }}</li>
                <li class="mb-1"><i class="bi bi-envelope me-2 text-muted"></i>{{ $lead->email ?: '—' }}</li>
                <li class="mb-1"><i class="bi bi-box-seam me-2 text-muted"></i>{{ $lead->product?->name ?: '—' }}</li>
                <li class="mb-1"><i class="bi bi-person me-2 text-muted"></i>{{ $lead->assignee?->name ?: '—' }}</li>
                <li class="mb-1"><i class="bi bi-currency-rupee me-2 text-muted"></i>{{ money($lead->expected_value) }}</li>
                <li class="mb-1"><i class="bi bi-alarm me-2 text-muted"></i>{{ $lead->next_followup_at?->format('d M Y, h:i A') ?: '—' }}</li>
            </ul>
            @if($lead->notes)<div class="alert alert-light small">{{ $lead->notes }}</div>@endif
        </div></div>

        <div class="card mt-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Update Status</h6>
            <form method="POST" action="{{ route('leads.status',$lead) }}">@csrf
                <div class="row g-2">
                    <div class="col-6"><select name="status" class="form-select form-select-sm">@foreach($statuses as $s)<option value="{{ $s }}" {{ $lead->status===$s?'selected':'' }} class="text-capitalize">{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
                    <div class="col-6"><input type="datetime-local" name="next_followup_at" class="form-control form-control-sm"></div>
                    <div class="col-12"><input name="note" class="form-control form-control-sm" placeholder="Note (optional)"></div>
                    <div class="col-12"><button class="btn btn-ak btn-sm w-100">Save</button></div>
                </div>
            </form>
        </div></div>
    </div>

    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3">Log Activity</h6>
            <form method="POST" action="{{ route('leads.activity',$lead) }}" class="mb-3">@csrf
                <div class="input-group input-group-sm">
                    <select name="type" class="form-select" style="max-width:120px">@foreach(['call','note','meeting','demo','whatsapp'] as $t)<option value="{{ $t }}" class="text-capitalize">{{ $t }}</option>@endforeach</select>
                    <input name="note" class="form-control" placeholder="What happened?" required>
                    <button class="btn btn-ak">Add</button>
                </div>
            </form>
            <h6 class="fw-semibold mb-3">Timeline</h6>
            <ul class="list-unstyled">
            @forelse($lead->activities as $a)
                <li class="d-flex gap-2 mb-3">
                    <div><i class="bi bi-record-circle text-primary"></i></div>
                    <div class="small">
                        <div><span class="fw-semibold text-capitalize">{{ str_replace('_',' ',$a->type) }}</span>
                        @if($a->old_status) <span class="text-muted">{{ $a->old_status }} → {{ $a->new_status }}</span>@endif</div>
                        @if($a->note)<div class="text-muted">{{ $a->note }}</div>@endif
                        <small class="text-muted">{{ $a->user?->name }} · {{ $a->created_at->diffForHumans() }}</small>
                    </div>
                </li>
            @empty
                <li class="text-muted small">No activity yet.</li>
            @endforelse
            </ul>
        </div></div>
    </div>
</div>
@endsection
