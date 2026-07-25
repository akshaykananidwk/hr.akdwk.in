@extends('layouts.app')
@section('title','Employees')
@section('page-title','Employees')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5 class="fw-bold mb-0">Employees</h5>
    @can('manage employees')<a href="{{ route('employees.create') }}" class="btn btn-ak btn-sm"><i class="bi bi-person-plus me-1"></i>Add Employee</a>@endcan
</div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name / email / code"></div>
    <div class="col-md-3"><select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">All Departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button></div>
</form>
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Employee</th><th>Code</th><th>Department</th><th>Role</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($employees as $e)
        <tr>
            <td><div class="d-flex align-items-center gap-2"><img src="{{ $e->avatar_url }}" class="avatar-sm" width="32" height="32"><div><div class="fw-semibold small">{{ $e->name }}</div><small class="text-muted">{{ $e->email }}</small></div></div></td>
            <td class="small">{{ $e->employee_code }}</td>
            <td class="small">{{ $e->department?->name ?? '—' }}</td>
            <td class="small">{{ $e->roles->pluck('name')->implode(', ') }}</td>
            <td><span class="badge bg-{{ $e->is_active?'success':'secondary' }}">{{ $e->is_active?'Active':'Inactive' }}</span></td>
            <td><a href="{{ route('employees.show',$e) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No employees found.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $employees->links() }}
</div></div>
@endsection
