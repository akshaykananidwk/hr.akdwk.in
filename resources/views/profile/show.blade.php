@extends('layouts.app')
@section('title','My Profile')
@section('page-title','My Profile')
@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center"><div class="card-body">
            <img src="{{ $user->avatar_url }}" class="rounded-circle mb-2" width="90" height="90">
            <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
            <p class="text-muted small mb-1">{{ $user->designation?->name ?? $user->roles->pluck('name')->implode(', ') }}</p>
            <p class="small text-muted">{{ $user->employee_code }} · {{ $user->department?->name }}</p>
        </div></div>
        <div class="card mt-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Change Password</h6>
            <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
                <div class="mb-2"><input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current password" required></div>
                <div class="mb-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="New password" required></div>
                <div class="mb-2"><input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Confirm password" required></div>
                <button class="btn btn-outline-primary btn-sm w-100">Update Password</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Edit Profile</h6>
            <form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Name</label><input name="name" value="{{ old('name',$user->name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="phone" value="{{ old('phone',$user->phone) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Emergency Contact</label><input name="emergency_contact_name" value="{{ old('emergency_contact_name',$user->profile?->emergency_contact_name) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Emergency Phone</label><input name="emergency_contact_phone" value="{{ old('emergency_contact_phone',$user->profile?->emergency_contact_phone) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Address</label><textarea name="current_address" class="form-control" rows="2">{{ old('current_address',$user->profile?->current_address) }}</textarea></div>
                </div>
                <button class="btn btn-ak btn-sm mt-3">Save Changes</button>
            </form>
        </div></div>

        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Upload Document</h6>
            <form method="POST" action="{{ route('profile.documents') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-4"><select name="type" class="form-select form-select-sm">@foreach(['aadhar','pan','driving_license','passport','resume','offer_letter','education','experience','address_proof'] as $t)<option value="{{ $t }}" class="text-capitalize">{{ str_replace('_',' ',$t) }}</option>@endforeach</select></div>
                <div class="col-md-5"><input type="file" name="file" class="form-control form-control-sm" required></div>
                <div class="col-md-3"><button class="btn btn-ak btn-sm w-100">Upload</button></div>
            </form>
            <hr>
            @forelse($user->documents as $doc)
                <div class="d-flex justify-content-between small border-bottom py-1">
                    <span><i class="bi bi-file-earmark me-1"></i>{{ ucfirst(str_replace('_',' ',$doc->type)) }} v{{ $doc->version }}</span>
                    <span><span class="badge bg-{{ $doc->status==='verified'?'success':'warning' }}">{{ $doc->status }}</span> <a href="{{ $doc->url }}" target="_blank" class="ms-1">View</a></span>
                </div>
            @empty
                <p class="text-muted small mb-0">No documents yet.</p>
            @endforelse
        </div></div>

        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3">Digital Agreements / Policies</h6>
            <p class="text-muted small">Read and accept the company policies. Each acceptance is logged with your IP, device and time.</p>
            @foreach($policies as $key => $label)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span class="small text-capitalize">{{ str_replace('_',' ',$label) }}</span>
                    @if(in_array($key,$accepted))
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Accepted</span>
                    @else
                        <form method="POST" action="{{ route('profile.accept-policy') }}">@csrf<input type="hidden" name="policy_type" value="{{ $key }}"><button class="btn btn-outline-primary btn-sm">I Agree</button></form>
                    @endif
                </div>
            @endforeach
        </div></div>
    </div>
</div>
@endsection
