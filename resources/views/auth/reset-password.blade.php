@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
    <div class="mb-4">
        <div style="width:48px;height:48px;border-radius:50%;background:#EFF6FF;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="fas fa-lock" style="color:#00A8B5;font-size:20px;"></i>
        </div>
        <h3 class="fw-bold mb-1" style="font-size:20px;color:#0F172A;">Set New Password 🔐</h3>
        <p class="mb-0" style="font-size:13px;color:#64748B;">Your new password must be at least 8 characters and different from previous passwords.</p>
    </div>

    @if ($errors->any())
        <div class="alert-auth-error" style="margin-bottom:1.25rem;">
            <i class="fas fa-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}" />
        <input type="hidden" name="email" value="{{ $email ?? old('email') }}" / maxlength="100">

        {{-- New Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
            <div class="auth-input-group">
                <i class="fas fa-lock auth-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Min. 8 characters"
                    autofocus
                    required
                />
                <button type="button" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="auth-input-group">
                <i class="fas fa-shield-alt auth-icon"></i>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Repeat your new password"
                    required
                />
                <button type="button" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-auth-primary d-flex align-items-center justify-content-center gap-2 mb-3">
            <span>Update Password</span>
            <i class="fas fa-check" style="font-size:14px;"></i>
        </button>
    </form>

    <div class="text-center mt-2">
        <a href="{{ route('login') }}" class="fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:13px;color:#64748B;">
            <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back to Sign In
        </a>
    </div>
@endsection
