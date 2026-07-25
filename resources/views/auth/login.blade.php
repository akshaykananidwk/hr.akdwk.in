@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
    <h5 class="fw-semibold mb-1">{{ __('Welcome back') }}</h5>
    <p class="text-muted small mb-4">{{ __('Sign in to your account to continue') }}</p>

    @if($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">{{ __('Phone or Email') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="login" value="{{ old('login') }}" class="form-control" placeholder="Phone number or email" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold">{{ __('Password') }}</label>
            <div class="input-group" x-data="{ show:false }">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input :type="show ? 'text' : 'password'" name="password" class="form-control" placeholder="••••••••" required>
                <button type="button" class="btn btn-outline-secondary" @click="show=!show"><i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i></button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label small" for="remember">{{ __('Remember me') }}</label>
            </div>
        </div>
        <button class="btn btn-ak w-100 py-2 fw-semibold">{{ __('Sign In') }} <i class="bi bi-arrow-right ms-1"></i></button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">&copy; {{ date('Y') }} {{ setting('company_name', 'AK Computer') }}</small>
    </div>
@endsection
