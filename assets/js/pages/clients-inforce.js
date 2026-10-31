/* Page script for clients-inforce */
let inforceClientsTable = null;

function openInforceClientProfileModal(name, phone, address, policyNumber) {
    $('#inforceProfileName').text(name || 'Client');
    $('#inforceProfilePhone').text(phone || '-');
    $('#inforceProfileAddress').text(address || '-');
    $('#inforceProfilePolicy').text(policyNumber || '-');

    const modalEl = document.getElementById('inforceClientProfileModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openAlreadyInforceClientModal(clientName, company) {
    $('#alreadyInforceClientName').text(clientName || 'Client');
    $('#alreadyInforceClientCompany').text(company ? 'Insurance Company: ' + company : '');

    const modalEl = document.getElementById('alreadyInforceClientModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openClaimUpdateModal(clientName, company, clientId) {
    const form = $('#lodgeClaimForm');
    form.attr('action', form.data('action-base') + '/' + clientId + '/claim');
    form[0].reset();

    $('#claimModalTitle').text('New Claim Update - ' + clientName);
    $('#claimClientSelect').val(clientName || '');
    if (company && $('#claimCompanySelect option[value="' + company + '"]').length > 0) {
        $('#claimCompanySelect').val(company);
    } else if (company) {
        $('#claimCompanySelect').append(new Option(company, company, true, true)).val(company);
    }

    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openCancellationUpdateModal(clientName, company, clientId) {
    const form = $('#addCancellationForm');
    form.attr('action', form.data('action-base') + '/' + clientId + '/cancellation');
    form[0].reset();

    $('#cancModalTitle').text('New Cancellation Update - ' + clientName);
    $('#cancClientSelect').val(clientName || '');
    if (company && $('#cancCompanySelect option[value="' + company + '"]').length > 0) {
        $('#cancCompanySelect').val(company);
    } else if (company) {
        $('#cancCompanySelect').append(new Option(company, company, true, true)).val(company);
    }

    const modalEl = document.getElementById('addCancellationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
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

            if (inforceClientsTable) {
                inforceClientsTable.row.add([
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
                                <a href="javascript:void(0);" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Profile</a>
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
            if (!$.fn.DataTable.isDataTable('#inforceClientsTable')) {
                inforceClientsTable = $('#inforceClientsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/clients/inforce/data',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: function(d) {
                            // Include custom filter parameters
                            d.client_search = $('#filterClientSearch').val();
                            d.phone_search = $('#filterNumberSearch').val();
                            d.address_search = $('#filterAddressSearch').val();
                            d.dob_search = $('#filterDobSearch').val();
                        },
                        dataSrc: function(json) {
                            // Update the count in the header
                            $('#inforceClientCount').text('(' + json.recordsTotal.toLocaleString() + ' Active)');
                            return json.data;
                        }
                    },
                    columns: [
                        { 
                            data: 'full_name',
                            render: function(data, type, row) {
                                return '<span class="fw-bold text-dark fs-13">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'phone',
                            render: function(data) {
                                return '<span class="fs-13 text-muted">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'dob',
                            render: function(data) {
                                return '<span class="fs-13 text-muted">' + data + '</span>';
                            }
                        },
                        { 
                            data: null,
                            render: function(data, type, row) {
                                const location = row.city || row.address || '-';
                                return '<span class="fs-13 text-muted">' + location + '</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function() {
                                return '<span class="status-pill-inforce">Inforce</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function() {
                                return '<span class="fs-13 text-muted">Direct</span>';
                            }
                        },
                        { 
                            data: 'policy_no',
                            render: function(data) {
                                return '<span class="fs-13 fw-semibold text-dark text-center">' + data + '</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function() {
                                return '<span class="fs-13 text-muted">N/A</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                const canWrite = $('#inforceClientsTable').attr('data-can-write') === '1';
                                const writeActions = canWrite ? `
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openClientRequestModal('${row.full_name}', '${row.company}', '${row.id}', '/clients/inforce/${row.id}/requests')"><i class="feather-clipboard text-warning me-1"></i> Client Request</a>
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-shield text-info me-1"></i> Claim Update</a>
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openInactiveClientModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-user-x text-secondary me-1"></i> Inactive Clients</a>
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwDeferredModal('${row.full_name}', '${row.company}', '${row.id}', '${row.adviser}')"><i class="feather-clock text-purple me-1"></i> NPW Deferred</a>
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationUpdateModal('${row.full_name}', '${row.company}', '${row.id}', '${row.adviser}')"><i class="feather-file-minus text-danger me-1"></i> Cancellation update</a>
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
                                        data-adviser="${row.adviser}"
                                        data-policy-no="${row.policy_no}">
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
                        searchPlaceholder: "Search inforce clients...",
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                    }
                });

                // Handle custom filter search
                $('#btnExecuteSearch').on('click', function() {
                    inforceClientsTable.ajax.reload();
                });

                // Handle clear filters
                $('#btnClearSearch').on('click', function() {
                    $('#filterClientSearch').val('');
                    $('#filterNumberSearch').val('');
                    $('#filterAddressSearch').val('');
                    $('#filterDobSearch').val('');
                    inforceClientsTable.ajax.reload();
                });

                // Allow Enter key to trigger search
                $('#filterClientSearch, #filterNumberSearch, #filterAddressSearch, #filterDobSearch').on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        $('#btnExecuteSearch').click();
                    }
                });
            }
        });
