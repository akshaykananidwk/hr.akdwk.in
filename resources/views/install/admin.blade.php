@extends('install.layout')
@php($step=3)
@section('install-content')
    <h5 class="fw-semibold mb-3">Company &amp; Admin Account</h5>
    <form method="POST" action="{{ route('install.finish') }}">
        @csrf
        <div class="mb-2"><label class="form-label small fw-semibold">Company Name</label><input name="company_name" value="{{ old('company_name','AK Computer') }}" class="form-control" required></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Application URL</label><input name="app_url" value="{{ old('app_url', url('/')) }}" class="form-control" placeholder="https://hr.akdwk.in"></div>
        <hr>
        <div class="mb-2"><label class="form-label small fw-semibold">Admin Name</label><input name="admin_name" value="{{ old('admin_name') }}" class="form-control" required></div>
        <div class="mb-2"><label class="form-label small fw-semibold">Admin Email</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" class="form-control" required></div>
        <div class="row g-2">
            <div class="col-md-6 mb-2"><label class="form-label small fw-semibold">Password</label><input type="password" name="admin_password" class="form-control" required></div>
            <div class="col-md-6 mb-2"><label class="form-label small fw-semibold">Confirm Password</label><input type="password" name="admin_password_confirmation" class="form-control" required></div>
        </div>
        <div class="form-check my-3">
            <input type="checkbox" name="seed_demo" value="1" class="form-check-input" id="seed_demo" checked>
            <label class="form-check-label small" for="seed_demo">Install demo data (sample employees, leads &amp; products)</label>
        </div>
        <button class="btn btn-ak w-100 py-2">Install Now <i class="bi bi-rocket-takeoff"></i></button>
        <p class="text-muted small text-center mt-2">This runs migrations &amp; seeders. It may take up to a minute.</p>
    </form>
@endsection
