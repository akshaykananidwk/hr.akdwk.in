@extends('layouts.app')
@section('title','Settings')
@section('page-title','Settings')
@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3">Company Settings</h6>
            <form method="POST" action="{{ route('settings.update') }}">@csrf
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Company Name</label><input name="company_name" value="{{ $settings['company_name']->value ?? '' }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Tagline</label><input name="company_tagline" value="{{ $settings['company_tagline']->value ?? '' }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input name="company_email" value="{{ $settings['company_email']->value ?? '' }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="company_phone" value="{{ $settings['company_phone']->value ?? '' }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Address</label><input name="company_address" value="{{ $settings['company_address']->value ?? '' }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Currency Symbol</label><input name="currency_symbol" value="{{ $settings['currency_symbol']->value ?? '₹' }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Work Start</label><input name="work_start_time" value="{{ $settings['work_start_time']->value ?? '09:30' }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Late After</label><input name="late_after_time" value="{{ $settings['late_after_time']->value ?? '09:45' }}" class="form-control"></div>
                </div>
                <button class="btn btn-ak btn-sm mt-3">Save Settings</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3">Policies</h6>
            <div class="accordion" id="polAcc">
            @foreach($policies as $i => $p)
                <div class="accordion-item">
                    <h2 class="accordion-header"><button class="accordion-button collapsed small" type="button" data-bs-toggle="collapse" data-bs-target="#pol{{ $i }}">{{ ucfirst(str_replace(['policy_','_'],['',' '],$p->key)) }}</button></h2>
                    <div id="pol{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#polAcc"><div class="accordion-body">
                        <form method="POST" action="{{ route('settings.policy') }}">@csrf
                            <input type="hidden" name="key" value="{{ $p->key }}">
                            <textarea name="value" class="form-control form-control-sm mb-2" rows="4">{{ $p->value }}</textarea>
                            <button class="btn btn-outline-primary btn-sm">Save</button>
                        </form>
                    </div></div>
                </div>
            @endforeach
            </div>
        </div></div>
    </div>
</div>
@endsection
