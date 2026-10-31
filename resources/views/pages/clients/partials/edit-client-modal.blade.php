@once
<!-- Modal: Edit Client Profile -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                <h5 class="modal-title text-white mb-0"><i class="feather-edit me-2"></i> Edit Client Profile - <span id="editClientNameHeader"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClientForm" method="POST" action="" onsubmit="handleEditClientSubmit(event)">
                @csrf
                <input type="hidden" name="_method" value="PATCH">
                <input type="hidden" name="client_id" id="editClientId" value="">

                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    <div id="editClientAlertError" class="alert alert-danger d-none mb-3"></div>

                    <!-- SECTION 1: POLICY & INSURANCE PROVIDER -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Policy No.</label>
                                <input type="text" class="form-control" name="policy_no" id="editClientPolicyNo" placeholder="e.g. POL-2026-9912" maxlength="50">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                <select class="form-select" name="company" id="editClientCompany">
                                    <option value="">Select Company</option>
                                    @if(isset($insuranceCompanies))
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Login Date</label>
                                <input type="date" class="form-control" name="login_date" id="editClientLoginDate">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label>
                                <input type="number" step="0.01" class="form-control" name="anp" id="editClientAnp" placeholder="2500">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Client Status</label>
                                <select class="form-select" name="status" id="editClientStatus">
                                    <option value="Inforce">Inforce</option>
                                    <option value="Login Client">Login Client</option>
                                    <option value="NPW Deferred">NPW Deferred</option>
                                    <option value="Cancellation">Cancellation</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Claims">Claims</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Lead Source</label>
                                <select class="form-select" name="lead_source_id" id="editClientLeadSource">
                                    <option value="">Select Lead Source</option>
                                    @if(isset($leadSources))
                                        @foreach($leadSources as $source)
                                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
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
                                <input type="text" name="first_name" id="editClientFirstName" class="form-control" required placeholder="e.g. Rahul" maxlength="100">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="editClientLastName" class="form-control" required placeholder="e.g. Sharma" maxlength="100">
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                <input type="date" name="dob" id="editClientDob" class="form-control">
                            </div>
                            <div class="col-md-6 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="editClientMobile" class="form-control" placeholder="021 XXX XXXX" required maxlength="30">
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                                <input type="email" name="email" id="editClientEmail" class="form-control" placeholder="client@example.com" maxlength="150">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: ADDRESS DETAILS -->
                    <div class="modal-section-card">
                        <div class="modal-section-title">
                            <i class="feather-map-pin text-primary fs-15"></i> 3. Address Details
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Street Address</label>
                                <input type="text" name="address" id="editClientAddress" class="form-control" placeholder="e.g. 42 Queen Street" maxlength="200">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Suburb</label>
                                <input type="text" name="suburb" id="editClientSuburb" class="form-control" placeholder="e.g. Central" maxlength="100">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">City</label>
                                <input type="text" name="city" id="editClientCity" class="form-control" placeholder="e.g. Auckland" maxlength="100">
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Post Code</label>
                                <input type="text" name="post_code" id="editClientPostCode" class="form-control" placeholder="1010" maxlength="20">
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
                                <select class="form-select" name="adviser" id="editClientAdviser">
                                    <option value="">Select Advisor</option>
                                    @if(isset($advisers))
                                        @foreach($advisers as $advisorName)
                                            <option value="{{ $advisorName }}">{{ $advisorName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Not Counting</label>
                                <select class="form-select" name="not_counting" id="editClientNotCounting">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Compliance by</label>
                                <input type="text" class="form-control" name="compliance_by" id="editClientComplianceBy" placeholder="Officer Name" maxlength="100">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label>
                                <input type="date" class="form-control" name="roa_due_date" id="editClientRoaDueDate">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label>
                                <select class="form-select" name="status_compliance" id="editClientStatusCompliance">
                                    <option value="">Select Status</option>
                                    <option value="Sent to Compliance">Sent to Compliance</option>
                                    <option value="In Review">In Review</option>
                                    <option value="Approved">Approved</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label>
                                <select class="form-select" name="sent_to_client" id="editClientSentToClient">
                                    <option value="Pending">Pending</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label>
                                <input type="text" class="form-control" name="outcome" id="editClientOutcome" placeholder="e.g. Pending Medical Test" maxlength="300">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" id="btnSaveClientEdit">
                        <i class="feather-save me-1"></i> Update Client Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function() {
    if (typeof window.formatDateToInput !== 'function') {
        window.formatDateToInput = function (dateStr) {
            if (!dateStr || dateStr === '-') return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
            const parts = dateStr.split('/');
            if (parts.length === 3) {
                return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
            }
            return '';
        };
    }

    if (typeof window.openEditClientModalFromRow !== 'function') {
        window.openEditClientModalFromRow = function (wrapper) {
            if (!wrapper) return;
            const clientId = wrapper.getAttribute('data-client-id') || wrapper.dataset?.clientId;
            window.openEditClientModal(clientId, wrapper);
        };
    }

    window.openEditClientModal = async function (clientId, wrapper = null) {
        if (!clientId) return;

        if (wrapper) {
            const d = wrapper.dataset;
            $('#editClientId').val(clientId);
            $('#editClientNameHeader').text(d.clientName || 'Client');
            $('#editClientFirstName').val(d.firstName || '');
            $('#editClientLastName').val(d.lastName || '');
            $('#editClientMobile').val(d.phone && d.phone !== '-' ? d.phone : '');
            $('#editClientEmail').val(d.email && d.email !== '-' ? d.email : '');
            $('#editClientDob').val(d.dob || '');
            $('#editClientAddress').val(d.address || '');
            $('#editClientSuburb').val(d.suburb || '');
            $('#editClientCity').val(d.city || '');
            $('#editClientPostCode').val(d.postCode || '');
            if (d.company && d.company !== '-') $('#editClientCompany').val(d.company);
            if (d.status && d.status !== '-') $('#editClientStatus').val(d.status);
            if (d.adviser && d.adviser !== '-') $('#editClientAdviser').val(d.adviser);
        }

        $('#editClientAlertError').addClass('d-none').empty();
        $('#editClientForm').attr('action', `/clients/${clientId}`);

        const modalEl = document.getElementById('editClientModal');
        if (modalEl) {
            document.body.appendChild(modalEl);
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }

        try {
            const response = await fetch(`/clients/${clientId}/profile`, {
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const client = await response.json();
                $('#editClientId').val(client.id);
                const fullName = `${client.first_name || ''} ${client.last_name || ''}`.trim() || client.company || `Client #${client.id}`;
                $('#editClientNameHeader').text(fullName);

                $('#editClientPolicyNo').val(client.policy_no && client.policy_no !== '-' ? client.policy_no : '');
                if (client.company && client.company !== '-') {
                    if ($('#editClientCompany option[value="' + client.company + '"]').length > 0) {
                        $('#editClientCompany').val(client.company);
                    } else {
                        $('#editClientCompany').append(new Option(client.company, client.company, true, true)).val(client.company);
                    }
                } else {
                    $('#editClientCompany').val('');
                }

                $('#editClientLoginDate').val(client.login_date_raw || (client.login_date ? window.formatDateToInput(client.login_date) : ''));
                $('#editClientAnp').val(client.anp || '');
                if (client.status) $('#editClientStatus').val(client.status);
                if (client.lead_source_id) $('#editClientLeadSource').val(client.lead_source_id);

                $('#editClientFirstName').val(client.first_name || '');
                $('#editClientLastName').val(client.last_name || '');
                $('#editClientDob').val(client.dob_raw || (client.dob ? window.formatDateToInput(client.dob) : ''));
                $('#editClientMobile').val(client.phone && client.phone !== '-' ? client.phone : '');
                $('#editClientEmail').val(client.email && client.email !== '-' ? client.email : '');

                $('#editClientAddress').val(client.address || '');
                $('#editClientSuburb').val(client.suburb || '');
                $('#editClientCity').val(client.city || '');
                $('#editClientPostCode').val(client.post_code || '');

                if (client.adviser && client.adviser !== '-') {
                    if ($('#editClientAdviser option[value="' + client.adviser + '"]').length > 0) {
                        $('#editClientAdviser').val(client.adviser);
                    } else {
                        $('#editClientAdviser').append(new Option(client.adviser, client.adviser, true, true)).val(client.adviser);
                    }
                } else {
                    $('#editClientAdviser').val('');
                }

                $('#editClientNotCounting').val(client.not_counting ? '1' : '0');
                $('#editClientComplianceBy').val(client.compliance_by || '');
                $('#editClientRoaDueDate').val(client.roa_due_date_raw || (client.roa_due_date ? window.formatDateToInput(client.roa_due_date) : ''));
                if (client.status_compliance) $('#editClientStatusCompliance').val(client.status_compliance);
                if (client.sent_to_client) $('#editClientSentToClient').val(client.sent_to_client);
                $('#editClientOutcome').val(client.outcome || '');
            }
        } catch (e) {
            console.error('Error fetching full client profile for edit:', e);
        }
    };

    window.handleEditClientSubmit = async function (event) {
        event.preventDefault();

        const form = document.getElementById('editClientForm');
        const clientId = $('#editClientId').val();
        if (!clientId) return;

        const btn = $('#btnSaveClientEdit');
        const origText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        $('#editClientAlertError').addClass('d-none').empty();

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        try {
            const response = await fetch(`/clients/${clientId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                let errorMsg = result.message || 'Failed to update client details.';
                if (result.errors) {
                    const errorList = Object.values(result.errors).flat().map(e => `<li>${e}</li>`).join('');
                    errorMsg = `<ul class="mb-0 ps-3">${errorList}</ul>`;
                }
                $('#editClientAlertError').html(errorMsg).removeClass('d-none');
                btn.prop('disabled', false).html(origText);
                return;
            }

            const modalEl = document.getElementById('editClientModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            btn.prop('disabled', false).html(origText);

            // Reload active data table on whichever client page we are on
            if (typeof clientsDataTable !== 'undefined' && clientsDataTable) {
                clientsDataTable.ajax.reload(null, false);
            }
            if (typeof cancellationDataTable !== 'undefined' && cancellationDataTable) {
                cancellationDataTable.ajax.reload(null, false);
            }
            if (typeof inforceDataTable !== 'undefined' && inforceDataTable) {
                inforceDataTable.ajax.reload(null, false);
            }
            if (typeof claimsDataTable !== 'undefined' && claimsDataTable) {
                claimsDataTable.ajax.reload(null, false);
            }
            if (typeof npwDeferredDataTable !== 'undefined' && npwDeferredDataTable) {
                npwDeferredDataTable.ajax.reload(null, false);
            }
            if (typeof inactiveDataTable !== 'undefined' && inactiveDataTable) {
                inactiveDataTable.ajax.reload(null, false);
            }
            if (typeof loginClientsTable !== 'undefined' && loginClientsTable) {
                loginClientsTable.ajax.reload(null, false);
            }

            if (typeof window.alert === 'function') {
                window.alert('Client profile updated successfully.');
            }
        } catch (err) {
            console.error('Error updating client:', err);
            $('#editClientAlertError').text('An unexpected network error occurred. Please try again.').removeClass('d-none');
            btn.prop('disabled', false).html(origText);
        }
    };
})();
</script>
@endonce
