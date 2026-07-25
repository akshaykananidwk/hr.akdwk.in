@extends('layouts.app')
@section('title','Attendance')
@section('page-title','Attendance')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Attendance</h5>
    <div class="d-flex gap-2" x-data="{}">
        @if(!$today || !$today->check_in_at)
            <form method="POST" action="{{ route('attendance.checkin') }}" id="ciF">@csrf
                <input type="hidden" name="latitude" id="cilat"><input type="hidden" name="longitude" id="cilng">
                <button class="btn btn-ak btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Check In</button>
            </form>
        @elseif(!$today->check_out_at)
            <form method="POST" action="{{ route('attendance.checkout') }}" id="coF">@csrf
                <input type="hidden" name="latitude" id="colat"><input type="hidden" name="longitude" id="colng">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Check Out</button>
            </form>
        @else
            <span class="badge bg-success align-self-center">Completed today</span>
        @endif
    </div>
</div>
<div class="card"><div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr>@if($isManager)<th>Employee</th>@endif<th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($records as $r)
        <tr>
            @if($isManager)<td class="small fw-semibold">{{ $r->user->name }}</td>@endif
            <td class="small">{{ $r->date->format('d M Y') }}</td>
            <td class="small">{{ $r->check_in_at?->format('h:i A') ?? '—' }} @if($r->is_late)<span class="badge bg-warning">Late</span>@endif</td>
            <td class="small">{{ $r->check_out_at?->format('h:i A') ?? '—' }}</td>
            <td class="small">{{ $r->working_hours }}h</td>
            <td><span class="badge bg-{{ $r->status==='present'?'success':($r->status==='half_day'?'warning':'secondary') }} text-capitalize">{{ str_replace('_',' ',$r->status) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted small">No attendance records yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
{{ $records->links() }}
</div></div>
@endsection
@push('scripts')<script>
navigator.geolocation && navigator.geolocation.getCurrentPosition(p=>{
  ['cilat','colat'].forEach(i=>{let e=document.getElementById(i);if(e)e.value=p.coords.latitude});
  ['cilng','colng'].forEach(i=>{let e=document.getElementById(i);if(e)e.value=p.coords.longitude});
});
</script>@endpush
