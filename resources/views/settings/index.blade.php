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
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-whatsapp text-success me-1"></i>WhatsApp (AK Bulk)</h6>
                @if(($whatsapp['whatsapp_enabled']->value ?? '0') === '1')<span class="badge bg-success">Enabled</span>@else<span class="badge bg-secondary">Off</span>@endif
            </div>
            <form method="POST" action="{{ route('settings.whatsapp') }}">@csrf
                <div class="form-check form-switch mb-2">
                    <input type="checkbox" name="whatsapp_enabled" value="1" class="form-check-input" id="waEnabled" {{ ($whatsapp['whatsapp_enabled']->value ?? '0')==='1'?'checked':'' }}>
                    <label class="form-check-label small" for="waEnabled">Enable WhatsApp notifications</label>
                </div>
                <div class="row g-2">
                    <div class="col-12"><label class="form-label small fw-semibold">API Key</label><input name="whatsapp_api_key" value="{{ $whatsapp['whatsapp_api_key']->value ?? '' }}" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small fw-semibold">Session ID</label><input name="whatsapp_session_id" value="{{ $whatsapp['whatsapp_session_id']->value ?? '' }}" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small fw-semibold">Admin Mobile</label><input name="whatsapp_admin_mobile" value="{{ $whatsapp['whatsapp_admin_mobile']->value ?? '' }}" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Group ID</label><input name="whatsapp_group_id" value="{{ $whatsapp['whatsapp_group_id']->value ?? '' }}" class="form-control form-control-sm" placeholder="1234567890@g.us"></div>
                </div>
                <hr class="my-2">
                <p class="small text-muted mb-2">Templates — use <code>{name}</code>, <code>{time}</code>, <code>{date}</code></p>
                @foreach(['whatsapp_msg_in'=>'IN','whatsapp_msg_out'=>'OUT','whatsapp_msg_lunch_out'=>'Lunch Out','whatsapp_msg_lunch_in'=>'Lunch In','whatsapp_msg_leave'=>'Leave'] as $k=>$label)
                    <div class="mb-2"><label class="form-label small">{{ $label }}</label><input name="{{ $k }}" value="{{ $whatsapp[$k]->value ?? '' }}" class="form-control form-control-sm"></div>
                @endforeach
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-success btn-sm flex-grow-1"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('settings.whatsapp.test') }}" class="mt-2">@csrf
                <button class="btn btn-outline-success btn-sm w-100"><i class="bi bi-send me-1"></i>Send Test Group Message</button>
            </form>
        </div></div>

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
