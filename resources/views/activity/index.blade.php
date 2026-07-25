@extends('layouts.app')
@section('title','Activity Log')
@section('page-title','Activity Log')
@section('content')
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-sm table-hover align-middle">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr>
            <td class="small text-muted">{{ $log->created_at->format('d M, h:i A') }}</td>
            <td class="small">{{ $log->user?->name ?? 'System' }}</td>
            <td><code class="small">{{ $log->action }}</code></td>
            <td class="small">{{ $log->description }}</td>
            <td class="small text-muted">{{ $log->ip_address }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted small">No activity yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $logs->links() }}
</div></div>
@endsection
