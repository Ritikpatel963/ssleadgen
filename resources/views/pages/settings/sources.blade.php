@extends('layouts.app')
@section('title', 'Lead Sources Settings')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Lead Acquisition Source Settings</h4>
                    <p class="text-muted fs-13 mb-0">Configure lead channels, partner attribution rules, and source tracking.</p>
                </div>
                <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addSourceModal"><i class="feather-plus me-1"></i> Add Lead Source</button>
            </div>

            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="sourcesSettingTable">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Source Name</th>
                                <th>Category</th>
                                <th>Total Leads</th>
                                <th>Conversion Rate</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-dark fs-13">Referral</td>
                                <td class="fs-13 text-muted">Organic Network</td>
                                <td class="fs-13 fw-semibold">485</td>
                                <td class="fs-13 fw-bold text-success">68.4%</td>
                                <td><span class="status-pill-inforce">Active</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light py-1 px-2">Edit</button></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">Existing Client</td>
                                <td class="fs-13 text-muted">Cross-Sell / Up-Sell</td>
                                <td class="fs-13 fw-semibold">320</td>
                                <td class="fs-13 fw-bold text-success">74.2%</td>
                                <td><span class="status-pill-inforce">Active</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light py-1 px-2">Edit</button></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">Online / Website</td>
                                <td class="fs-13 text-muted">Digital Inbound</td>
                                <td class="fs-13 fw-semibold">210</td>
                                <td class="fs-13 fw-bold text-success">42.1%</td>
                                <td><span class="status-pill-inforce">Active</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light py-1 px-2">Edit</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            

</div>
    </div>

    <!-- Modal: Add Lead Source -->
    <div class="modal fade" id="addSourceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-plus me-2"></i> Add Lead Acquisition Source</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addSourceForm" onsubmit="event.preventDefault(); handleAddNewSource();">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Source Channel Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sourceNameInput" placeholder="e.g. Meta Lead Campaign" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Channel Category</label>
                            <input type="text" class="form-control" id="sourceCatInput" placeholder="e.g. Paid Social Media" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Source</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}"></script>
    <script src="{{ asset('assets/js/pages/settings-sources.js') }}?v={{ filemtime(public_path('assets/js/pages/settings-sources.js')) }}"></script>
@endpush

@endsection
