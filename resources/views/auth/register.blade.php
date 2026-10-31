@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-subtitle">Get started with SS Advisory Lead Engine</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- Name --}}
        <div class="mb-3">
            <label for="name" class="form-label">Full name</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                autocomplete="name"
                autofocus
                placeholder="Jane Smith"
                required
            />
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                autocomplete="email"
                placeholder="you@example.com"
                required
            / maxlength="100">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    autocomplete="new-password"
                    placeholder="Min. 8 characters"
                    required
                />
                <button type="button" class="btn btn-toggle-pw" tabindex="-1" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <div class="input-group">
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    autocomplete="new-password"
                    placeholder="Repeat password"
                    required
                />
                <button type="button" class="btn btn-toggle-pw" tabindex="-1" aria-label="Toggle confirm password visibility">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" id="btn-register-submit" class="btn-auth-submit">
            Create Account
        </button>
    </form>

    <p class="auth-divider mt-4">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </p>
@endsection
