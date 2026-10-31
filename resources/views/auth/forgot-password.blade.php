@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="mb-4">
        <div style="width:48px;height:48px;border-radius:50%;background:#EFF6FF;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="fas fa-key" style="color:#00A8B5;font-size:20px;"></i>
        </div>
        <h3 class="fw-bold mb-1" style="font-size:20px;color:#0F172A;">Forgot Password? 🔑</h3>
        <p class="mb-0" style="font-size:13px;color:#64748B;">Enter your registered work email address below to receive password reset instructions.</p>
    </div>

    {{-- Success status --}}
    @if (session('status'))
        <div class="alert-auth-error" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;margin-bottom:1.25rem;">
            <i class="fas fa-check-circle"></i>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Work Email Address <span class="text-danger">*</span></label>
            <div class="auth-input-group">
                <i class="fas fa-envelope auth-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="advisor@ssadvisory.co.nz"
                    autofocus
                    required
                / maxlength="100">
            </div>
            @error('email')
                <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-auth-primary d-flex align-items-center justify-content-center gap-2 mb-3">
            <span>Send Reset Instructions</span>
            <i class="fas fa-paper-plane" style="font-size:14px;"></i>
        </button>
    </form>

    <div class="text-center mt-2">
        <a href="{{ route('login') }}" class="fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:13px;color:#64748B;">
            <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back to Login Page
        </a>
    </div>
@endsection
