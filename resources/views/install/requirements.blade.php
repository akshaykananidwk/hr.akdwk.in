@extends('install.layout')
@php($step=1)
@section('install-content')
    <h5 class="fw-semibold mb-3">Server Requirements</h5>
    <ul class="list-group mb-3">
        <li class="list-group-item d-flex justify-content-between align-items-center">
            PHP &ge; 8.3 <span class="small text-muted">(current: {{ $phpVersion }})</span>
            <span>@if($phpOk)<i class="bi bi-check-circle-fill text-success"></i>@else<i class="bi bi-x-circle-fill text-danger"></i>@endif</span>
        </li>
        @foreach($extensions as $ext => $ok)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-capitalize">{{ $ext }} extension</span>
            <span>@if($ok)<i class="bi bi-check-circle-fill text-success"></i>@else<i class="bi bi-x-circle-fill text-danger"></i>@endif</span>
        </li>
        @endforeach
        @foreach($writable as $path => $ok)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $path }} writable</span>
            <span>@if($ok)<i class="bi bi-check-circle-fill text-success"></i>@else<i class="bi bi-x-circle-fill text-danger"></i>@endif</span>
        </li>
        @endforeach
    </ul>
    @php($allOk = $phpOk && !$extensions->contains(false) && !collect($writable)->contains(false))
    @if(!$allOk)<div class="alert alert-warning py-2 small">Some requirements are not met. Fix them, then reload this page.</div>@endif
    <a href="{{ route('install.database') }}" class="btn btn-ak w-100 py-2 {{ $allOk?'':'disabled' }}">Continue <i class="bi bi-arrow-right"></i></a>
@endsection
