@extends('layouts.app')
@section('title', 'Create New Lead')
@section('content')
<div class="card-widget p-4">
                <form action="{{ route('crm.store') }}" method="POST">
                    @csrf
                    <h5 class="fw-bold text-dark mb-3">Prospect Lead Details</h5>
                    
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required placeholder="e.g. Jason" value="{{ old('first_name') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required placeholder="e.g. Taylor" value="{{ old('last_name') }}" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="jason@example.com" value="{{ old('email') }}" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required placeholder="021 555 9988" value="{{ old('phone') }}" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Acquisition Source</label>
                            <select name="lead_source_id" class="form-select">
                                <option value="">Select a source</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->id }}" @selected(old('lead_source_id') == $source->id)>{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Estimated Cover ($)</label>
                            <input type="number" name="estimated_cover" class="form-control" placeholder="500000" value="{{ old('estimated_cover') }}">
                        </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold fs-13 text-dark">Pipeline Stage</label>
                            <select name="status" class="form-select">
                                @foreach($pipelineStages as $stage)
                                    <option value="{{ $stage->stage_key }}" @selected(old('status', 'new') === $stage->stage_key)>{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end mt-4">
                            <a href="{{ route('crm.pipeline') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4">Create Lead</button>
                        </div>
                    </div>
                </form>
            



@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}"></script>
    <script src="{{ asset('assets/js/pages/crm-leads-create.js') }}"></script>
@endpush

@endsection
