@extends('layouts.app')
@section('title', 'Page Not Found')
@section('content')
    <div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 70vh;">
        <div class="mb-4">
            <div class="avatar-text avatar-xl bg-soft-primary text-primary rounded-circle mx-auto fs-1"
                style="width: 100px; height: 100px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="feather-alert-triangle"></i>
            </div>
        </div>
        <h1 class="fw-bold text-dark mb-2" style="font-size: 3rem;">404</h1>
        <h4 class="fw-bold text-dark mb-3">Page Not Found</h4>
        <p class="text-muted fs-14 mb-4" style="max-width: 400px;">
            Oops! The page you are looking for doesn't exist, has been moved, or is temporarily unavailable.
        </p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
            <i class="feather-home me-2"></i> Return to Dashboard
        </a>
    </div>
@endsection