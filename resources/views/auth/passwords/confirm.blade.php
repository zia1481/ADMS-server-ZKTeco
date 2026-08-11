@extends('layouts.auth')

@section('auth-title', 'Confirm Password')
@section('title', 'ADMS — Confirm Password')

@section('content')
    <p class="text-muted small mb-4">
        {{ __('Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }} <span class="required-mark">*</span></label>
            <input id="password" type="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password" required autocomplete="current-password">

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-shield-lock me-1"></i>{{ __('Confirm Password') }}
            </button>
        </div>

        @if(Route::has('password.request'))
            <div class="text-center">
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            </div>
        @endif
    </form>
@endsection
