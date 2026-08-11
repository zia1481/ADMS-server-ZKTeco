@extends('layouts.auth')

@section('auth-title', 'Verify Email')
@section('title', 'ADMS — Verify Email')

@section('content')
    @if(session('resent'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ __('A fresh verification link has been sent to your email address.') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <p class="text-muted small mb-4">
        {{ __('Before proceeding, please check your email for a verification link.') }}
    </p>

    <div class="text-center">
        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-envelope-arrow-up me-1"></i>{{ __('Resend Verification Email') }}
            </button>
        </form>
    </div>
@endsection
