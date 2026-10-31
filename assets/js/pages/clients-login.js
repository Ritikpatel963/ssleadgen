/* Page script for clients-login */
let loginClientsTable = null;

function viewClientDetailsFromRow(wrapper) {
    if (typeof window.viewSharedClientDetailsFromRow === 'function') {
        window.viewSharedClientDetailsFromRow(wrapper);
        return;
    }

    viewLoginClientFromRow(wrapper);
}

function viewLoginClientFromRow(wrapper) {
    const data = wrapper.dataset;

    $('#viewPolicyNoHeader').text(data.policy_no || '-');
    $('#viewPolicyNo').val(data.policy_no);
    $('#viewCompany').val(data.company);
    $('#viewFirstName').val(data.first_name);
    $('#viewLastName').val(data.last_name);

    let dob = data.dob;
    if (dob) {
        let parts = dob.split('-');
        if (parts.length === 3) dob = parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    $('#viewDob').val(dob);

    $('#viewMobile').val(data.phone);
    $('#viewEmail').val(data.email);
    $('#viewAddress').val(data.address);
    $('#viewSuburb').val(data.suburb);
    $('#viewCity').val(data.city);
    $('#viewPostCode').val(data.post_code);

    let loginDate = data.login_date;
    if (loginDate) {
        let parts = loginDate.split('-');
        if (parts.length === 3) loginDate = parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    $('#viewLoginDate').val(loginDate);

    $('#viewAnp').val(data.anp);
    $('#viewOutcome').val(data.outcome);
    $('#viewAdviser').val(data.adviser);
    $('#viewNotCounting').val(data.not_counting === '1' ? 'Yes' : 'No');
    $('#viewComplianceBy').val(data.compliance_by);

    let roaDate = data.roa_due_date;
    if (roaDate) {
        let parts = roaDate.split('-');
        if (parts.length === 3) roaDate = parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    $('#viewRoaDueDate').val(roaDate);

    $('#viewStatusCompliance').val(data.status_compliance);
    $('#viewSentToClient').val(data.sent_to_client);

    const modalEl = document.getElementById('viewLoginClientModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function editCurrentViewedLoginClient() {
    const viewModalEl = document.getElementById('viewLoginClientModal');
    const viewModal = bootstrap.Modal.getInstance(viewModalEl);
    if (viewModal) viewModal.hide();

    const policyNo = $('#viewPolicyNo').val();
    const wrapper = document.querySelector('.action-kebab-wrapper[data-policy_no="' + policyNo + '"]');
    if (wrapper) {
        setTimeout(() => {
            editLoginClientFromRow(wrapper);
        }, 300);
    }
}

function openAddLoginClientModal() {
    const form = $('#addLoginClientForm');
    form[0].reset();
    form.attr('action', form.data('create-action'));
    $('#loginFormMethod').val('POST');
    $('#loginClientId').val('');
    $('#loginModalTitle').text('Add New Login Client Entry');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('addLoginClientModal')).show();
}

function editLoginClientFromRow(wrapper) {
    const data = wrapper.dataset;

    $('#loginModalTitle').text('Edit Login Client Entry - ' + (data.policy_no || ''));
    $('#addLoginClientForm')[0].reset();

    $('#loginFormMethod').val('PATCH');
    $('#loginClientId').val(data.id);

    // We update the form action to point to the update route
    let formAction = $('#addLoginClientForm').attr('action');
    if (formAction.endsWith('/login')) {
        $('#addLoginClientForm').attr('action', formAction + '/' + data.id);
    } else {
        // If it already has an ID, replace it
        formAction = formAction.substring(0, formAction.lastIndexOf('/'));
        $('#addLoginClientForm').attr('action', formAction + '/' + data.id);
    }

    $('#loginPolicyNoInput').val(data.policy_no || '');
    if (data.company && $('#loginCompanyInput option[value="' + data.company + '"]').length > 0) {
        $('#loginCompanyInput').val(data.company);
    } else if (data.company) {
        $('#loginCompanyInput').append(new Option(data.company, data.company, true, true)).val(data.company);
    }
    $('#loginFirstNameInput').val(data.first_name || '');
    $('#loginLastNameInput').val(data.last_name || '');

    $('#loginDobInput').val(data.dob || '');

    $('#loginMobileInput').val(data.phone || '');
    $('#loginEmailInput').val(data.email || '');
    $('#loginAddressInput').val(data.address || '');
    $('#loginSuburbInput').val(data.suburb || '');
    $('#loginCityInput').val(data.city || '');
    $('#loginPostCodeInput').val(data.post_code || '');

    $('#loginDateInput').val(data.login_date || '');

    $('#loginAnpInput').val(data.anp || '');
    $('#loginOutcomeInput').val(data.outcome || '');
    $('#loginAdviserInput').val(data.adviser || '');
    $('#loginNotCountingSelect').val(data.not_counting === '1' ? '1' : '0');
    $('#loginComplianceByInput').val(data.compliance_by || '');

    $('#loginRoaDueDateInput').val(data.roa_due_date || '');

    $('#loginStatusComplianceSelect').val(data.status_compliance || 'Sent to Compliance');
    $('#loginSentToClientSelect').val(data.sent_to_client || 'Pending');

    const modalEl = document.getElementById('addLoginClientModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function openClientRequestModal(clientName, company, clientId) {
    $('#reqClientNameHeader').text(clientName);
    $('#reqClientNameInput').val(clientName).trigger('change');
    $('#reqCompanyInput').val(company || '');
    $('#reqClientId').val(clientId);

    // Build the action URL from the current base path
    const basePath = window.location.origin + '/clients/login/' + clientId + '/inforce';
    $('#clientRequestForm').attr('action', basePath);

    // Reset variable fields
    $('#reqDateInput').val('');
    $('#reqFinishedDateInput').val('');
    $('#reqTypeSelect').val('');
    $('#reqProcessSelect').val('');
    $('#reqProcessBySelect').val('');
    $('#reqOutcomeInput').val('');
    $('#reqCommentsInput').val('');

    const modalEl = document.getElementById('clientRequestModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function openClaimUpdateModal(clientName, company, clientId, adviser) {
    $('#claimModalTitle').text('New Claim Update - ' + (clientName || 'Client'));

    // Convert selects to text inputs for display purposes since we are moving to a specific client record
    $('#claimClientNameDisplay').val(clientName).trigger('change');
    if ($('#claimClientSelect').length) $('#claimClientSelect').val(clientName);
    $('#claimCompanyDisplay').val(company || '');
    if ($('#claimCompanySelect').length) {
        $('#claimCompanySelect').val(company || '');
        if (company && !$('#claimCompanySelect').val()) {
            $('#claimCompanySelect').append(new Option(company, company, true, true)).val(company);
        }
    }
    $('#claimClientId').val(clientId);

    // Build the action URL from the current base path
    const basePath = window.location.origin + '/clients/login/' + clientId + '/claim';
    $('#lodgeClaimForm').attr('action', basePath);

    // Reset fields
    $('#claimTypeInput').val('');
    $('#claimProcessedDateInput').val('');
    $('#claimApprovedDateInput').val('');
    $('#claimOutcomeInput').val('');
    const assignedAdmin = adviser || '';
    $('#claimAdminSelect').val(assignedAdmin);
    if (assignedAdmin && $('#claimAdminSelect').val() !== assignedAdmin) {
        $('#claimAdminSelect').append(new Option(assignedAdmin, assignedAdmin, true, true)).val(assignedAdmin);
    }
    $('#claimUpdateSelect').val('Under Assessment');

    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openClaimUpdateModalFromRow(wrapper) {
    const data = wrapper.dataset;
    const form = $('#lodgeClaimForm');

    form.attr('action', form.data('action-base') + '/' + data.clientId + '/claim');
    form[0].reset();

    $('#claimModalTitle').text('New Claim Update - ' + (data.clientName || 'Client'));
    $('#claimClientSelect').val(data.clientName || '');
    $('#claimCompanySelect').val(data.company || '');
    if (!$('#claimCompanySelect').val() && data.company) {
        $('#claimCompanySelect').append(new Option(data.company, data.company, true, true));
    }
    $('#claimTypeInput').val(data.claimType || '');
    const admin = data.claimAdmin || data.adviser || '';
    $('#claimAdminSelect').val(admin);
    if (admin && $('#claimAdminSelect').val() !== admin) {
        $('#claimAdminSelect').append(new Option(admin, admin, true, true)).val(admin);
    }
    $('#claimProcessedDateInput').val(data.claimProcessedDate || '');
    $('#claimUpdateSelect').val(data.claimUpdateStatus || 'Under Assessment');
    $('#claimApprovedDateInput').val(data.claimApprovedDate || '');
    $('#claimOutcomeInput').val(data.claimResult || '');

    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openCancellationUpdateModal(clientName, company, clientId, adviser) {
    let name = clientName;
    let comp = company;
    let id = clientId;
    let adv = adviser;
    if (clientName && clientName.dataset) {
        const data = clientName.dataset;
        name = data.clientName;
        comp = data.company;
        id = data.clientId;
        adv = data.cancAdmin || data.adviser;
    }
    const form = $('#addCancellationForm');
    if (form.length) form[0].reset();
    $('#cancModalTitle').text('New Cancellation Update - ' + (name || 'Client'));

    // Set client name and company fields
    $('#cancClientNameDisplay').val(name || '').trigger('change');
    if ($('#cancClientSelect').length) $('#cancClientSelect').val(name || '').trigger('change');

    if (comp) {
        if ($('#cancCompanyDisplay').length) {
            if ($('#cancCompanyDisplay option[value="' + comp + '"]').length === 0) {
                $('#cancCompanyDisplay').append(new Option(comp, comp, true, true));
            }
            $('#cancCompanyDisplay').val(comp).trigger('change');
        }

        if ($('#cancCompanySelect').length) {
            if ($('#cancCompanySelect option[value="' + comp + '"]').length > 0) {
                $('#cancCompanySelect').val(comp).trigger('change');
            } else {
                $('#cancCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
            }
        }
    }

    // Build the action URL from the current base path
    if (id) {
        const form = $('#addCancellationForm');
        if (form.length && form.data('action-base')) {
            form.attr('action', form.data('action-base') + '/' + id + '/cancellation');
        } else if (form.length) {
            form.attr('action', window.location.origin + '/clients/login/' + id + '/cancellation');
        }
    }

    // Reset fields
    $('#cancDateSentInput').val('');
    $('#cancCompletedInput').val('');
    $('#cancOutcomeInput').val('');
    $('#cancCommentsInput').val('');

    const assignedAdmin = adv || '';
    if ($('#cancAdminSelect').length) {
        if (assignedAdmin && $('#cancAdminSelect option[value="' + assignedAdmin + '"]').length === 0) {
            $('#cancAdminSelect').append(new Option(assignedAdmin, assignedAdmin, true, true));
        }
        $('#cancAdminSelect').val(assignedAdmin).trigger('change');
    }

    const modalEl = document.getElementById('addCancellationModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function handleSaveClientRequest(event) {
    return true;
}

// Client modal submissions stay in the modal when Laravel returns validation errors.
document.addEventListener('submit', async function (event) {
    const form = event.target;
    const modal = form instanceof HTMLFormElement ? form.closest('.modal') : null;
    const action = form?.getAttribute('action')?.trim();

    if (!modal || !action || form.id === 'logout-form' || form.id === 'importForm' || form.enctype === 'multipart/form-data') return;

    event.preventDefault();
    event.stopImmediatePropagation();

    const body = modal.querySelector('.modal-body');
    let errors = modal.querySelector('.modal-form-errors');
    if (!errors && body) {
        errors = document.createElement('div');
        errors.className = 'alert alert-danger modal-form-errors d-none';
        errors.setAttribute('role', 'alert');
        body.prepend(errors);
    }

    const clearErrors = () => {
        if (errors) {
            errors.classList.add('d-none');
            errors.replaceChildren();
        }
        form.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
    };
    const showErrors = (messages, fieldErrors = {}) => {
        if (!errors) return;
        const list = document.createElement('ul');
        list.className = 'mb-0';
        messages.forEach(message => {
            const item = document.createElement('li');
            item.textContent = message;
            list.append(item);
        });
        errors.replaceChildren(list);
        errors.classList.remove('d-none');

        Object.keys(fieldErrors).forEach(name => {
            form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach(input => input.classList.add('is-invalid'));
        });
        form.querySelector('.is-invalid')?.focus({ preventScroll: true });
    };

    clearErrors();
    const submitButton = event.submitter || form.querySelector('[type="submit"]');
    const oldButtonHtml = submitButton?.innerHTML;
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';
    }

    try {
        const response = await fetch(action, {
            method: (form.method || 'POST').toUpperCase(),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: new FormData(form)
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const fieldErrors = data.errors || {};
            const messages = Object.values(fieldErrors).flat();
            showErrors(messages.length ? messages : [data.message || 'Could not save. Please check the form and try again.'], fieldErrors);
            return;
        }

        if (data.success !== true) {
            showErrors([data.message || 'Could not save. Please try again.']);
            return;
        }

        bootstrap.Modal.getOrCreateInstance(modal).hide();
        form.reset();
        if (typeof refreshLoginClientsTable === 'function' && document.querySelector('#loginClientsTable, #inactiveClientsTable')) {
            await refreshLoginClientsTable();
        } else {
            window.location.reload();
        }
    } catch (error) {
        showErrors([error.message || 'A network error occurred. Your entries are still here; please try again.']);
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = oldButtonHtml;
        }
    }
}, true);

document.addEventListener('input', event => {
    if (event.target.closest('.modal-form-errors') || !event.target.closest('.modal')) return;
    const modal = event.target.closest('.modal');
    const errors = modal.querySelector('.modal-form-errors');
    if (!errors) return;
    event.target.classList.remove('is-invalid');
    if (!modal.querySelector('.is-invalid')) {
        errors.classList.add('d-none');
        errors.replaceChildren();
    }
});

function initializeLoginClientsTable() {
    if (!$.fn.DataTable.isDataTable('#loginClientsTable')) {
        loginClientsTable = $('#loginClientsTable').DataTable({
            retrieve: true,
            responsive: false,
            pageLength: 10,
            order: [[0, 'desc']],
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search login clients...'
            }
        });
    } else {
        loginClientsTable = $('#loginClientsTable').DataTable();
    }
}

function showLoginClientMessage(message, type = 'success') {
    const title = type === 'success' ? 'Success' : 'Unable to complete action';

    if (typeof window.alert === 'function') {
        window.alert(`${title}: ${message}`);
    }
}

function refreshLoginClientsTable(retryCount = 0, waitForImportedRows = false) {
    const tableSelector = document.querySelector('#loginClientsTable')
        ? '#loginClientsTable'
        : '#inactiveClientsTable';
    const refreshUrl = new URL(window.location.href);
    refreshUrl.searchParams.set('_table_refresh', Date.now().toString());

    return fetch(refreshUrl.toString(), {
        cache: 'no-store',
        headers: {
            Accept: 'text/html',
            'Cache-Control': 'no-cache',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(html => {
            const page = new DOMParser().parseFromString(html, 'text/html');
            const incomingTable = page.querySelector(tableSelector);
            const currentTable = document.querySelector(tableSelector);

            if (!incomingTable || !currentTable) return;

            const incomingRows = Array.from(incomingTable.querySelectorAll('tbody tr'))
                .filter(row => !row.querySelector('td[colspan]'));

            if (waitForImportedRows && incomingRows.length === 0 && retryCount < 5) {
                return new Promise(resolve => {
                    window.setTimeout(() => {
                        resolve(refreshLoginClientsTable(retryCount + 1, true));
                    }, 500);
                });
            }

            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
            }

            currentTable.querySelector('tbody').replaceWith(incomingTable.querySelector('tbody'));

            if (tableSelector === '#loginClientsTable') {
                initializeLoginClientsTable();
            } else {
                $(tableSelector).DataTable({
                    retrieve: true,
                    responsive: false,
                    pageLength: 10,
                    order: [[0, 'desc']],
                    language: {
                        search: '_INPUT_',
                        searchPlaceholder: 'Search inactive clients...'
                    }
                });
            }
        });
}

function submitLoginClientForm(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton ? submitButton.innerHTML : '';

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';
    }

    return fetch(form.action, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: new FormData(form)
    })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Please check the entered values.');
            return data;
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'The action could not be completed.');

            const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
            if (modal) modal.hide();
            form.reset();
            showLoginClientMessage(data.message || 'Action completed successfully.');

            const hasLoginTable = document.querySelector('#loginClientsTable, #inactiveClientsTable');
            if (hasLoginTable) {
                return refreshLoginClientsTable();
            }

            window.location.reload();
        })
        .catch(error => {
            showLoginClientMessage(error.message, 'error');
        })
        .finally(() => {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
}

function handleLoginClientDelete(event, form) {
    event.preventDefault();

    const modalElement = document.getElementById('globalConfirmModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    $('#globalConfirmMessage').text('Delete this client?');
    $('#globalConfirmBtn').off('click.loginClientDelete').on('click.loginClientDelete', function () {
        modal.hide();
        submitLoginClientForm(form);
    });
    modal.show();

    return false;
}





function handleAddNewLoginClient() {
    const policyNo = $('#loginPolicyNoInput').val().trim() || ('POL-2026-' + Math.floor(1000 + Math.random() * 9000));
    const company = $('#loginCompanyInput').val().trim() || 'AIA Health';
    const firstName = $('#loginFirstNameInput').val().trim();
    const lastName = $('#loginLastNameInput').val().trim();
    const rawDob = $('#loginDobInput').val();
    const dob = rawDob ? rawDob.split('-').reverse().join('/') : '15/06/1990';
    const mobile = $('#loginMobileInput').val().trim();
    const email = $('#loginEmailInput').val().trim() || (firstName.toLowerCase() + '.' + lastName.toLowerCase() + '@gmail.com');
    const address = $('#loginAddressInput').val().trim() || '123 Queen Street';
    const suburb = $('#loginSuburbInput').val().trim() || 'Central';
    const city = $('#loginCityInput').val().trim() || 'Auckland';
    const postCode = $('#loginPostCodeInput').val().trim() || '1010';
    const rawLoginDate = $('#loginDateInput').val();
    const loginDate = rawLoginDate ? rawLoginDate.split('-').reverse().join('/') : '18/08/2026';
    const anpVal = $('#loginAnpInput').val().trim() || '2500';
    const formattedAnp = anpVal.startsWith('$') ? anpVal : `$${parseFloat(anpVal.replace(/[^0-9.]/g, '') || 2500).toLocaleString()}`;
    const outcome = $('#loginOutcomeInput').val().trim() || 'Underwriting Review';
    const adviser = $('#loginAdviserInput').val() || 'Sushant Yadav';
    const notCounting = $('#loginNotCountingSelect').val() || 'No';
    const complianceBy = $('#loginComplianceByInput').val().trim() || 'Royson Pinto';
    const rawRoaDate = $('#loginRoaDueDateInput').val();
    const roaDueDate = rawRoaDate ? rawRoaDate.split('-').reverse().join('/') : '30/08/2026';
    const statusCompliance = $('#loginStatusComplianceSelect').val() || 'Sent to Compliance';
    const sentToClient = $('#loginSentToClientSelect').val() || 'Pending';

    if (!firstName || !lastName || !mobile) {
        alert('Please fill in First Name, Last Name, and Mobile Number.');
        return;
    }

    let complianceBadgeClass = 'bg-soft-primary text-primary';
    if (statusCompliance.includes('Approved') || statusCompliance.includes('Completed')) complianceBadgeClass = 'bg-soft-success text-success';
    if (statusCompliance.includes('Review') || statusCompliance.includes('Pending')) complianceBadgeClass = 'bg-soft-warning text-warning';
    const complianceBadge = `<span class="badge ${complianceBadgeClass} fs-11">${statusCompliance}</span>`;

    const sentClientBadge = sentToClient === 'Yes'
        ? '<span class="badge bg-soft-success text-success fs-11"><i class="feather-check me-1"></i>Yes</span>'
        : '<span class="badge bg-soft-secondary text-secondary fs-11">Pending</span>';

    const clientFullName = firstName + ' ' + lastName;

    const actionParams = `'${policyNo}', '${company}', '${firstName}', '${lastName}', '${dob}', '${mobile}', '${email}', '${address}', '${suburb}', '${city}', '${postCode}', '${loginDate}', '${formattedAnp}', '${outcome}', '${adviser}', '${notCounting}', '${complianceBy}', '${roaDueDate}', '${statusCompliance}', '${sentToClient}'`;
    const clientActions = $('#loginClientsTable').attr('data-can-write') === '1' ? `
        <a href="javascript:void(0);" class="action-kebab-item" onclick="editLoginClientDetails(${actionParams})"><i class="feather-edit text-success me-1"></i> Edit Client</a>
        <a href="javascript:void(0);" class="action-kebab-item" onclick="openClientRequestModal('${clientFullName}', '${company}', '')"><i class="feather-git-pull-request text-warning me-1"></i> Client Request</a>
        <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModal('${clientFullName}', '${company}', '')"><i class="feather-shield text-info me-1"></i> Claim Update</a>
        <a href="javascript:void(0);" class="action-kebab-item" onclick="openInactiveClientModal('${clientFullName}', '${company}', '')"><i class="feather-user-x text-secondary me-1"></i> Inactive Clients</a>
        <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwDeferredModal('${clientFullName}', '${company}', '')"><i class="feather-clock text-purple me-1"></i> NPW Deferred</a>
        <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationUpdateModal('${clientFullName}', '${company}', '')"><i class="feather-file-minus text-danger me-1"></i> Cancellation update</a>` : '';

    const rowData = [
        `<span class="fw-bold text-dark fs-12">${policyNo}</span>`,
        `<span class="fs-13 fw-semibold text-dark">${company}</span>`,
        `<span class="fs-13 fw-bold text-dark">${clientFullName}</span>`,
        `<span class="fs-13 text-muted">${mobile}</span>`,
        `<span class="fs-13 text-muted">${loginDate}</span>`,
        `<span class="fs-13 fw-bold text-dark">${formattedAnp}</span>`,
        complianceBadge,
        sentClientBadge,
        `<div class="text-center">
            <div class="action-kebab-wrapper">
                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                <div class="action-kebab-dropdown">
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="viewLoginClientDetails(${actionParams})"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                    ${clientActions}
                </div>
            </div>
        </div>`
    ];

    if (loginClientsTable) {
        let existingRowIndex = -1;
        loginClientsTable.rows().every(function (rowIdx) {
            const data = this.data();
            if (data && data[0] && data[0].includes(policyNo)) {
                existingRowIndex = rowIdx;
            }
        });

        if (existingRowIndex >= 0) {
            loginClientsTable.row(existingRowIndex).data(rowData).draw(false);
            alert(`Login Client Record for ${clientFullName} (${policyNo}) updated successfully!`);
        } else {
            loginClientsTable.row.add(rowData).draw(false);
            alert(`Login Client Record for ${clientFullName} (${policyNo}) created successfully!`);
        }
    }

    $('#addLoginClientForm')[0].reset();
    const modalEl = document.getElementById('addLoginClientModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function openImportClientsModal() {
    const modalElement = document.getElementById('importLoginClientsModal');

    if (modalElement) {
        const form = document.getElementById('importForm');
        const fileInput = document.getElementById('importFileInput');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const submitButton = document.getElementById('importClientsSubmitButton');

        form?.reset();
        if (fileInput) fileInput.value = '';
        if (fileNameDisplay) {
            fileNameDisplay.textContent = '';
            fileNameDisplay.classList.remove('text-danger');
        }
        if (submitButton) submitButton.disabled = true;

        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
}

function initializeImportFileDropzone() {
    const dropZone = document.querySelector('#importLoginClientsModal .drag-drop-box');
    const fileInput = document.getElementById('importFileInput');

    if (!dropZone || !fileInput || dropZone.dataset.dropzoneReady === 'true') {
        return;
    }

    const updateSelectedFile = () => {
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        if (fileInput.files[0] && fileNameDisplay) {
            fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`;
            fileNameDisplay.classList.remove('text-danger');
        }

        const submitButton = document.getElementById('importClientsSubmitButton');
        if (submitButton) submitButton.disabled = !fileInput.files.length;
    };

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, event => {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, event => {
            event.preventDefault();
            event.stopPropagation();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', event => {
        if (!event.dataTransfer.files.length) {
            return;
        }

        fileInput.files = event.dataTransfer.files;
        updateSelectedFile();
    });

    fileInput.addEventListener('change', updateSelectedFile);
    dropZone.dataset.dropzoneReady = 'true';
}

function downloadLoginClientsTemplate(event) {
    event.preventDefault();

    const headers = [
        'Policy No', 'Insurance Company', 'First Name', 'Last Name', 'DOB', 'Mobile',
        'Email', 'Address', 'Suburb', 'City', 'Post Code', 'Login Date', 'ANP',
        'Outcome', 'Adviser', 'Not Counting', 'Compliance by', 'RoA Due on',
        'Status', 'Sent to Client'
    ];
    const csv = `${headers.join(',')}\r\n`;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const downloadUrl = URL.createObjectURL(blob);
    const downloadLink = document.createElement('a');

    downloadLink.href = downloadUrl;
    downloadLink.download = 'login-clients-template.csv';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    downloadLink.remove();
    URL.revokeObjectURL(downloadUrl);
}

function escapeImportResultHtml(value) {
    return $('<div>').text(value ?? '').html();
}

function openNpwDeferredModal(clientName, company, clientId, adviser) {
    let name = clientName;
    let comp = company;
    let id = clientId;
    let adv = adviser;
    if (clientName && clientName.dataset) {
        const data = clientName.dataset;
        name = data.clientName;
        comp = data.company;
        id = data.clientId;
        adv = data.npwAdmin || data.adviser;
    }
    const form = $('#addNpwForm');
    if (form.length) form[0].reset();
    $('#npwClientNameDisplay').val(name || '').text(name || '').trigger('change');
    if ($('#npwClientSelect').length) $('#npwClientSelect').val(name || '').trigger('change');
    if ($('#npwClientInput').length) $('#npwClientInput').val(name || '').trigger('change');

    if (comp) {
        if ($('#npwCompanyDisplay').length) {
            if ($('#npwCompanyDisplay option[value="' + comp + '"]').length === 0) {
                $('#npwCompanyDisplay').append(new Option(comp, comp, true, true));
            }
            $('#npwCompanyDisplay').val(comp).trigger('change');
        }
        if ($('#npwCompanySelect').length) {
            if ($('#npwCompanySelect option[value="' + comp + '"]').length === 0) {
                $('#npwCompanySelect').append(new Option(comp, comp, true, true));
            }
            $('#npwCompanySelect').val(comp).trigger('change');
        }
        if ($('#npwCompanyInput').length) {
            if ($('#npwCompanyInput option[value="' + comp + '"]').length === 0) {
                $('#npwCompanyInput').append(new Option(comp, comp, true, true));
            }
            $('#npwCompanyInput').val(comp).trigger('change');
        }
    }

    if (id) {
        const basePath = form.data('action-base') || (window.location.origin + '/clients/login');
        form.attr('action', basePath.replace(/\/$/, '') + '/' + id + '/npw-deferred');
    }

    const assignedAdmin = adv || '';
    if ($('#npwAdminSelect').length) {
        if (assignedAdmin && $('#npwAdminSelect option[value="' + assignedAdmin + '"]').length === 0) {
            $('#npwAdminSelect').append(new Option(assignedAdmin, assignedAdmin, true, true));
        }
        $('#npwAdminSelect').val(assignedAdmin).trigger('change');
    }
    $('#npwPendingSelect').val('');

    const modalEl = document.getElementById('addNpwModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}
function openInactiveClientModal(clientName, company, clientId) {
    const form = document.getElementById('addInactiveForm');
    if (form) form.reset();
    $('#inactiveClientNameDisplay').text(clientName);

    const basePath = form?.dataset.actionBase || (window.location.origin + '/clients/login');
    if (form && clientId) form.action = basePath.replace(/\/$/, '') + '/' + clientId + '/inactive';

    const modalEl = document.getElementById('addInactiveModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

document.addEventListener('hidden.bs.modal', event => {
    event.target.querySelectorAll('.modal-form-errors').forEach(errors => {
        errors.classList.add('d-none');
        errors.replaceChildren();
    });
    event.target.querySelectorAll('.is-invalid').forEach(input => input.classList.remove('is-invalid'));
});

// Keep legacy View Profile links compatible with the shared client-details modal.
window.viewLoginClientFromRow = function (wrapper) {
    if (typeof window.viewSharedClientDetailsFromRow === 'function') {
        window.viewSharedClientDetailsFromRow(wrapper);
        return;
    }

    const data = wrapper.dataset;
    const value = (key) => {
        const camelKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());

        return data[key] || data[camelKey] || wrapper.getAttribute(`data-${key}`) || wrapper.getAttribute(`data-${key.replaceAll('_', '-')}`) || '';
    };

    $('#viewPolicyNoHeader').text(value('policy_no') || '-');
    $('#viewPolicyNo').val(value('policy_no'));
    $('#viewCompany').val(value('company'));
    $('#viewFirstName').val(value('first_name'));
    $('#viewLastName').val(value('last_name'));
    $('#viewDob').val(value('dob'));
    $('#viewMobile').val(value('phone'));
    $('#viewEmail').val(value('email'));
    $('#viewAddress').val(value('address'));
    $('#viewSuburb').val(value('suburb'));
    $('#viewCity').val(value('city'));
    $('#viewPostCode').val(value('post_code'));
    $('#viewLoginDate').val(value('login_date'));
    $('#viewAnp').val(value('anp'));
    $('#viewAdviser').val(value('adviser'));
    $('#viewNotCounting').val(value('not_counting'));
    $('#viewComplianceBy').val(value('compliance_by'));
    $('#viewRoaDueDate').val(value('roa_due_date'));
    $('#viewStatusCompliance').val(value('status_compliance'));
    $('#viewSentToClient').val(value('sent_to_client'));
    $('#viewOutcome').val(value('outcome'));

    const modalElement = document.getElementById('viewLoginClientModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    modal.show();
};

let currentImportDbFields = {};
let currentImportDestination = 'login_client';
let currentUploadedImportPath = null;
let currentImportSheets = [];
let currentPreviewOffset = 0;
let currentPreviewLimit = 25;
const clientImportStorageKey = 'pendingClientImports';
const activeClientImportPolls = new Set();
const dismissedClientImportToasts = new Set();

function savePendingClientImport(trackingId) {
    const pending = JSON.parse(localStorage.getItem(clientImportStorageKey) || '[]');
    if (!pending.includes(trackingId)) {
        pending.push(trackingId);
        localStorage.setItem(clientImportStorageKey, JSON.stringify(pending));
    }
}

function removePendingClientImport(trackingId) {
    const pending = JSON.parse(localStorage.getItem(clientImportStorageKey) || '[]')
        .filter(id => id !== trackingId);
    localStorage.setItem(clientImportStorageKey, JSON.stringify(pending));
}

function showClientImportToast(message, trackingId, errorCsvUrl = null, variant = 'info', progress = null, keepOpen = false) {
    if (keepOpen && dismissedClientImportToasts.has(trackingId)) return;

    let container = document.getElementById('clientImportToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'clientImportToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1090';
        container.style.width = 'min(380px, calc(100vw - 24px))';
        document.body.appendChild(container);
    }

    const accentColors = {
        info: 'var(--ss-cyan, #00A8B5)',
        success: '#34D399',
        warning: '#FBBF24',
        error: '#F87171',
    };
    const toastId = `clientImportToast-${trackingId}`;
    let toastElement = document.getElementById(toastId);
    if (!toastElement) {
        toastElement = document.createElement('div');
        toastElement.id = toastId;
        toastElement.dataset.importId = trackingId;
        toastElement.className = 'toast align-items-center border-0 shadow';
        toastElement.setAttribute('role', 'status');
        toastElement.setAttribute('aria-live', 'polite');
        toastElement.style.backgroundColor = 'var(--ss-navy-dark, #0A192F)';
        toastElement.style.color = '#F8FAFC';
        toastElement.style.width = '100%';
        toastElement.style.position = 'relative';

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.style.paddingRight = '40px';
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close btn-close-white';
        closeButton.setAttribute('aria-label', 'Dismiss import progress');
        closeButton.title = 'Dismiss';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '12px';
        closeButton.style.right = '12px';
        closeButton.style.zIndex = '1';
        closeButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            dismissedClientImportToasts.add(trackingId);
            clearTimeout(toastElement.dismissTimer);
            toastElement.remove();
        });
        toastElement.append(body, closeButton);
        toastElement.addEventListener('hidden.bs.toast', () => {
            clearTimeout(toastElement.dismissTimer);
            toastElement.remove();
        }, { once: true });
        container.appendChild(toastElement);
    }

    toastElement.style.borderLeft = `4px solid ${accentColors[variant] || accentColors.info}`;
    const body = toastElement.querySelector('.toast-body');
    body.replaceChildren();
    const text = document.createElement('div');
    text.textContent = message;
    body.appendChild(text);

    if (keepOpen) {
        const total = Number(progress?.total) || 0;
        const processed = Math.min(total || Number.MAX_SAFE_INTEGER, Number(progress?.processed) || 0);
        const failed = Number(progress?.failed) || 0;
        const percent = total > 0 ? Math.floor((processed / total) * 100) : 32;
        let animationStyles = document.getElementById('clientImportProgressAnimation');
        if (!animationStyles) {
            animationStyles = document.createElement('style');
            animationStyles.id = 'clientImportProgressAnimation';
            animationStyles.textContent = '@keyframes clientImportToastSweep { from { transform: translateX(-120%); } to { transform: translateX(320%); } }';
            document.head.appendChild(animationStyles);
        }

        const progressTrack = document.createElement('div');
        progressTrack.style.height = '6px';
        progressTrack.style.width = '100%';
        progressTrack.style.marginTop = '8px';
        progressTrack.style.backgroundColor = 'rgba(248, 250, 252, 0.2)';
        progressTrack.style.borderRadius = '999px';
        progressTrack.style.overflow = 'hidden';
        progressTrack.setAttribute('role', 'progressbar');
        progressTrack.setAttribute('aria-valuemin', '0');
        if (total > 0) {
            progressTrack.setAttribute('aria-valuemax', String(total));
            progressTrack.setAttribute('aria-valuenow', String(processed));
        } else {
            progressTrack.setAttribute('aria-label', 'Import waiting for worker');
        }
        const progressBar = document.createElement('div');
        progressBar.style.height = '100%';
        progressBar.style.width = `${percent}%`;
        progressBar.style.borderRadius = '999px';
        progressBar.style.backgroundColor = accentColors[variant] || accentColors.info;
        progressBar.style.transition = 'width 0.35s ease';
        if (total === 0) {
            progressBar.style.animation = 'clientImportToastSweep 1.4s ease-in-out infinite';
        }
        progressTrack.appendChild(progressBar);
        body.appendChild(progressTrack);

        const progressText = document.createElement('div');
        progressText.className = 'fs-11 mt-1';
        progressText.style.color = '#CBD5E1';
        progressText.textContent = total > 0
            ? `${processed} / ${total} rows${failed ? ` - ${failed} failed` : ''}`
            : `${processed} rows processed - waiting for total`;
        body.appendChild(progressText);
    }

    const links = document.createElement('div');
    links.className = 'd-flex gap-3 mt-2';
    const detailsLink = document.createElement('a');
    detailsLink.href = `${window.location.pathname}?import_id=${encodeURIComponent(trackingId)}`;
    detailsLink.textContent = 'View results';
    detailsLink.style.color = 'var(--ss-cyan, #00A8B5)';
    detailsLink.style.fontWeight = '600';
    links.appendChild(detailsLink);
    if (errorCsvUrl) {
        const csvLink = document.createElement('a');
        csvLink.href = errorCsvUrl;
        csvLink.textContent = 'Download error CSV';
        csvLink.style.color = 'var(--ss-cyan, #00A8B5)';
        csvLink.style.fontWeight = '600';
        links.appendChild(csvLink);
    }
    if (!keepOpen || errorCsvUrl) body.appendChild(links);

    clearTimeout(toastElement.dismissTimer);
    bootstrap.Toast.getOrCreateInstance(toastElement, { autohide: false }).show();
    if (!keepOpen) {
        toastElement.dismissTimer = setTimeout(() => {
            bootstrap.Toast.getOrCreateInstance(toastElement).hide();
        }, 10000);
    }
}

function showClientImportResult(data, trackingId, openModal = false) {
    const hasFailures = Number(data.failed_count || 0) > 0;
    const message = `Client import ${data.status === 'failed' ? 'failed' : 'finished'}: ${data.imported_count || 0} imported, ${data.skipped_count || 0} skipped, ${data.failed_count || 0} failed.`;
    const variant = data.status === 'failed' ? 'error' : (hasFailures ? 'warning' : 'success');
    const summary = document.getElementById('importResultsSummary');
    if (!summary) {
        showClientImportToast(message, trackingId, data.error_csv_url, variant);
        return;
    }
    summary.replaceChildren();

    const alert = document.createElement('div');
    alert.className = `alert ${data.status === 'failed' ? 'alert-danger' : (hasFailures ? 'alert-warning' : 'alert-success')} mb-0`;
    alert.textContent = data.error_message ? `${message} ${data.error_message}` : message;
    summary.appendChild(alert);

    if (data.error_csv_url) {
        const download = document.createElement('a');
        download.className = 'btn btn-outline-danger btn-sm mt-3';
        download.href = data.error_csv_url;
        download.textContent = 'Download failed rows CSV';
        summary.appendChild(download);
    }

    if (openModal) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('importResultsModal')).show();
    } else {
        showClientImportToast(message, trackingId, data.error_csv_url, variant);
    }
}

function showImportStep(step) {
    [1, 2, 3].forEach(i => {
        $(`#importStep${i}`).addClass('d-none');
        $(`#importFooterStep${i}`).addClass('d-none');
    });
    $(`#importStep${step}`).removeClass('d-none');
    $(`#importFooterStep${step}`).removeClass('d-none');
}

async function loadSelectedImportSheet(sheetIndex) {
    const formData = new FormData();
    formData.set('file_path', currentUploadedImportPath);
    formData.set('destination', currentImportDestination);
    formData.set('sheet_index', sheetIndex);

    const response = await fetch('/import/headers', {
        method: 'POST',
        body: formData,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not read the selected worksheet.');
    }

    document.getElementById('importSheetIndex').value = result.sheet_index;
    document.getElementById('selectedSheetName').textContent = `(${result.sheet_name})`;
    currentImportDbFields = result.db_fields;

    const mappingTableBody = document.querySelector('.mapping-table tbody');
    mappingTableBody.replaceChildren();
    result.mapping.forEach((mapObj, index) => {
        const row = document.createElement('tr');
        const headerCell = document.createElement('td');
        headerCell.innerHTML = `<span class="fw-semibold text-dark">${escapeImportResultHtml(mapObj.original_header)}</span>
                                <input type="hidden" name="mapping[${index}][original_header]" value="${escapeImportResultHtml(mapObj.original_header)}">`;
        const mappingCell = document.createElement('td');
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm mapping-select';
        select.name = `mapping[${index}][db_field]`;
        select.add(new Option('-- Do Not Import --', '--ignore--'));
        Object.entries(result.db_fields).forEach(([fieldKey, fieldConfig]) => {
            const option = new Option(`${fieldConfig.label}${fieldConfig.required ? ' *' : ''}`, fieldKey);
            option.selected = mapObj.suggested_field === fieldKey;
            select.add(option);
        });
        mappingCell.appendChild(select);
        row.append(headerCell, mappingCell);
        mappingTableBody.appendChild(row);
    });

    showImportStep(2);
}

async function handleImportStepUpload() {
    const fileInput = document.getElementById('importFileInput');
    if (!currentUploadedImportPath && (!fileInput || fileInput.files.length === 0)) {
        alert('Please select a CSV or Excel file to import.');
        return;
    }

    const form = document.getElementById('importForm');
    const submitButton = document.getElementById('importClientsUploadButton');
    const originalButtonHtml = submitButton.innerHTML;
    submitButton.disabled = true;

    try {
        if (!currentUploadedImportPath) {
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Uploading...';
            const formData = new FormData();
            formData.set('import_file', fileInput.files[0]);
            formData.set('destination', document.getElementById('importDestination').value);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'The file could not be uploaded.');
            }

            currentUploadedImportPath = result.file_path;
            currentImportDestination = result.destination;
            currentImportSheets = result.sheets || [];
            document.getElementById('tempFilePath').value = result.file_path;

            if (currentImportSheets.length > 1) {
                const selector = document.getElementById('importSheetSelect');
                selector.replaceChildren(...currentImportSheets.map(sheet => new Option(sheet.name, sheet.index)));
                document.getElementById('importSheetSelector').classList.remove('d-none');
                return;
            }
        }

        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Reading Worksheet...';
        const selectedSheetIndex = currentImportSheets.length > 1
            ? document.getElementById('importSheetSelect').value
            : (currentImportSheets[0]?.index ?? 0);
        await loadSelectedImportSheet(selectedSheetIndex);
    } catch (error) {
        alert(error.message || 'Could not read the selected worksheet.');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = currentUploadedImportPath && currentImportSheets.length > 1
            ? '<i class="feather-arrow-right me-1"></i> Continue with Selected Worksheet'
            : originalButtonHtml;
    }
}

async function handleImportStepPreview(offset = 0) {
    const previewBtn = document.getElementById('importClientsPreviewButton');
    const originalHtml = previewBtn.innerHTML;
    previewBtn.disabled = true;
    previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading Preview...';
    document.getElementById('importPreviewPreviousButton').disabled = true;
    document.getElementById('importPreviewNextButton').disabled = true;

    try {
        const formData = new FormData(document.getElementById('importForm'));
        formData.set('file_path', document.getElementById('tempFilePath').value);
        formData.set('preview_offset', offset);
        formData.set('preview_limit', currentPreviewLimit);

        const response = await fetch('/import/preview', {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Could not generate preview.');
        }

        currentPreviewOffset = result.preview_offset;
        currentPreviewLimit = result.preview_limit;

        const thead = document.querySelector('.preview-table thead');
        const tbody = document.querySelector('.preview-table tbody');
        thead.innerHTML = '';
        tbody.innerHTML = '';

        // Build headers for preview based on mapping
        let headerRow = '<tr><th>Row</th>';
        const mappedFields = [];

        document.querySelectorAll('.mapping-select').forEach(select => {
            const val = select.value;
            if (val !== '--ignore--') {
                mappedFields.push(val);
                headerRow += `<th>${currentImportDbFields[val]?.label || val}</th>`;
            }
        });
        headerRow += '<th>Status</th></tr>';
        thead.innerHTML = headerRow;

        // Build rows
        result.preview_data.forEach((rowResult, index) => {
            let trClass = rowResult.success ? '' : 'table-danger';
            let tr = `<tr class="${trClass}"><td>${rowResult.row_number}</td>`;

            mappedFields.forEach(field => {
                const val = rowResult.raw[field] ?? '-';
                tr += `<td>${escapeImportResultHtml(val)}</td>`;
            });

            if (rowResult.success) {
                tr += `<td><span class="badge bg-soft-success text-success">Valid</span></td>`;
            } else {
                tr += `<td><span class="badge bg-soft-danger text-danger" title="${escapeImportResultHtml(rowResult.errors.join(', '))}">${escapeImportResultHtml(rowResult.errors[0])}</span></td>`;
            }
            tr += '</tr>';
            tbody.innerHTML += tr;
        });

        const rowStart = result.total_rows ? result.preview_offset + 1 : 0;
        const rowEnd = Math.min(result.preview_offset + result.preview_limit, result.total_rows);
        document.getElementById('importPreviewPageSummary').textContent = `Showing ${rowStart}-${rowEnd} of ${result.total_rows} rows`;
        document.getElementById('importPreviewPreviousButton').disabled = result.preview_offset === 0;
        document.getElementById('importPreviewNextButton').disabled = !result.has_more;

        showImportStep(3);
    } catch (error) {
        alert(error.message || 'Error loading preview.');
    } finally {
        previewBtn.disabled = false;
        previewBtn.innerHTML = originalHtml;
    }
}

async function handleImportStepProcess() {
    const processBtn = document.getElementById('importClientsProcessButton');
    const originalHtml = processBtn.innerHTML;
    processBtn.disabled = true;
    processBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Queuing Import...';

    try {
        const formData = new FormData(document.getElementById('importForm'));
        formData.set('file_path', document.getElementById('tempFilePath').value);
        formData.set('skip_errors', document.getElementById('skipErrorsCheck').checked ? '1' : '0');

        const response = await fetch('/import/process', {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Error queuing import process.');
        }

        const trackingId = result.tracking_id;
        savePendingClientImport(trackingId);
        processBtn.disabled = false;
        processBtn.innerHTML = originalHtml;
        bootstrap.Modal.getInstance(document.getElementById('importLoginClientsModal'))?.hide();
        showClientImportToast('Import queued. You can continue working while it processes.', trackingId, null, 'info', { processed: 0, total: 0 }, true);
        pollImportStatus(trackingId).catch(error => console.error('Unable to monitor client import:', error));

    } catch (error) {
        alert(error.message || 'Error during import process.');
        processBtn.disabled = false;
        processBtn.innerHTML = originalHtml;
    }
}

async function pollImportStatus(trackingId, openWhenComplete = false) {
    if (activeClientImportPolls.has(trackingId)) return;
    activeClientImportPolls.add(trackingId);
    let transientFailures = 0;

    try {
        while (true) {
            try {
                const response = await fetch(`/import/status/${encodeURIComponent(trackingId)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.status === 404) {
                    removePendingClientImport(trackingId);
                    return;
                }
                if (!response.ok) throw new Error(`Status request failed (${response.status}).`);

                const data = await response.json();
                transientFailures = 0;
                if (['done', 'done_with_errors', 'failed'].includes(data.status)) {
                    removePendingClientImport(trackingId);
                    showClientImportResult(data, trackingId, openWhenComplete);
                    if (typeof refreshLoginClientsTable === 'function') {
                        refreshLoginClientsTable(0, true).catch(error => console.error('Unable to refresh login clients:', error));
                    }
                    return;
                }

                const progressMessage = data.status === 'pending' ? 'Import is queued.' : 'Import is in progress.';
                showClientImportToast(
                    progressMessage,
                    trackingId,
                    null,
                    'info',
                    {
                        processed: data.processed_count,
                        total: data.total_rows,
                        failed: data.failed_count,
                    },
                    true
                );
            } catch (error) {
                transientFailures++;
                if (transientFailures >= 5) {
                    console.error('Client import status polling paused:', error);
                    return;
                }
            }

            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    } finally {
        activeClientImportPolls.delete(trackingId);
    }
}

// Ensure the modal resets to step 1 when closed manually
$(document).on('hidden.bs.modal', '#importLoginClientsModal', function () {
    showImportStep(1);
    $('#importForm')[0].reset();
    $('#fileNameDisplay').empty();
    currentUploadedImportPath = null;
    currentImportSheets = [];
    document.getElementById('importSheetSelector').classList.add('d-none');
    document.getElementById('selectedSheetName').textContent = '';
});

document.getElementById('importFileInput')?.addEventListener('change', function () {
    currentUploadedImportPath = null;
    currentImportSheets = [];
    document.getElementById('tempFilePath').value = '';
    document.getElementById('importSheetIndex').value = '0';
    document.getElementById('importSheetSelector').classList.add('d-none');
    document.getElementById('importSheetSelect').replaceChildren();
});

// Re-add missing document ready elements
$(document).ready(function () {
    if (typeof initializeLoginClientsTable === 'function') {
        initializeLoginClientsTable();
    }
    if (typeof initializeImportFileDropzone === 'function') {
        initializeImportFileDropzone();
    }

    if (!document.getElementById('importResultsSummary')) return;

    const requestedImportId = new URLSearchParams(window.location.search).get('import_id');
    if (requestedImportId) {
        savePendingClientImport(requestedImportId);
        pollImportStatus(requestedImportId, true);
    } else {
        const pendingImports = JSON.parse(localStorage.getItem(clientImportStorageKey) || '[]');
        pendingImports.forEach(trackingId => pollImportStatus(trackingId));
    }
});

window.openImportClientsModal = function () {
    const form = document.getElementById('importForm');
    if (form) form.reset();
    $('#fileNameDisplay').empty();
    showImportStep(1);
    const modalEl = document.getElementById('importLoginClientsModal');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
};


// Local trigger for login clients table filtering since it is excluded from global
$(document).on('click', '#btnExecuteSearch', function (e) {
    if (typeof loginClientsTable !== 'undefined') {
        loginClientsTable.draw();
    }
});

$(document).on('click', '#btnClearSearch', function (e) {
    if (typeof loginClientsTable !== 'undefined') {
        loginClientsTable.search('').columns().search('').draw();
    }
});
