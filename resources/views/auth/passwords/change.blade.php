@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6 col-xl-5">
        @include('layouts.partials.page-header', [
            'title' => 'Change Password',
            'subtitle' => 'Update the password for your account.',
        ])

        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-lock me-1"></i>Change Password
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin-password.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label">{{ __('Current Password') }} <span class="required-mark">*</span></label>
                        <input id="current_password" type="password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            name="current_password" required autocomplete="current-password">

                        @error('current_password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('New Password') }} <span class="required-mark">*</span></label>
                        <input id="password" type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password" required autocomplete="new-password">

                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">{{ __('Confirm New Password') }} <span class="required-mark">*</span></label>
                        <input id="password-confirm" type="password"
                            class="form-control"
                            name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('devices.Attendance') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ __('Change Password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
