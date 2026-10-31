<div class="modal fade" id="clientRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                <h5 class="modal-title text-white mb-0"><i class="feather-git-pull-request me-2"></i> Client Service Request - <span id="reqClientNameHeader"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="clientRequestForm" method="POST" action="" data-create-action-base="{{ url('/clients/login') }}" data-update-action-base="{{ url('/clients/requests') }}">
                @csrf
                <input type="hidden" name="_method" id="clientRequestMethod" value="">
                <input type="hidden" id="reqClientId">
                <input type="hidden" id="clientRequestId">
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    <div class="modal-section-card">
                        <div class="modal-section-title"><i class="feather-calendar text-primary fs-15"></i> 1. Request Overview</div>
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
                                    <option value="">Select Company</option>
                                    @foreach($insuranceCompanies as $company)
                                        <option value="{{ $company->name }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-section-card">
                        <div class="modal-section-title"><i class="feather-layers text-primary fs-15"></i> 2. Service Request &amp; Processing</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Request Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="reqTypeSelect" name="inforce_request_type" required>
                                    <option value="">Select Request Type</option>
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
                                    <option value="">Select Process Status</option>
                                    <option value="Login">Login</option>
                                    <option value="In Processing">In Processing</option>
                                    <option value="Pending Information">Pending Information</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Process by</label>
                                <select class="form-select" id="reqProcessBySelect" name="inforce_process_by">
                                    <option value="">Select User</option>
                                    @foreach($advisers as $advisorName)
                                        <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-section-card mb-0">
                        <div class="modal-section-title"><i class="feather-check-circle text-primary fs-15"></i> 3. Outcome &amp; Completion Details</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Results / Outcome</label>
                                <textarea class="form-control" id="reqOutcomeInput" name="inforce_outcome" rows="2" placeholder="e.g. Address updated with AIA portal successfully." maxlength="500"></textarea>
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
                    <button type="button" class="btn btn-success btn-sm px-4 fw-bold d-none" id="clientRequestMoveButton" onclick="moveCurrentClientRequestToInforce()"><i class="feather-git-pull-request me-1"></i> Move to Inforce</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" id="clientRequestSaveButton"><i class="feather-save me-1"></i> Save Client Request</button>
                </div>
            </form>
        </div>
    </div>
</div>