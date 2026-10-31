@extends('layouts.app')
@section('title', 'Cancellation Update Directory')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Cancellation Update Directory <span id="cancellationClientCount"></span></h4>
                    <p class="text-muted fs-13 mb-0">Track policy cancellation notices, retention attempts, and insurer
                        completions.</p>
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
                    <table class="table table-hover align-middle w-100" id="cancellationTable" data-server-paginated data-can-write="{{ auth()->user()->canWrite('clients_cancellation') ? '1' : '0' }}">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Client Name</th>
                                <th>Company</th>
                                <th>Cancellation Sent</th>
                                <th>Completed</th>
                                <th>Outcome</th>
                                <th>Admin</th>
                                <th>Comments</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr>
                                <td class="fw-bold text-dark fs-13" data-order="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</td>
                                <td class="fs-13 fw-semibold text-dark">{{ $client->company }}</td>
                                <td class="fs-13 text-muted">{{ $client->canc_date_sent ? $client->canc_date_sent->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    @if($client->canc_completed_date)
                                        <span class="badge bg-soft-success text-success fs-11">{{ $client->canc_completed_date?->format('d/m/Y') ?? 'N/A' }}</span>
                                    @else
                                        <span class="badge bg-soft-secondary text-secondary fs-11">Pending</span>
                                    @endif
                                </td>
                                <td class="fs-13 text-muted">{{ $client->canc_outcome ?? 'N/A' }}</td>
                                <td class="fs-13 text-muted">{{ $client->canc_admin ?? 'N/A' }}</td>
                                <td class="fs-13 text-muted">{{ Str::limit($client->canc_comments ?? 'No comments.', 50) }}</td>
                                <td class="text-center">
                                    <div class="action-kebab-wrapper"
                                        data-client-id="{{ $client->id }}"
                                        data-client-name="{{ $client->first_name }} {{ $client->last_name }}"
                                        data-company="{{ $client->company }}"
                                        data-policy-no="{{ $client->policy_no ?: ($client->policies->pluck('policy_number')->filter()->join(', ') ?: '-') }}"
                                        data-first-name="{{ $client->first_name }}"
                                        data-last-name="{{ $client->last_name }}"
                                        data-dob="{{ $client->dob?->format('d/m/Y') }}"
                                        data-phone="{{ $client->phone }}"
                                        data-email="{{ $client->email }}"
                                        data-address="{{ $client->address }}"
                                        data-suburb="{{ $client->suburb }}"
                                        data-city="{{ $client->city }}"
                                        data-post-code="{{ $client->post_code }}"
                                        data-login-date="{{ $client->login_date?->format('d/m/Y') }}"
                                        data-anp="{{ $client->anp }}"
                                        data-adviser="{{ $client->adviser }}"
                                        data-not-counting="{{ $client->not_counting ? 'Yes' : 'No' }}"
                                        data-compliance-by="{{ $client->compliance_by }}"
                                        data-roa-due-date="{{ $client->roa_due_date?->format('d/m/Y') }}"
                                        data-status-compliance="{{ $client->status_compliance }}"
                                        data-sent-to-client="{{ $client->sent_to_client }}"
                                        data-outcome="{{ $client->outcome }}"
                                        data-claim-type="{{ $client->claim_type }}"
                                        data-claim-admin="{{ $client->claim_admin ?: $client->adviser }}"
                                        data-canc-admin="{{ $client->canc_admin ?: $client->adviser }}"
                                        data-npw-admin="{{ $client->npw_admin ?: $client->adviser }}"
                                        data-adviser="{{ $client->adviser }}"
                                        data-claim-processed-date="{{ $client->claim_processed_date?->format('Y-m-d') }}"
                                        data-claim-update-status="{{ $client->claim_update_status }}"
                                        data-claim-approved-date="{{ $client->claim_approved_date?->format('Y-m-d') }}"
                                        data-claim-result="{{ $client->claim_result }}">
                                        <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                        <div class="action-kebab-dropdown">
                                            <a href="javascript:void(0);" class="action-kebab-item" onclick="viewClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                                            @if(auth()->user()->canWrite('clients_cancellation'))
                                                <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationClientInforceModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', {{ $client->id }})"><i class="feather-git-pull-request text-warning me-1"></i> Inforce Clients</a>
                                                <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModalFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-shield text-info me-1"></i> Claim Update</a>
                                                <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationClientInactiveModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ $client->id }}')"><i class="feather-user-x text-secondary me-1"></i> Inactive Clients</a>
                                                <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwDeferredModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', '{{ $client->id }}', '{{ addslashes($client->npw_admin ?: ($client->adviser ?? '')) }}')"><i class="feather-clock text-purple me-1"></i> NPW Deferred</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No cancellation records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            

<!-- Modal: View Cancellation Details (Read-only Form Layout) -->
    <div class="modal fade" id="viewCancellationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-file-text me-2"></i> Cancellation Record
                        Details - <span id="viewCancClientHeader"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">

                    <!-- SECTION 1: CLIENT & PROVIDER INFO -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-user text-primary fs-15"></i> 1. Client & Insurance Company
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Client Name</label>
                                <input type="text" class="form-control" id="viewCancClientName" readonly>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Company</label>
                                <input type="text" class="form-control" id="viewCancCompany" readonly>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                <input type="text" class="form-control" id="viewCancAdmin" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: CANCELLATION TIMELINE & OUTCOME -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-clock text-primary fs-15"></i> 2. Cancellation Timeline & Outcome
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Cancellation Sent</label>
                                <input type="text" class="form-control" id="viewCancDateSent" readonly>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Completed Date / Status</label>
                                <input type="text" class="form-control text-primary fw-semibold"
                                    id="viewCancCompletedDate" readonly>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Outcome</label>
                                <input type="text" class="form-control text-danger fw-semibold" id="viewCancOutcome"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: COMMENTS & NOTES -->
                    <div class="modal-section-card mb-0">
                        <div class="modal-section-title">
                            <i class="feather-message-square text-primary fs-15"></i> 3. Comments & Internal Notes
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Comments</label>
                                <textarea class="form-control" id="viewCancComments" rows="3" readonly></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold"
                        onclick="$('#viewCancellationModal').modal('hide'); editCancellationDetails($('#viewCancClientName').val(), $('#viewCancCompany').val(), $('#viewCancDateSent').val(), $('#viewCancCompletedDate').val(), $('#viewCancOutcome').val(), $('#viewCancAdmin').val(), $('#viewCancComments').val());"><i
                            class="feather-edit me-1"></i> Edit Record</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Log / Edit Cancellation -->
    <div class="modal fade" id="addCancellationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-file-minus me-2"></i> <span
                            id="cancModalTitle">Log Policy Cancellation</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addCancellationForm" onsubmit="event.preventDefault(); handleAddNewCancellation();">
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">

                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Insurance Provider
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cancClientSelect" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Company</label>
                                    <select class="form-select" id="cancCompanySelect" name="company">
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
                                    <input type="date" class="form-control" id="cancDateSentInput">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Completed</label>
                                    <input type="text" class="form-control" id="cancCompletedInput"
                                        placeholder="e.g. 15/08/2026 or Pending">
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="cancAdminSelect">
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Outcome</label>
                                    <input type="text" class="form-control" id="cancOutcomeInput"
                                        placeholder="e.g. Cancelled - Premium Cost Concerns">
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
                                    <textarea class="form-control" id="cancCommentsInput" rows="3"
                                        placeholder="Enter comments or cancellation reason..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Cancellation
                            Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    
        </div>
    </div>

    
    
    
    
    
    
    
    
    
    
    

    <!-- Modal: Client Request Popup -->
    <div class="modal fade" id="clientRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-git-pull-request me-2"></i> Client Service Request - <span id="reqClientNameHeader"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="clientRequestForm" method="POST" action="" data-action-base="{{ url('/clients/cancellation') }}">
                    @csrf
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-calendar text-primary fs-15"></i> 1. Request Overview
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="reqDateInput" name="inforce_date" required>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reqClientNameInput" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                    <select class="form-select" id="reqCompanyInput" name="company">
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
                                <i class="feather-layers text-primary fs-15"></i> 2. Service Request & Processing
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Request Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="reqTypeSelect" name="inforce_request_type" required>
                                        <option value="" disabled selected>Select Request Type</option>
                                        <option value="LOA">LOA</option>
                                        <option value="Address update">Address update</option>
                                        <option value="Put the premium on hold">Put the premium on hold</option>
                                        <option value="LOA / Change of Adviser">LOA / Change of Adviser</option>
                                        <option value="Name Correction">Name Correction</option>
                                        <option value="Update Payment Details - DD">Update Payment Details - DD</option>
                                        <option value="MOT">MOT</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Process Status</label>
                                    <select class="form-select" id="reqProcessSelect" name="inforce_process_status">
                                        <option value="" disabled selected>Select Process Status</option>
                                        <option value="Login">Login</option>
                                        <option value="In Processing">In Processing</option>
                                        <option value="Pending Information">Pending Information</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Process by</label>
                                    <select class="form-select" id="reqProcessBySelect" name="inforce_process_by">
                                        <option value="" disabled selected>Select User</option>
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-check-circle text-primary fs-15"></i> 3. Outcome & Completion Details
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Results / Outcome</label>
                                    <textarea class="form-control" id="reqOutcomeInput" name="inforce_outcome" rows="2" placeholder="e.g. Address updated with AIA portal successfully." maxlength="255"></textarea>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Comments</label>
                                    <textarea class="form-control" id="reqCommentsInput" name="inforce_comments" rows="2" placeholder="Internal notes or adviser instructions..." maxlength="1000"></textarea>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Finished Day (Date)</label>
                                    <input type="date" class="form-control" id="reqFinishedDateInput" name="inforce_finished_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold"><i class="feather-save me-1"></i> Save Client Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Claim Update Popup -->
    <div class="modal fade" id="lodgeClaimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-shield me-2"></i> <span id="claimModalTitle">New Claim Update</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="lodgeClaimForm" method="POST" action="" data-action-base="{{ url('/clients/cancellation') }}">
                    @csrf
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Provider Info
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="claimClientSelect" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                    <select class="form-select" id="claimCompanySelect" name="company">
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

    <!-- Modal: Cancellation Update Popup -->
    <div class="modal fade" id="addCancellationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-file-minus me-2"></i> <span id="cancModalTitle">New Cancellation Update</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addCancellationForm" onsubmit="event.preventDefault(); handleAddNewCancellation();">
                    <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                        <div class="modal-section-card">
                            <div class="modal-section-title">
                                <i class="feather-user text-primary fs-15"></i> 1. Client & Insurance Provider
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Client Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="cancClientSelect" name="client_name" placeholder="Client Name" required>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Company</label>
                                    <select class="form-select" id="cancCompanySelect" name="company">
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
                                    <input type="date" class="form-control" id="cancDateSentInput">
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Completed</label>
                                    <input type="text" class="form-control" id="cancCompletedInput" placeholder="e.g. 15/08/2026 or Pending">
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="cancAdminSelect">
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Outcome</label>
                                    <input type="text" class="form-control" id="cancOutcomeInput" placeholder="e.g. Cancelled - Premium Cost Concerns">
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
                                    <textarea class="form-control" id="cancCommentsInput" rows="3" placeholder="Enter comments or cancellation reason..."></textarea>
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


    <!-- Modal: Mark as Inactive -->
    <div class="modal fade" id="addInactiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #0A192F;">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-x me-2"></i> Mark Client as Inactive</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addInactiveForm" method="POST" action="" data-action-base="{{ url('/clients/cancellation') }}">
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
                <form id="addNpwForm" method="POST" action="" data-action-base="{{ url('/clients/cancellation') }}" onsubmit="return true;">
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
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Expected Issue Date <span class="text-danger"></span></label>
                                    <input type="date" class="form-control" id="npwIssueDateInput" name="npw_issue_date">
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <label class="form-label fw-semibold fs-13 text-dark">Expected Premium ($) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="npwPremiumInput" name="npw_premium" placeholder="e.g. 1500.00" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label>
                                    <select class="form-select" id="npwAdminSelect">
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section-card mb-0">
                            <div class="modal-section-title">
                                <i class="feather-message-square text-primary fs-15"></i> 3. Internal Notes & Next Steps
                            </div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold fs-13 text-dark">Comments / Reason for Deferral</label>
                                    <textarea class="form-control" id="npwNotesCommentsInput" rows="3" placeholder="Add any specific context here..."></textarea>
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
    <div class="modal fade" id="alreadyCancellationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-circle me-2"></i> Cancellation Update</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="status">
                        <i class="feather-check-circle"></i>
                        <span>This client already has a cancellation update.</span>
                    </div>
                    <div class="row g-3 fs-13">
                        <div class="col-6"><strong>Client:</strong><br><span id="alreadyCancellationClient"></span></div>
                        <div class="col-6"><strong>Company:</strong><br><span id="alreadyCancellationCompany"></span></div>
                        <div class="col-6"><strong>Date Sent:</strong><br><span id="alreadyCancellationDateSent"></span></div>
                        <div class="col-6"><strong>Completed:</strong><br><span id="alreadyCancellationCompleted"></span></div>
                        <div class="col-12"><strong>Outcome:</strong><br><span id="alreadyCancellationOutcome"></span></div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@include('pages.auth.partials.client-details-modal')
@push('scripts')
    <script src="{{ asset('assets/js/pages/clients-login.js') }}?v={{ filemtime(public_path('assets/js/pages/clients-login.js')) }}"></script>
    <script src="{{ asset('assets/js/pages/clients-cancellation.js') }}?v={{ filemtime(public_path('assets/js/pages/clients-cancellation.js')) }}"></script>
@endpush

@endsection


