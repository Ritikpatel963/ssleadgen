@extends('layouts.app')
@section('title', 'Client Requests')

@section('content')
    <div>
        <h4 class="fw-bold text-dark mb-1">Client Requests <span id="clientRequestsCount"></span></h4>
        <p class="text-muted fs-13 mb-0">Review submitted client service requests and their resolution status.</p>
    </div>

    <section class="dash-filter-card">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap flex-fill">
                <div style="min-width: 170px;" class="flex-fill">
                    <label class="fs-11 fw-bold text-muted mb-1 d-block">Client Search (First, Last Name)</label>
                    <div class="dash-filter-input-group">
                        <i class="feather-search"></i>
                        <input type="text" id="filterClientSearch" class="form-control dash-filter-input" placeholder="Search by client name" />
                    </div>
                </div>
                <div style="min-width: 170px;" class="flex-fill">
                    <label class="fs-11 fw-bold text-muted mb-1 d-block">Mobile Number</label>
                    <div class="dash-filter-input-group">
                        <i class="feather-search"></i>
                        <input type="text" id="filterNumberSearch" class="form-control dash-filter-input" placeholder="Search by mobile number" />
                    </div>
                </div>
                <div style="min-width: 170px;" class="flex-fill">
                    <label class="fs-11 fw-bold text-muted mb-1 d-block">Address (Street Address, Pin code, City, Suburb)</label>
                    <div class="dash-filter-input-group">
                        <i class="feather-search"></i>
                        <input type="text" id="filterAddressSearch" class="form-control dash-filter-input" placeholder="Search by address" />
                    </div>
                </div>
                <div style="min-width: 150px;" class="flex-fill">
                    <label class="fs-11 fw-bold text-muted mb-1 d-block">Date of Birth Search</label>
                    <div class="dash-filter-input-group">
                        <i class="feather-calendar"></i>
                        <input type="date" id="filterDobSearch" class="form-control dash-filter-input" />
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button id="btnExecuteSearch" class="btn btn-navy px-3 py-2 fw-bold"><i class="feather-search me-1"></i> Search</button>
                <button id="btnClearSearch" class="btn btn-light px-3 py-2 fw-semibold">Clear</button>
            </div>
        </div>
    </section>

    <div class="card-widget">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="clientRequestsTable" data-server-paginated>
                <thead>
                    <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                        <th>Date</th><th>Client Name</th><th>Insurance Company</th><th>Request Type</th>
                        <th>Process Status</th><th>Status</th><th>Mobile</th><th>Address</th><th>Date of Birth</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

@include('pages.clients.partials.client-request-modal')
@include('pages.auth.partials.client-details-modal')
@push('scripts')
    <script src="{{ asset('assets/js/pages/client-requests.js') }}?v={{ filemtime(public_path('assets/js/pages/client-requests.js')) }}"></script>
@endpush
@endsection
