@extends('layouts.app')
@section('title', 'Inactive Clients')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Inactive Clients Directory <span id="inactiveClientCount"></span></h4>
                    <p class="text-muted fs-13 mb-0">Clients whose policies have lapsed or are currently inactive.</p>
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
                    <table class="table table-hover align-middle w-100" id="inactiveClientsTable" data-server-paginated data-can-write="{{ auth()->user()->canWrite('clients_inactive') ? '1' : '0' }}">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Client Name</th>
                                <th>Phone Number</th>
                                <th>Date of Birth</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Policy Number</th>
                                <th>Last Contact</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr data-source="Existing Client" data-status="Inactive">
                                <td class="fw-bold text-dark fs-13" data-order="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</td>
                                <td class="fs-13 text-muted">{{ $client->phone }}</td>
                                <td class="fs-13 text-muted">{{ $client->dob?->format('d/m/Y') ?? 'N/A' }}</td>
                                <td class="fs-13 text-muted">{{ $client->city ?: 'N/A' }}{{ $client->city && $client->zip_code ? ', ' : '' }}{{ $client->zip_code }}</td>
                                <td><span class="status-pill-inactive">Inactive</span></td>
                                <td class="fs-13 text-muted">Login Client</td>
                                <td class="fs-13 fw-semibold text-dark text-center">{{ $client->policy_no ?: ($client->policies->pluck('policy_number')->filter()->join(', ') ?: '-') }}</td>
                                <td class="fs-13 text-muted">{{ $client->updated_at?->format('d M Y') ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="action-kebab-wrapper"
                                        data-client-id="{{ $client->id }}"
                                        data-client-name="{{ $client->first_name }} {{ $client->last_name }}"
                                        data-policy_no="{{ $client->policy_no ?: ($client->policies->pluck('policy_number')->filter()->join(', ') ?: '-') }}"
                                        data-company="{{ $client->company }}"
                                        data-first_name="{{ $client->first_name }}"
                                        data-last_name="{{ $client->last_name }}"
                                        data-dob="{{ $client->dob?->format('d/m/Y') }}"
                                        data-phone="{{ $client->phone }}"
                                        data-email="{{ $client->email }}"
                                        data-address="{{ $client->address }}"
                                        data-suburb="{{ $client->suburb }}"
                                        data-city="{{ $client->city }}"
                                        data-post_code="{{ $client->post_code }}"
                                        data-login_date="{{ $client->login_date?->format('d/m/Y') }}"
                                        data-anp="{{ $client->anp }}"
                                        data-adviser="{{ $client->adviser }}"
                                        data-not_counting="{{ $client->not_counting ? 'Yes' : 'No' }}"
                                        data-compliance_by="{{ $client->compliance_by }}"
                                        data-roa_due_date="{{ $client->roa_due_date?->format('d/m/Y') }}"
                                        data-status_compliance="{{ $client->status_compliance }}"
                                        data-sent_to_client="{{ $client->sent_to_client }}"
                                        data-outcome="{{ $client->outcome }}"
                                        data-claim-type="{{ $client->claim_type }}"
                                        data-claim-admin="{{ $client->claim_admin ?: $client->adviser }}"
                                        data-adviser="{{ $client->adviser }}"
                                        data-claim-processed-date="{{ $client->claim_processed_date?->format('Y-m-d') }}"
                                        data-claim-update-status="{{ $client->claim_update_status }}"
                                        data-claim-approved-date="{{ $client->claim_approved_date?->format('Y-m-d') }}"
                                        data-claim-result="{{ $client->claim_result }}">
                                        <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                        <div class="action-kebab-dropdown">
                                            <a href="javascript:void(0);" class="action-kebab-item" onclick="viewClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                                            @if(auth()->user()->canWrite('clients_inactive'))
                                                <a href="javascript:void(0);" class="action-kebab-item" onclick="openInactiveClientInforceModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', {{ $client->id }})"><i class="feather-git-pull-request text-warning me-1"></i> Inforce Clients</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No inactive clients found.</td></tr>
                            @endforelse
</tbody>
                    </table>
                </div>
            

</div>
    </div>

    <!-- Modal: Add New Client -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-plus me-2"></i> Add New Client
                        Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="addClientForm" onsubmit="event.preventDefault(); handleAddNewClient();">
                    <div class="modal-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Client Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientNameInput"
                                    placeholder="e.g. Ramesh Patel" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientPhoneInput" placeholder="021 XXX XXXX"
                                    required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                <input type="date" class="form-control" id="clientDobInput">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Residential Address</label>
                                <input type="text" class="form-control" id="clientAddressInput"
                                    placeholder="e.g. Auckland, NZ">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Policy Status</label>
                                <select class="form-select" id="clientStatusSelect">
                                    <option value="Inactive">Inactive</option>
                                    <option value="Inforce">Inforce</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Lead Source</label>
                                <select class="form-select" id="clientSourceSelect">
                                    <option value="Referral">Referral</option>
                                    <option value="Existing Client">Existing Client</option>
                                    <option value="Meta Ads">Meta Ads</option>
                                    <option value="Website">Website Inbound</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Previous Policies</label>
                                <input type="number" class="form-control" id="clientPoliciesInput" placeholder="1"
                                    value="1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Client Profile</button>
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
                <form id="clientRequestForm" method="POST" action="" data-action-base="{{ url('/clients/inactive') }}">
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
                <form id="lodgeClaimForm" method="POST" action="" data-action-base="{{ url('/clients/inactive') }}">
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
                <form id="addInactiveForm" method="POST" action="" data-action-base="{{ url('/clients/inactive') }}">
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
                <form id="addNpwForm" method="POST" action="" data-action-base="{{ url('/clients/inactive') }}" onsubmit="return true;">
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
    <div class="modal fade" id="alreadyInactiveClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-circle me-2"></i> Inactive Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="text-success mb-3" style="font-size: 2.5rem;"><i class="feather-check-circle"></i></div>
                    <h5 class="fw-bold text-dark mb-2" id="alreadyInactiveClientName"></h5>
                    <p class="text-muted mb-2" id="alreadyInactiveClientCompany"></p>
                    <span class="badge bg-soft-success text-success fs-12 px-3 py-2">Already Inactive Client</span>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="inactiveClientProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user me-2"></i> Client Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 fs-13">
                        <div class="col-12"><h5 class="fw-bold text-dark mb-0" id="inactiveProfileName"></h5><span class="badge bg-soft-warning text-warning mt-1">Inactive Client</span></div>
                        <div class="col-6"><strong>Phone:</strong><br><span id="inactiveProfilePhone"></span></div>
                        <div class="col-6"><strong>Address:</strong><br><span id="inactiveProfileAddress"></span></div>
                        <div class="col-12"><strong>Policy Number:</strong><br><span id="inactiveProfilePolicy"></span></div>
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
    <script src="{{ asset('assets/js/pages/clients-inactive.js') }}?v={{ filemtime(public_path('assets/js/pages/clients-inactive.js')) }}"></script>
@endpush

@endsection


