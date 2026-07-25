@extends('layouts.app')
@section('title', $employee->exists ? 'Edit Employee' : 'Add Employee')
@section('page-title', $employee->exists ? 'Edit Employee' : 'Add Employee')
@section('content')
<form method="POST" action="{{ $employee->exists ? route('employees.update',$employee) : route('employees.store') }}">
@csrf
@if($employee->exists)@method('PUT')@endif
<div class="card mb-3"><div class="card-body">
    <h6 class="fw-semibold mb-3">Account &amp; Role</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">Full Name *</label><input name="name" value="{{ old('name',$employee->name) }}" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Employee Code</label><input name="employee_code" value="{{ old('employee_code',$employee->employee_code) }}" class="form-control" placeholder="Auto"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Phone</label><input name="phone" value="{{ old('phone',$employee->phone) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" value="{{ old('email',$employee->email) }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Password {{ $employee->exists ? '(leave blank to keep)' : '*' }}</label><input type="password" name="password" class="form-control" {{ $employee->exists ? '' : 'required' }}></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Role *</label><select name="role" class="form-select" required>@foreach($roles as $r)<option value="{{ $r }}" {{ $employee->roles->pluck('name')->first()===$r?'selected':'' }}>{{ $r }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select">@foreach(['active','probation','inactive','terminated'] as $s)<option value="{{ $s }}" {{ old('status',$employee->status)===$s?'selected':'' }} class="text-capitalize">{{ $s }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Date of Joining</label><input type="date" name="date_of_joining" value="{{ old('date_of_joining',$employee->date_of_joining?->toDateString()) }}" class="form-control"></div>
    </div>
</div></div>
<div class="card mb-3"><div class="card-body">
    <h6 class="fw-semibold mb-3">Organization</h6>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label small fw-semibold">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach($branches as $b)<option value="{{ $b->id }}" {{ $employee->branch_id==$b->id?'selected':'' }}>{{ $b->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Department</label><select name="department_id" class="form-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ $employee->department_id==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Designation</label><select name="designation_id" class="form-select"><option value="">—</option>@foreach($designations as $d)<option value="{{ $d->id }}" {{ $employee->designation_id==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Manager</label><select name="manager_id" class="form-select"><option value="">—</option>@foreach($managers as $m)<option value="{{ $m->id }}" {{ $employee->manager_id==$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select></div>
    </div>
</div></div>
<div class="card mb-3"><div class="card-body">
    <h6 class="fw-semibold mb-3">HR &amp; Bank</h6>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label small fw-semibold">Basic Salary</label><input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary',$employee->profile?->basic_salary) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Blood Group</label><input name="blood_group" value="{{ old('blood_group',$employee->profile?->blood_group) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Aadhar</label><input name="aadhar_number" value="{{ old('aadhar_number',$employee->profile?->aadhar_number) }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">PAN</label><input name="pan_number" value="{{ old('pan_number',$employee->profile?->pan_number) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Bank Name</label><input name="bank_name" value="{{ old('bank_name',$employee->profile?->bank_name) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Account No.</label><input name="bank_account_number" value="{{ old('bank_account_number',$employee->profile?->bank_account_number) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">IFSC</label><input name="bank_ifsc" value="{{ old('bank_ifsc',$employee->profile?->bank_ifsc) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Emergency Contact Name</label><input name="emergency_contact_name" value="{{ old('emergency_contact_name',$employee->profile?->emergency_contact_name) }}" class="form-control"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Emergency Contact Phone</label><input name="emergency_contact_phone" value="{{ old('emergency_contact_phone',$employee->profile?->emergency_contact_phone) }}" class="form-control"></div>
    </div>
</div></div>
<button class="btn btn-ak"><i class="bi bi-check-lg me-1"></i>{{ $employee->exists ? 'Update' : 'Create' }} Employee</button>
<a href="{{ route('employees.index') }}" class="btn btn-link">Cancel</a>
</form>
@endsection
