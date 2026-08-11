@extends('layouts.auth')

@section('auth-title', 'Reset Password')
@section('title', 'ADMS — Reset Password')

@section('content')
    <p class="text-muted small mb-4">
        {{ __('Enter your email address and we will send you a password reset link.') }}
    </p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email Address') }} <span class="required-mark">*</span></label>
            <input id="email" type="email"
                class="form-control @error('email') is-invalid @enderror"
                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                placeholder="you@company.com">

            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i>{{ __('Send Password Reset Link') }}
            </button>
        </div>

        <div class="text-center">
            <a class="small text-decoration-none" href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Sign In') }}
            </a>
        </div>
    </form>
@endsection
