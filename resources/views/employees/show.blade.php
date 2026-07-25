@extends('layouts.app')
@section('title',$employee->name)
@section('page-title','Employee Profile')
@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center"><div class="card-body">
            <img src="{{ $employee->avatar_url }}" class="rounded-circle mb-2" width="80" height="80">
            <h5 class="fw-bold mb-0">{{ $employee->name }}</h5>
            <p class="text-muted small mb-1">{{ $employee->designation?->name ?? $employee->roles->pluck('name')->implode(', ') }}</p>
            <span class="badge bg-{{ $employee->is_active?'success':'secondary' }}">{{ $employee->is_active?'Active':'Inactive' }}</span>
            <hr>
            <ul class="list-unstyled small text-start">
                <li class="mb-1"><i class="bi bi-hash me-2 text-muted"></i>{{ $employee->employee_code }}</li>
                <li class="mb-1"><i class="bi bi-envelope me-2 text-muted"></i>{{ $employee->email }}</li>
                <li class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i>{{ $employee->phone ?? '—' }}</li>
                <li class="mb-1"><i class="bi bi-building me-2 text-muted"></i>{{ $employee->department?->name ?? '—' }}</li>
                <li class="mb-1"><i class="bi bi-person-badge me-2 text-muted"></i>Manager: {{ $employee->manager?->name ?? '—' }}</li>
                <li class="mb-1"><i class="bi bi-calendar me-2 text-muted"></i>Joined: {{ $employee->date_of_joining?->format('d M Y') ?? '—' }}</li>
            </ul>
            @can('manage employees')
            <div class="d-flex gap-2">
                <a href="{{ route('employees.edit',$employee) }}" class="btn btn-outline-primary btn-sm flex-grow-1"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form method="POST" action="{{ route('employees.toggle',$employee) }}">@csrf<button class="btn btn-outline-{{ $employee->is_active?'danger':'success' }} btn-sm">{{ $employee->is_active?'Deactivate':'Activate' }}</button></form>
            </div>
            @endcan
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">HR Details</h6>
            <div class="row small g-2">
                <div class="col-md-4">Blood Group: <strong>{{ $employee->profile?->blood_group ?? '—' }}</strong></div>
                <div class="col-md-4">Aadhar: <strong>{{ $employee->profile?->aadhar_number ?? '—' }}</strong></div>
                <div class="col-md-4">PAN: <strong>{{ $employee->profile?->pan_number ?? '—' }}</strong></div>
                <div class="col-md-6">Bank: <strong>{{ $employee->profile?->bank_name ?? '—' }}</strong></div>
                <div class="col-md-6">Account: <strong>{{ $employee->profile?->bank_account_number ?? '—' }}</strong></div>
                @can('view payroll')<div class="col-md-6">Basic Salary: <strong>{{ money($employee->profile?->basic_salary ?? 0) }}</strong></div>@endcan
            </div>
        </div></div>
        <div class="card mb-3"><div class="card-body">
            <h6 class="fw-semibold mb-3">Documents ({{ $employee->documents->count() }})</h6>
            @forelse($employee->documents as $doc)
                <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                    <span><i class="bi bi-file-earmark me-1"></i>{{ ucfirst(str_replace('_',' ',$doc->type)) }} <span class="text-muted">v{{ $doc->version }}</span></span>
                    <span><span class="badge bg-{{ $doc->status==='verified'?'success':($doc->status==='rejected'?'danger':'warning') }}">{{ $doc->status }}</span> <a href="{{ $doc->url }}" target="_blank" class="ms-2">View</a></span>
                </div>
            @empty
                <p class="text-muted small mb-0">No documents uploaded.</p>
            @endforelse
        </div></div>
        <div class="card"><div class="card-body">
            <h6 class="fw-semibold mb-3">Policy Acceptances ({{ $employee->policyAcceptances->count() }})</h6>
            @forelse($employee->policyAcceptances as $p)
                <div class="small border-bottom py-1"><i class="bi bi-check-circle text-success me-1"></i>{{ ucfirst(str_replace('_',' ',$p->policy_type)) }} — {{ $p->accepted_at?->format('d M Y') }} <span class="text-muted">({{ $p->ip_address }})</span></div>
            @empty
                <p class="text-muted small mb-0">No policies accepted yet.</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection
