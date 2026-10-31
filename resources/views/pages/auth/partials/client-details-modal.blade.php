<!-- Shared modal: Client Details -->
<div class="modal fade" id="viewLoginClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                <h5 class="modal-title text-white mb-0"><i class="feather-user-check me-2"></i> Client Profile Details - <span id="viewPolicyNoHeader"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                <div class="modal-section-card">
                    <div class="modal-section-title"><i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider</div>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Policy No.</label><input type="text" class="form-control text-primary" id="viewPolicyNo" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label><input type="text" class="form-control" id="viewCompany" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Login Date</label><input type="text" class="form-control" id="viewLoginDate" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label><input type="text" class="form-control text-success fw-bold" id="viewAnp" readonly></div>
                    </div>
                </div>
                <div class="modal-section-card">
                    <div class="modal-section-title"><i class="feather-user text-primary fs-15"></i> 2. Client Contact Information</div>
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">First Name</label><input type="text" class="form-control" id="viewFirstName" readonly></div>
                        <div class="col-md-4 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Last Name</label><input type="text" class="form-control" id="viewLastName" readonly></div>
                        <div class="col-md-4 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label><input type="text" class="form-control" id="viewDob" readonly></div>
                        <div class="col-md-6 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Mobile Number</label><input type="text" class="form-control" id="viewMobile" readonly></div>
                        <div class="col-md-6 col-sm-12"><label class="form-label fw-semibold fs-13 text-dark">Email Address</label><input type="text" class="form-control" id="viewEmail" readonly></div>
                    </div>
                </div>
                <div class="modal-section-card">
                    <div class="modal-section-title"><i class="feather-map-pin text-primary fs-15"></i> 3. Address Details</div>
                    <div class="row g-3">
                        <div class="col-md-6 col-sm-12"><label class="form-label fw-semibold fs-13 text-dark">Street Address</label><input type="text" class="form-control" id="viewAddress" readonly></div>
                        <div class="col-md-2 col-sm-4"><label class="form-label fw-semibold fs-13 text-dark">Suburb</label><input type="text" class="form-control" id="viewSuburb" readonly></div>
                        <div class="col-md-2 col-sm-4"><label class="form-label fw-semibold fs-13 text-dark">City</label><input type="text" class="form-control" id="viewCity" readonly></div>
                        <div class="col-md-2 col-sm-4"><label class="form-label fw-semibold fs-13 text-dark">Post Code</label><input type="text" class="form-control" id="viewPostCode" readonly></div>
                    </div>
                </div>
                <div class="modal-section-card mb-0">
                    <div class="modal-section-title"><i class="feather-clipboard text-primary fs-15"></i> 4. Compliance & Processing Status</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Assigned Advisor</label><input type="text" class="form-control" id="viewAdviser" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Not Counting</label><input type="text" class="form-control" id="viewNotCounting" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Compliance by</label><input type="text" class="form-control" id="viewComplianceBy" readonly></div>
                        <div class="col-md-3 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label><input type="text" class="form-control text-danger fw-semibold" id="viewRoaDueDate" readonly></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label><input type="text" class="form-control text-primary fw-semibold" id="viewStatusCompliance" readonly></div>
                        <div class="col-md-4 col-sm-6"><label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label><input type="text" class="form-control text-success fw-semibold" id="viewSentToClient" readonly></div>
                        <div class="col-md-4 col-sm-12"><label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label><input type="text" class="form-control" id="viewOutcome" readonly></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
                @if(auth()->user() && (auth()->user()->canWrite('clients') || auth()->user()->canWrite('clients_login') || auth()->user()->canWrite('clients_cancellation') || auth()->user()->canWrite('clients_inforce') || auth()->user()->canWrite('clients_claims') || auth()->user()->canWrite('clients_inactive') || auth()->user()->canWrite('clients_npw_deferred')))
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="editCurrentViewedSharedClient()"><i class="feather-edit me-1"></i> Edit Client</button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
      window.currentViewedSharedClientId = null;

    window.editCurrentViewedSharedClient = function () {
        const viewModalEl = document.getElementById('viewLoginClientModal');
        const clientId = window.currentViewedSharedClientId || viewModalEl?.getAttribute('data-client-id');
        const viewModal = bootstrap.Modal.getInstance(viewModalEl);
        if (viewModal) viewModal.hide();

        if (clientId && typeof window.openEditClientModal === 'function') {
            setTimeout(() => {
                window.openEditClientModal(clientId);
            }, 300);
        } else if (typeof editCurrentViewedLoginClient === 'function') {
            editCurrentViewedLoginClient();
        }
    };

    window.viewSharedClientDetails = function (data) {
        window.currentViewedSharedClientId = data.id || null;
         const modalElement = document.getElementById('viewLoginClientModal');
        if (modalElement && data.id) {
            modalElement.setAttribute('data-client-id', data.id);
        }
        const fields = {
            viewPolicyNo: data.policy_no || '',
            viewCompany: data.company || '',
            viewFirstName: data.first_name || '',
            viewLastName: data.last_name || '',
            viewDob: data.dob || '',
            viewMobile: data.phone || '',
            viewEmail: data.email || '',
            viewAddress: data.address || '',
            viewSuburb: data.suburb || '',
            viewCity: data.city || '',
            viewPostCode: data.post_code || '',
            viewLoginDate: data.login_date || '',
            viewAnp: data.anp || '',
            viewAdviser: data.adviser || '',
            viewNotCounting: data.not_counting || '',
            viewComplianceBy: data.compliance_by || '',
            viewRoaDueDate: data.roa_due_date || '',
            viewStatusCompliance: data.status_compliance || '',
            viewSentToClient: data.sent_to_client || '',
            viewOutcome: data.outcome || ''
        };

        document.getElementById('viewPolicyNoHeader').textContent = fields.viewPolicyNo;
        Object.entries(fields).forEach(([id, fieldValue]) => {
            const field = document.getElementById(id);
            if (field) {
                field.value = fieldValue;
            }
        });

        
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();
    };

    window.viewSharedClientDetailsFromRow = async function (wrapper) {
       const clientId = wrapper.getAttribute('data-client-id') || wrapper.dataset?.clientId;

        if (clientId) {
             window.currentViewedSharedClientId = clientId;
            const modalElement = document.getElementById('viewLoginClientModal');
            if (modalElement) {
                modalElement.setAttribute('data-client-id', clientId);
            }
            try {
                const response = await fetch(`/clients/${clientId}/profile`, {
                    headers: { Accept: 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Unable to load client profile.');
                }

                window.viewSharedClientDetails(await response.json());
                return;
            } catch (error) {
                console.error(error);
                window.alert('Unable to load this client profile. Please refresh and try again.');
                return;
            }
        }

        const data = wrapper.dataset;
        const value = (key) => {
            const camelKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());

            return data[key] || data[camelKey] || wrapper.getAttribute(`data-${key}`) || wrapper.getAttribute(`data-${key.replaceAll('_', '-')}`) || '';
        };
        const fields = {
            viewPolicyNo: value('policy_no'),
            viewCompany: value('company'),
            viewFirstName: value('first_name'),
            viewLastName: value('last_name'),
            viewDob: value('dob'),
            viewMobile: value('phone'),
            viewEmail: value('email'),
            viewAddress: value('address'),
            viewSuburb: value('suburb'),
            viewCity: value('city'),
            viewPostCode: value('post_code'),
            viewLoginDate: value('login_date'),
            viewAnp: value('anp'),
            viewAdviser: value('adviser'),
            viewNotCounting: ['1', 'yes'].includes(value('not_counting').toLowerCase()) ? 'Yes' : 'No',
            viewComplianceBy: value('compliance_by'),
            viewRoaDueDate: value('roa_due_date'),
            viewStatusCompliance: value('status_compliance'),
            viewSentToClient: value('sent_to_client'),
            viewOutcome: value('outcome')
        };

        document.getElementById('viewPolicyNoHeader').textContent = fields.viewPolicyNo;
        Object.entries(fields).forEach(([id, fieldValue]) => {
            const field = document.getElementById(id);
            if (field) {
                field.value = fieldValue;
            }
        });

        const modalElement = document.getElementById('viewLoginClientModal');
        const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
        modal.show();
    };
</script>

@include('pages.clients.partials.edit-client-modal')
