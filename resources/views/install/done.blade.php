@extends('install.layout')
@php($step=4)
@section('install-content')
    <div class="text-center py-3">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem"></i>
        <h5 class="fw-bold mt-3">Installation Complete!</h5>
        <p class="text-muted">AK Workforce Pro is ready. Sign in with your admin account:</p>
        <p class="mb-1"><strong>{{ $email }}</strong></p>
        <a href="{{ route('login') }}" class="btn btn-ak px-4 mt-3">Go to Login <i class="bi bi-box-arrow-in-right"></i></a>
        <div class="alert alert-warning small mt-4 text-start">
            <i class="bi bi-shield-lock me-1"></i> For security, consider deleting <code>routes</code> access to <code>/install</code> or keeping the <code>storage/installed</code> lock file in place. Re-installation is blocked while that file exists.
        </div>
    </div>
@endsection
