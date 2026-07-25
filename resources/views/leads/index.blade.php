@extends('layouts.app')
@section('title','Leads')
@section('page-title','Leads / CRM')
@php($sc=['new'=>'secondary','contacted'=>'info','demo'=>'primary','negotiation'=>'warning','won'=>'success','lost'=>'danger','follow_up'=>'dark'])
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Leads</h5>
    @can('manage leads')<a href="{{ route('leads.create') }}" class="btn btn-ak btn-sm"><i class="bi bi-plus-lg me-1"></i>New Lead</a>@endcan
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach($sc as $s=>$c)
        <a href="{{ route('leads.index',['status'=>$s]) }}" class="badge bg-{{ $c }} text-decoration-none text-capitalize">{{ str_replace('_',' ',$s) }} {{ $statusCounts[$s] ?? 0 }}</a>
    @endforeach
    <a href="{{ route('leads.index') }}" class="badge bg-light text-dark text-decoration-none">All</a>
</div>
<div class="card"><div class="card-body">
<form class="mb-3"><div class="input-group input-group-sm" style="max-width:320px"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name / business / phone"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button></div></form>
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Business / Name</th><th>Product</th><th>Assigned</th><th>Value</th><th>Follow-up</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($leads as $l)
        <tr>
            <td><a href="{{ route('leads.show',$l) }}" class="text-decoration-none fw-semibold">{{ $l->business_name ?: $l->name }}</a><br><small class="text-muted">{{ $l->phone }}</small></td>
            <td class="small">{{ $l->product?->name ?? '—' }}</td>
            <td class="small">{{ $l->assignee?->name ?? '—' }}</td>
            <td class="small">{{ money($l->expected_value) }}</td>
            <td class="small text-muted">{{ $l->next_followup_at?->format('d M') ?? '—' }}</td>
            <td><span class="badge bg-{{ $sc[$l->status] ?? 'secondary' }} text-capitalize">{{ str_replace('_',' ',$l->status) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No leads found.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $leads->links() }}
</div></div>
@endsection
