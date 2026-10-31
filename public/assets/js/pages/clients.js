/* Page script for clients */
var clientsDataTable = window.clientsDataTable || null;

function viewClientDetails(name, phone, address, status, policies) {
    $('#viewClientModalTitle').text(name + ' - Profile Overview');
    $('#viewClientModalBody').html(`
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="avatar-text avatar-lg bg-soft-primary text-primary rounded-circle fs-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"><i class="feather-user"></i></div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">${name}</h5>
                        <span class="badge ${status === 'Inforce' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning'} fw-bold fs-11 mt-1">${status} Client</span>
                    </div>
                </div>
                <div class="row g-3 fs-13">
                    <div class="col-6"><strong>Phone:</strong> ${phone}</div>
                    <div class="col-6"><strong>Address:</strong> ${address}</div>
                    <div class="col-6"><strong>Active Policies:</strong> ${policies}</div>
                    <div class="col-6"><strong>Advisor Assigned:</strong> Sushant Yadav</div>
                </div>
            `);
    $('#viewClientModal').modal('show');
}

function handleAddNewClient() {
    const name = $('#clientNameInput').val().trim();
    const phone = $('#clientPhoneInput').val().trim();
    const rawDob = $('#clientDobInput').val();
    const dob = rawDob ? rawDob.split('-').reverse().join('/') : '12/05/1988';
    const address = $('#clientAddressInput').val().trim() || 'Auckland, NZ';
    const status = $('#clientStatusSelect').val();
    const source = $('#clientSourceSelect').val();
    const policies = $('#clientPoliciesInput').val() || '1';

    if (!name || !phone) return;

    const statusPill = status === 'Inforce'
        ? `<span class="status-pill-inforce">Inforce</span>`
        : `<span class="status-pill-inactive">Inactive</span>`;

    if (clientsDataTable) {
        clientsDataTable.row.add([
            `<span class="fw-bold text-dark fs-13">${name}</span>`,
            `<span class="fs-13 text-muted">${phone}</span>`,
            `<span class="fs-13 text-muted">${dob}</span>`,
            `<span class="fs-13 text-muted">${address}</span>`,
            statusPill,
            `<span class="fs-13 text-muted">${source}</span>`,
            `<span class="fs-13 fw-semibold text-dark text-center">${policies}</span>`,
            `<span class="fs-13 text-muted">Today</span>`,
            `<div class="text-center">
                        <div class="action-kebab-wrapper">
                            <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                            <div class="action-kebab-dropdown">
                                <a href="javascript:void(0);" class="action-kebab-item" onclick="viewClientDetails('${name}', '${phone}', '${address}', '${status}', '${policies}')"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                            </div>
                        </div>
                    </div>`
        ]).draw(false);
    }

    $('#addClientForm')[0].reset();
    const modalEl = document.getElementById('addClientModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#clientsDirectoryTable')) {
        clientsDataTable = $('#clientsDirectoryTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: '/clients/data',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    // Include custom filter parameters
                    d.client_search = $('#filterClientSearch').val();
                    d.phone_search = $('#filterNumberSearch').val();
                    d.address_search = $('#filterAddressSearch').val();
                    d.dob_search = $('#filterDobSearch').val();
                },
                dataSrc: function (json) {
                    // Update the count in the header
                    $('#allClientsCount').text('(' + json.recordsTotal.toLocaleString() + ' Profiles)');
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'full_name',
                    render: function (data, type, row) {
                        return '<span class="fw-bold text-dark fs-13">' + data + '</span>';
                    }
                },
                {
                    data: 'phone',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                {
                    data: 'dob',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                {
                    data: 'address',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                {
                    data: 'status',
                    orderable: false,
                    render: function (data) {
                        let badge = '';
                        switch (data) {
                            case 'Inforce':
                                badge = '<span class="status-pill-inforce">Inforce</span>';
                                break;
                            case 'Inactive':
                                badge = '<span class="status-pill-inactive">Inactive</span>';
                                break;
                            case 'Cancellation':
                                badge = '<span class="status-pill-cancellation">Cancellation</span>';
                                break;
                            case 'NPW Deferred':
                                badge = '<span class="status-pill-npw">NPW Deferred</span>';
                                break;
                            case 'Login Client':
                                badge = '<span class="badge bg-primary fs-11 px-2 py-1">Login Client</span>';
                                break;
                            case 'Claims':
                                badge = '<span class="badge bg-info fs-11 px-2 py-1">Claims</span>';
                                break;
                            default:
                                badge = '<span class="badge bg-secondary fs-11 px-2 py-1">' + (data || 'Unknown') + '</span>';
                        }
                        return badge;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function () {
                        return '<span class="fs-13 text-muted">Direct</span>';
                    }
                },
                {
                    data: 'updated_at',
                    orderable: false,
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        const canWrite = $('#clientsDirectoryTable').attr('data-can-write') === '1';
                        const writeActions = canWrite ? `
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openEditClientModalFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-edit text-info me-1"></i> Edit Client</a>
                                ` : '';

                        return `<div class="text-center">
                                    <div class="action-kebab-wrapper"
                                        data-client-id="${row.id}"
                                        data-client-name="${row.full_name}"
                                        data-company="${row.company}"
                                        data-phone="${row.phone}"
                                        data-first-name="${row.first_name}"
                                        data-last-name="${row.last_name}"
                                        data-email="${row.email}"
                                        data-dob="${row.dob_raw}"
                                        data-address="${row.address}"
                                        data-suburb="${row.suburb}"
                                        data-city="${row.city}"
                                        data-post_code="${row.post_code}"
                                        data-status="${row.status}"
                                        data-adviser="${row.adviser}">
                                        <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                        <div class="action-kebab-dropdown">
                                            <a href="javascript:void(0);" class="action-kebab-item" onclick="viewSharedClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                                            ${writeActions}
                                        </div>
                                    </div>
                                </div>`;
                    }
                }
            ],
            retrieve: true,
            responsive: false,
            pageLength: 10,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search client records...",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });

        // Handle custom filter search
        $('#btnExecuteSearch').on('click', function () {
            clientsDataTable.ajax.reload();
        });

        // Handle clear filters
        $('#btnClearSearch').on('click', function () {
            $('#filterClientSearch').val('');
            $('#filterNumberSearch').val('');
            $('#filterAddressSearch').val('');
            $('#filterDobSearch').val('');
            clientsDataTable.ajax.reload();
        });

        // Allow Enter key to trigger search
        $('#filterClientSearch, #filterNumberSearch, #filterAddressSearch, #filterDobSearch').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btnExecuteSearch').click();
            }
        });
    }
});

function formatDateToInput(dateStr) {
    if (!dateStr || dateStr === '-') return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    const parts = dateStr.split('/');
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    }
    return '';
}

function openEditClientModalFromRow(wrapper) {
    if (!wrapper) return;
    const clientId = wrapper.getAttribute('data-client-id') || wrapper.dataset.clientId;
    openEditClientModal(clientId, wrapper);
}

async function openEditClientModal(clientId, wrapper = null) {
    if (!clientId) return;

    // Prefill immediately from row wrapper if available
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

    // Show modal immediately
    const modalEl = document.getElementById('editClientModal');
    if (modalEl) {
        document.body.appendChild(modalEl);
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Fetch full client details from profile API
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

            $('#editClientLoginDate').val(client.login_date_raw || (client.login_date ? formatDateToInput(client.login_date) : ''));
            $('#editClientAnp').val(client.anp || '');
            if (client.status && $('#editClientStatus option[value="' + client.status + '"]').length > 0) {
                $('#editClientStatus').val(client.status);
            }
            if (client.lead_source_id) {
                $('#editClientLeadSource').val(client.lead_source_id);
            }

            $('#editClientFirstName').val(client.first_name || '');
            $('#editClientLastName').val(client.last_name || '');
            $('#editClientDob').val(client.dob_raw || (client.dob ? formatDateToInput(client.dob) : ''));
            $('#editClientMobile').val(client.phone && client.phone !== '-' ? client.phone : '');
            $('#editClientEmail').val(client.email && client.email !== '-' ? client.email : '');

            $('#editClientAddress').val(client.address || '');
            $('#editClientSuburb').val(client.suburb || '');
            $('#editClientCity').val(client.city || '');
            $('#editClientPostCode').val(client.post_code || '');

            if (client.adviser && $('#editClientAdviser option[value="' + client.adviser + '"]').length > 0) {
                $('#editClientAdviser').val(client.adviser);
            } else if (client.adviser) {
                $('#editClientAdviser').append(new Option(client.adviser, client.adviser, true, true)).val(client.adviser);
            }

            $('#editClientNotCounting').val(client.not_counting === true || client.not_counting === 1 || client.not_counting === 'Yes' ? '1' : '0');
            $('#editClientComplianceBy').val(client.compliance_by || '');
            $('#editClientRoaDueDate').val(client.roa_due_date_raw || (client.roa_due_date ? formatDateToInput(client.roa_due_date) : ''));
            if (client.status_compliance) $('#editClientStatusCompliance').val(client.status_compliance);
            if (client.sent_to_client) $('#editClientSentToClient').val(client.sent_to_client);
            $('#editClientOutcome').val(client.outcome || '');
        }
    } catch (e) {
        console.error('Error fetching full client profile for edit:', e);
    }
}

async function handleEditClientSubmit(event) {
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

        // Close modal
        const modalEl = document.getElementById('editClientModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        btn.prop('disabled', false).html(origText);

        // Reload data table
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

        // Global notification
        if (typeof window.alert === 'function') {
            window.alert('Client profile updated successfully.');
        }
    } catch (err) {
        console.error('Error updating client:', err);
        $('#editClientAlertError').text('An unexpected network error occurred. Please try again.').removeClass('d-none');
        btn.prop('disabled', false).html(origText);
    }
}

window.openEditClientModalFromRow = openEditClientModalFromRow;
window.openEditClientModal = openEditClientModal;
window.handleEditClientSubmit = handleEditClientSubmit;

