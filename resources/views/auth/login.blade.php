@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
    <div class="mb-4">
        <h3 class="fw-bold mb-1" style="font-size:20px;color:#0F172A;">Welcome Back 👋</h3>
        <p class="mb-0" style="font-size:13px;color:#64748B;">Sign in to access your advisor dashboard and client records.</p>
    </div>

    {{-- Session status (e.g. password reset link sent) --}}
    @if (session('status'))
        <div class="alert-auth-error" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">
            <i class="fas fa-check-circle"></i>
            {{ session('status') }}
        </div>
    @endif

    {{-- Credential / throttle errors --}}
    @if ($errors->any())
        <div class="alert-auth-error">
            <i class="fas fa-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Work Email Address <span class="text-danger">*</span></label>
            <div class="auth-input-group">
                <i class="fas fa-envelope auth-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    placeholder="advisor@ssadvisory.co.nz"
                    required
                / maxlength="100">
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label for="password" class="form-label mb-0">Password <span class="text-danger">*</span></label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-decoration-none fw-semibold" style="font-size:12px;color:#00A8B5;">Forgot Password?</a>
                @endif
            </div>
            <div class="auth-input-group">
                <i class="fas fa-lock auth-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="current-password"
                    placeholder="••••••••••••"
                    required
                />
                <button type="button" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="form-check-input"
                    {{ old('remember', '1') ? 'checked' : '' }}
                />
                <label for="remember" class="form-check-label mb-0 fw-semibold" style="font-size:13px;">Remember me (Keep me signed in for 60 days)</label>
            </div>
        </div>

        <button type="submit" id="btn-login-submit" class="btn-auth-primary d-flex align-items-center justify-content-center gap-2">
            <span>Sign In</span>
            <i class="fas fa-arrow-right" style="font-size:14px;"></i>
        </button>
    </form>

    <div class="d-flex align-items-center gap-3 my-4" style="color:#94A3B8;font-size:12px;">
        <span class="flex-grow-1" style="height:1px;background:#E2E8F0;"></span>
        <span>OR</span>
        <span class="flex-grow-1" style="height:1px;background:#E2E8F0;"></span>
    </div>

    @if (filled(config('services.azure.client_id')) && filled(config('services.azure.client_secret')) && filled(config('services.azure.redirect')) && filled(config('services.azure.tenant')))
        <a href="{{ route('azure.redirect') }}" class="btn-auth-primary d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="background:#fff;color:#0F172A;border:1px solid #CBD5E1;">
            <i class="fab fa-microsoft" aria-hidden="true"></i>
            <span>Sign in with Microsoft</span>
        </a>
    @else
        <button type="button" class="btn-auth-primary d-flex align-items-center justify-content-center gap-2" style="background:#fff;color:#0F172A;border:1px solid #CBD5E1;opacity:.65;cursor:not-allowed;" disabled aria-describedby="azure-sso-setup-note">
            <i class="fab fa-microsoft" aria-hidden="true"></i>
            <span>Sign in with Microsoft</span>
        </button>
        <p id="azure-sso-setup-note" class="text-center text-muted mt-2 mb-0" style="font-size:12px;">Microsoft sign-in setup is incomplete.</p>
    @endif
@endsection
