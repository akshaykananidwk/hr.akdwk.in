@extends('layouts.app')
@section('title','Tasks')
@section('page-title','Tasks')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5 class="fw-bold mb-0">Tasks</h5>
    @if($canAssign)<button class="btn btn-ak btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal"><i class="bi bi-plus-lg me-1"></i>Assign Task</button>@endif
</div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Task</th><th>Assigned To</th><th>Priority</th><th>Due</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($tasks as $t)
        <tr>
            <td><div class="fw-semibold small">{{ $t->title }}</div><small class="text-muted">{{ Str::limit($t->description,50) }}</small></td>
            <td class="small">{{ $t->assignee?->name }}</td>
            <td><span class="badge bg-{{ $t->priority==='urgent'?'danger':($t->priority==='high'?'warning':'secondary') }} text-capitalize">{{ $t->priority }}</span></td>
            <td class="small">{{ $t->due_date?->format('d M') ?? '—' }}</td>
            <td>
                <form method="POST" action="{{ route('tasks.status',$t) }}" class="d-inline">@csrf
                    <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                        @foreach(['pending','in_progress','completed','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $t->status===$s?'selected':'' }} class="text-capitalize">{{ str_replace('_',' ',$s) }}</option>
                        @endforeach
                    </select>
                </form>
            </td>
            <td class="small text-muted">{{ $t->completion }}%</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No tasks.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $tasks->links() }}
</div></div>

@if($canAssign)
<div class="modal fade" id="taskModal"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('tasks.store') }}">@csrf
        <div class="modal-header"><h6 class="modal-title">Assign Task</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label class="form-label small">Title</label><input name="title" class="form-control" required></div>
            <div class="mb-2"><label class="form-label small">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="row g-2">
                <div class="col"><label class="form-label small">Assign To</label><select name="assigned_to" class="form-select" required>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                <div class="col"><label class="form-label small">Priority</label><select name="priority" class="form-select">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" class="text-capitalize">{{ $p }}</option>@endforeach</select></div>
            </div>
            <div class="mt-2"><label class="form-label small">Due Date</label><input type="date" name="due_date" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-ak btn-sm">Assign</button></div>
    </form>
</div></div></div>
@endif
@endsection
