@extends('layouts.app')
@section('title', 'Login Client Directory')
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="feather-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="feather-alert-circle me-2"></i> <strong>Validation Errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Login Client Directory <span id="loginClientsCount"></span></h4>
                    <p class="text-muted fs-13 mb-0">Clients with pending policy submissions, compliance audits, and RoA requirements.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-bold" onclick="openImportClientsModal()">
                        <i class="feather-upload me-1"></i> Import Clients
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="openAddLoginClientModal()">
                        <i class="feather-plus me-1"></i> Add Login Client
                    </button>
                </div>
            </div>

            <!-- Top Filter Search Bar -->
            <section class="dash-filter-card">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap flex-fill">
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Client Search (First, Last Name)</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterClientSearch" class="form-control dash-filter-input"
                                    placeholder="Search by client name" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Mobile Number</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterNumberSearch" class="form-control dash-filter-input"
                                    placeholder="Search by mobile number" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Address (Street Address, Pin code, City, Suburb)</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterAddressSearch" class="form-control dash-filter-input"
                                    placeholder="Search by address" />
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
                        <button id="btnExecuteSearch" class="btn btn-navy px-3 py-2 fw-bold"><i
                                class="feather-search me-1"></i> Search</button>
                        <button id="btnClearSearch" class="btn btn-light px-3 py-2 fw-semibold">Clear</button>
                    </div>
                </div>
            </section>

            <!-- Table Card -->
            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="loginClientsTable" data-server-paginated data-can-write="{{ auth()->user()->canWrite('clients_login') ? '1' : '0' }}">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Policy No.</th>
                                <th>Company</th>
                                <th>Client Name</th>
                                <th>Mobile</th>
                                <th>Login Date</th>
                                <th>ANP</th>
                                <th>Status - Sent to Compliance</th>
                                <th>Sent to Client</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            

@include('pages.clients.partials.client-request-modal')

    
    <!-- Modal: Claim Update Popup -->
    <div class="modal fade" id="lodgeClaimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-shield me-2"></i> <span id="claimModalTitle">New Claim Update</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="lodgeClaimForm" method="POST" action="" onsubmit="return true;">
                    @csrf
                    <input type="hidden" id="claimClientId">
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Provider Info
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="claimClientNameDisplay" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                    <select class="form-select" id="claimCompanyDisplay" name="company">
                                        <option value="" selected disabled>Select Company</option>
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Claims (Type / Description) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="claimTypeInput" name="claim_type" placeholder="e.g. Medical Surgery / Trauma" required maxlength="100">
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="claimAdminSelect" name="claim_admin">
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-clock text-primary fs-15"></i> 2. Timeline & Status Outcome
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Processed Date</label>
                                    <input type="date" class="form-control" id="claimProcessedDateInput" name="claim_processed_date">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Update (Status)</label>
                                    <select class="form-select" id="claimUpdateSelect" name="claim_update_status">
                                        <option value="Under Assessment">Under Assessment</option>
                                        <option value="Medical Review">Medical Review</option>
                                        <option value="Document Verification">Document Verification</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Declined">Declined</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Approved Date</label>
                                    <input type="date" class="form-control" id="claimApprovedDateInput" name="claim_approved_date">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Result / Outcome</label>
                                    <input type="text" class="form-control" id="claimOutcomeInput" name="claim_result" placeholder="e.g. Approved / Paid $18,500" maxlength="255">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Claim Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Mark as Inactive Confirmation -->
    <div class="modal fade" id="addInactiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #0A192F;">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-x me-2"></i> Mark Client as Inactive</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addInactiveForm" method="POST" action="">
                    @csrf
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 text-secondary" style="font-size: 3rem;">
                            <i class="feather-alert-circle"></i>
                        </div>
                        <h4 class="mb-3 text-dark fw-bold">Are you sure?</h4>
                        <p class="text-muted fs-14">
                            You are about to move <strong id="inactiveClientNameDisplay"></strong> to the Inactive Clients directory.
                        </p>
                    </div>
                    <div class="modal-footer bg-light justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn px-4 fw-bold text-white" style="background-color: #00A8B5; border-color: #00A8B5;" onmouseover="this.style.backgroundColor='#008C97'" onmouseout="this.style.backgroundColor='#00A8B5'">Update Inactive Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: NPW Deferred Popup -->
    <div class="modal fade" id="addNpwModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-clock me-2"></i> <span id="npwModalTitle">NPW Deferred</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addNpwForm" method="POST" action="" onsubmit="return true;">
                    @csrf
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Insurance Provider
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="npwClientNameDisplay" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Company</label>
                                    <select class="form-select" id="npwCompanyDisplay" name="company">
                                        <option value="" selected disabled>Select Company</option>
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-file-text text-primary fs-15"></i> 2. Policy & Admin Details
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">Issue Date <span class="text-danger"></span></label>
                                    <input type="date" class="form-control" id="npwIssueDateInput" name="npw_issue_date" >
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">Premium <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="npwPremiumInput" name="npw_premium" placeholder="e.g. 150.00" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">Premium Mode</label>
                                    <select class="form-select" id="npwPremiumModeSelect" name="npw_premium_mode">
                                        <option value="" disabled selected>Select Premium Mode</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Fortnightly">Fortnightly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Annually">Annually</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="npwAdminSelect" name="npw_admin">
                                        <option value="" disabled selected>Select Advisor</option>
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Pending</label>
                                    <select class="form-select" id="npwPendingSelect" name="npw_pending">
                                        <option value="" disabled selected>Select Pending</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-message-square text-primary fs-15"></i> 3. Notes & Comments
                            </div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Notes/Comments</label>
                                    <textarea class="form-control" id="npwNotesCommentsInput" name="npw_comments" rows="3" placeholder="Enter notes or explanation for deferral..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save NPW Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Cancellation Update Popup -->
    <div class="modal fade" id="addCancellationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-file-minus me-2"></i> <span id="cancModalTitle">New Cancellation Update</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addCancellationForm" method="POST" action="" onsubmit="return true;">
                    @csrf
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Insurance Provider
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cancClientNameDisplay" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Company</label>
                                    <select class="form-select" id="cancCompanyDisplay" name="company">
                                        <option value="" selected disabled>Select Company</option>
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-clock text-primary fs-15"></i> 2. Cancellation Timeline & Outcome
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Cancellation Sent</label>
                                    <input type="date" class="form-control" id="cancDateSentInput" name="canc_date_sent">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Completed</label>
                                    <input type="date" class="form-control" id="cancCompletedInput" name="canc_completed_date">
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="cancAdminSelect" name="canc_admin">
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Outcome</label>
                                    <input type="text" class="form-control" id="cancOutcomeInput" name="canc_outcome" placeholder="e.g. Cancelled - Premium Cost Concerns" maxlength="255">
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-message-square text-primary fs-15"></i> 3. Comments & Internal Notes
                            </div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Comments</label>
                                    <textarea class="form-control" id="cancCommentsInput" name="canc_comments" rows="3" placeholder="Enter comments or cancellation reason..." maxlength="1000"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Cancellation Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: View Login Client Details (Read-only Form Layout) -->
    <div class="modal fade" id="viewLoginClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-check me-2"></i> Client Profile Details - <span id="viewPolicyNoHeader"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    
                    <!-- SECTION 1: POLICY & PROVIDER INFO -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Policy No.</label>
                                <input type="text" class="form-control text-primary" id="viewPolicyNo" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                <input type="text" class="form-control" id="viewCompany" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Login Date</label>
                                <input type="text" class="form-control" id="viewLoginDate" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label>
                                <input type="text" class="form-control text-success fw-bold" id="viewAnp" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CLIENT PERSONAL & CONTACT -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-user text-primary fs-15"></i> 2. Client Contact Information
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">First Name</label>
                                <input type="text" class="form-control" id="viewFirstName" readonly>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Last Name</label>
                                <input type="text" class="form-control" id="viewLastName" readonly>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                <input type="text" class="form-control" id="viewDob" readonly>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Mobile Number</label>
                                <input type="text" class="form-control" id="viewMobile" readonly>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                                <input type="text" class="form-control" id="viewEmail" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: RESIDENTIAL ADDRESS -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-map-pin text-primary fs-15"></i> 3. Address Details
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Street Address</label>
                                <input type="text" class="form-control" id="viewAddress" readonly>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Suburb</label>
                                <input type="text" class="form-control" id="viewSuburb" readonly>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">City</label>
                                <input type="text" class="form-control" id="viewCity" readonly>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Post Code</label>
                                <input type="text" class="form-control" id="viewPostCode" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: COMPLIANCE & STATUS -->
                    <div class="modal-section-card mb-0">
                        <div class="modal-section-title">
                            <i class="feather-clipboard text-primary fs-15"></i> 4. Compliance & Processing Status
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Adviser</label>
                                <input type="text" class="form-control" id="viewAdviser" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Not Counting</label>
                                <input type="text" class="form-control" id="viewNotCounting" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Compliance by</label>
                                <input type="text" class="form-control" id="viewComplianceBy" readonly>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label>
                                <input type="text" class="form-control text-danger fw-semibold" id="viewRoaDueDate" readonly>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label>
                                <input type="text" class="form-control text-primary fw-semibold" id="viewStatusCompliance" readonly>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label>
                                <input type="text" class="form-control text-success fw-semibold" id="viewSentToClient" readonly>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label>
                                <input type="text" class="form-control" id="viewOutcome" readonly>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="editCurrentViewedLoginClient()"><i class="feather-edit me-1"></i> Edit Client</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Import Clients -->
    <div class="modal fade" id="importLoginClientsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen" id="importModalDialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-upload me-2"></i> Import Login Clients</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importForm" action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault(); handleImportStepUpload();">
                    @csrf
                    <input type="hidden" name="destination" value="login_client" id="importDestination">
                    <input type="hidden" id="tempFilePath" value="">
                    <input type="hidden" name="sheet_index" id="importSheetIndex" value="0">
                    
                    <div class="modal-body p-4" id="importStep1">
                        <div class="mb-4">
                            <label class="form-label fw-semibold fs-13 text-dark mb-2">Upload Excel or CSV File <span class="text-danger">*</span></label>
                            <div class="drag-drop-box" onclick="document.getElementById('importFileInput').click();"
                                ondragover="event.preventDefault(); event.stopPropagation(); this.classList.add('drag-over');"
                                ondragleave="event.preventDefault(); event.stopPropagation(); this.classList.remove('drag-over');"
                                ondrop="event.preventDefault(); event.stopPropagation(); this.classList.remove('drag-over'); const fileInput = document.getElementById('importFileInput'); if (event.dataTransfer.files.length) { fileInput.files = event.dataTransfer.files; fileInput.dispatchEvent(new Event('change', { bubbles: true })); }">
                                <i class="feather-file-text fs-1 text-primary mb-2"></i>
                                <h6 class="fw-bold text-dark mb-1">Click to browse or drag & drop file here</h6>
                                <p class="text-muted fs-12 mb-0">Supports .CSV, .XLS, or .XLSX spreadsheets</p>
                                <input type="file" id="importFileInput" name="import_file" accept=".csv, .xls, .xlsx" style="display: none;" onchange="if(this.files[0]) { $('#fileNameDisplay').text('Selected: ' + this.files[0].name).removeClass('text-danger'); document.getElementById('importClientsUploadButton').disabled = false; }">
                            </div>
                            <div id="fileNameDisplay" class="fs-12 fw-bold text-primary mt-2"></div>
                            <div id="importSheetSelector" class="mt-3 d-none">
                                <label for="importSheetSelect" class="form-label fw-semibold fs-13 text-dark">Worksheet</label>
                                <select id="importSheetSelect" class="form-select form-select-sm"></select>
                            </div>
                        </div>
                        
                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold fs-13 text-dark"><i class="feather-info me-1 text-info"></i> Column Mapping Support</span>
                                <a href="#" onclick="downloadLoginClientsTemplate(event);" class="fs-12 text-primary text-decoration-none fw-semibold"><i class="feather-download me-1"></i> Download CSV Template</a>
                            </div>
                            <p class="fs-12 text-muted mb-0">You can upload a file with any column names. In the next step, you will be able to map your columns to the database fields.</p>
                        </div>
                    </div>
                    
                    <div class="modal-body p-4 d-none" id="importStep2">
                        <h6 class="fw-bold text-dark mb-3">Map Columns <span id="selectedSheetName" class="text-muted fs-13 fw-normal"></span></h6>
                        <p class="text-muted fs-13 mb-3">Please map the columns from your uploaded file to the corresponding database fields. Required fields are marked with an asterisk (*).</p>
                        
                        <div class="table-responsive mapping-scroll-region" role="region" aria-label="Column mapping" tabindex="0" style="height: min(55vh, 400px); max-width: 100%; overflow: auto; overscroll-behavior: contain;">
                            <table class="table table-bordered table-sm align-middle fs-13 mapping-table" style="min-width: 640px; table-layout: fixed;">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th style="width: 50%;">Your File Column</th>
                                        <th style="width: 50%;">Map to Field</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-body p-4 d-none" id="importStep3">
                        <h6 class="fw-bold text-dark mb-3">Data Preview</h6>
                        <p class="text-muted fs-13 mb-3">Please review the first few rows of your mapped data before processing.</p>
                        
                        <div class="table-responsive mapping-scroll-region" style="max-height: 400px !important; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle fs-12 text-nowrap preview-table">
                                <thead class="bg-light sticky-top">
                                    <!-- Populated by JS -->
                                </thead>
                                <tbody>
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2" id="importPreviewPagination">
                            <span class="fs-12 text-muted" id="importPreviewPageSummary"></span>
                            <div class="btn-group btn-group-sm" role="group" aria-label="Preview pages">
                                <button type="button" class="btn btn-outline-secondary" id="importPreviewPreviousButton" onclick="handleImportStepPreview(Math.max(0, currentPreviewOffset - currentPreviewLimit))" disabled>Previous</button>
                                <button type="button" class="btn btn-outline-secondary" id="importPreviewNextButton" onclick="handleImportStepPreview(currentPreviewOffset + currentPreviewLimit)" disabled>Next</button>
                            </div>
                        </div>
                        
                        <div class="mt-3 d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skipErrorsCheck" checked>
                                <label class="form-check-label fs-13 text-dark" for="skipErrorsCheck">
                                    Skip rows with errors and import valid data
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light d-flex justify-content-between" id="importFooterStep1">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold" id="importClientsUploadButton" disabled>Continue to Mapping <i class="feather-arrow-right ms-1"></i></button>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between d-none" id="importFooterStep2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showImportStep(1)"><i class="feather-arrow-left me-1"></i> Back</button>
                        <button type="button" class="btn btn-primary btn-sm fw-bold" id="importClientsPreviewButton" onclick="handleImportStepPreview()">Generate Preview <i class="feather-arrow-right ms-1"></i></button>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between d-none" id="importFooterStep3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showImportStep(2)"><i class="feather-arrow-left me-1"></i> Back</button>
                        <button type="button" class="btn btn-success btn-sm fw-bold" id="importClientsProcessButton" onclick="handleImportStepProcess()"><i class="feather-check-circle me-1"></i> Confirm & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Import Results Summary -->
    <div class="modal fade" id="importResultsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-success);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-circle me-2"></i> Import Completed</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="importResultsSummary" class="mb-4"></div>
                    
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add New Login Client -->
    <div class="modal fade" id="addLoginClientModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-xl" id="addClientModalDialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-plus me-2"></i> <span id="loginModalTitle">Add New Login Client Entry</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addLoginClientForm" method="POST" action="{{ route('clients.login.store') }}" data-create-action="{{ route('clients.login.store') }}">
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        @csrf
                        <input type="hidden" name="_method" id="loginFormMethod" value="POST">
                        <input type="hidden" name="client_id" id="loginClientId" value="">
                        <!-- SECTION 1: POLICY & PROVIDER INFO -->
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Policy No.</label>
                                    <input type="text" class="form-control" id="loginPolicyNoInput" name="policy_no" placeholder="e.g. POL-2026-9912" maxlength="30">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                    <select class="form-select" id="loginCompanyInput" name="company">
                                        <option value="" selected disabled>Select Company</option>
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Login Date</label>
                                    <input type="date" class="form-control" id="loginDateInput" name="login_date">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label>
                                    <input type="number" step="0.01" class="form-control" id="loginAnpInput" name="anp" placeholder="2500.00">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: CLIENT PERSONAL & CONTACT -->
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 2. Client Contact Information
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="loginFirstNameInput" name="first_name" placeholder="e.g. Rahul" maxlength="50" required>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="loginLastNameInput" name="last_name" placeholder="e.g. Sharma" maxlength="50" required>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                    <input type="date" class="form-control" id="loginDobInput" name="dob">
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="loginMobileInput" name="phone" placeholder="021 XXX XXXX" maxlength="15" required>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                                    <input type="email" class="form-control" id="loginEmailInput" name="email" placeholder="client@example.com" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: RESIDENTIAL ADDRESS -->
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-map-pin text-primary fs-15"></i> 3. Address Details
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Street Address</label>
                                    <input type="text" class="form-control" id="loginAddressInput" name="address" placeholder="e.g. 42 Queen Street" maxlength="150">
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">Suburb</label>
                                    <input type="text" class="form-control" id="loginSuburbInput" name="suburb" placeholder="e.g. Central" maxlength="50">
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">City</label>
                                    <input type="text" class="form-control" id="loginCityInput" name="city" placeholder="e.g. Auckland" maxlength="50">
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <label class="form-label fw-semibold fs-13 text-dark">Post Code</label>
                                    <input type="text" class="form-control" id="loginPostCodeInput" name="post_code" placeholder="1010" maxlength="10">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: COMPLIANCE & STATUS -->
                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-clipboard text-primary fs-15"></i> 4. Compliance & Processing Status
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="loginAdviserInput" name="adviser">
                                        <option value="" disabled {{ in_array(auth()->user()->name, $advisers->toArray()) ? '' : 'selected' }}>Select Advisor</option>
                                        @foreach($advisers as $advisorName)
                                             <option value="{{ $advisorName }}" {{ auth()->user()->name === $advisorName ? 'selected' : '' }}>{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Not Counting</label>
                                    <select class="form-select" id="loginNotCountingSelect" name="not_counting">
                                        <option value="" disabled selected>Select</option>
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Compliance by</label>
                                    <input type="text" class="form-control" id="loginComplianceByInput" name="compliance_by" placeholder="Officer Name" maxlength="50">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label>
                                    <input type="date" class="form-control" id="loginRoaDueDateInput" name="roa_due_date">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label>
                                    <select class="form-select" id="loginStatusComplianceSelect" name="status_compliance">
                                        <option value="" disabled selected>Select Status</option>
                                        <option value="Sent to Compliance">Sent to Compliance</option>
                                        <option value="In Review">In Review</option>
                                        <option value="Approved">Approved</option>
                                        
                                    </select>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label>
                                    <select class="form-select" id="loginSentToClientSelect" name="sent_to_client">
                                        <option value="" disabled selected>Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label>
                                    <input type="text" class="form-control" id="loginOutcomeInput" name="outcome" placeholder="e.g. Pending Medical Test" maxlength="255">
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Login Client Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    
        </div>
    </div>
@push('scripts')
    <script src="{{ asset('assets/js/pages/clients-login.js') }}?v={{ filemtime(public_path('assets/js/pages/clients-login.js')) }}"></script>
@endpush

@push('styles')
    <style>
        .drag-drop-box {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 22px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .drag-drop-box.drag-over {
            border-color: #00a8b5;
            background-color: rgba(0, 168, 181, 0.08);
        }
    </style>
@endpush

@endsection




