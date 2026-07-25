@extends('layouts.app')
@section('title','Submit Report')
@section('page-title','Daily Report')
@section('content')
<form method="POST" action="{{ route('reports.store') }}" x-data="reportForm()">@csrf
<input type="hidden" name="latitude" x-model="lat"><input type="hidden" name="longitude" x-model="lng">
<div class="card mb-3"><div class="card-body">
    <h6 class="fw-semibold mb-3">Summary</h6>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label small fw-semibold">Date *</label><input type="date" name="date" value="{{ $today?->date?->toDateString() ?? now()->toDateString() }}" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Start Time</label><input type="time" name="start_time" value="{{ $today?->start_time ?? '09:30' }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">End Time</label><input type="time" name="end_time" value="{{ $today?->end_time ?? '18:30' }}" class="form-control"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Work Summary</label><textarea name="work_summary" class="form-control" rows="2">{{ $today?->work_summary }}</textarea></div>
    </div>
    <div class="row g-2 mt-1">
        @foreach([['total_visits','Visits'],['total_calls','Calls'],['total_followups','Follow-ups'],['total_demos','Demos'],['total_closings','Closings']] as $m)
        <div class="col-6 col-md"><label class="form-label small">{{ $m[1] }}</label><input type="number" min="0" name="{{ $m[0] }}" value="{{ $today?->{$m[0]} ?? 0 }}" class="form-control form-control-sm"></div>
        @endforeach
    </div>
    <div class="row g-2 mt-1">
        <div class="col-md-4"><label class="form-label small">Collection ₹</label><input type="number" step="0.01" name="total_collection" value="{{ $today?->total_collection ?? 0 }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small">Expenses ₹</label><input type="number" step="0.01" name="expenses" value="{{ $today?->expenses ?? 0 }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small">Petrol ₹</label><input type="number" step="0.01" name="petrol_expense" value="{{ $today?->petrol_expense ?? 0 }}" class="form-control form-control-sm"></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between mb-3"><h6 class="fw-semibold mb-0">Businesses Visited</h6><button type="button" class="btn btn-outline-primary btn-sm" @click="addVisit()"><i class="bi bi-plus"></i> Add</button></div>
    <template x-for="(v,i) in visits" :key="i">
        <div class="border rounded p-2 mb-2">
            <div class="row g-2">
                <div class="col-md-4"><input :name="`visits[${i}][business_name]`" class="form-control form-control-sm" placeholder="Business name"></div>
                <div class="col-md-3"><input :name="`visits[${i}][owner_name]`" class="form-control form-control-sm" placeholder="Owner"></div>
                <div class="col-md-2"><input :name="`visits[${i}][phone]`" class="form-control form-control-sm" placeholder="Phone"></div>
                <div class="col-md-2"><select :name="`visits[${i}][outcome]`" class="form-select form-select-sm"><option value="interested">Interested</option><option value="not_interested">Not Interested</option><option value="follow_up">Follow-up</option><option value="closed">Closed</option></select></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm w-100" @click="visits.splice(i,1)"><i class="bi bi-trash"></i></button></div>
                <div class="col-12"><input :name="`visits[${i}][remarks]`" class="form-control form-control-sm" placeholder="Remarks"></div>
            </div>
        </div>
    </template>
    <p class="text-muted small mb-0" x-show="visits.length===0">No businesses added yet.</p>
</div></div>

<button class="btn btn-ak"><i class="bi bi-check-lg me-1"></i>Submit Report</button>
</form>
@endsection
@push('scripts')<script>
function reportForm(){return{ visits:[{}], lat:'', lng:'',
  addVisit(){this.visits.push({})},
  init(){ navigator.geolocation && navigator.geolocation.getCurrentPosition(p=>{this.lat=p.coords.latitude;this.lng=p.coords.longitude}) }
}}
</script>@endpush
