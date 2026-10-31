/* Page script for clients-inactive */
let inactiveClientsTable = null;

function openInactiveClientProfileModal(name, phone, address, policyNumber) {
    $('#inactiveProfileName').text(name || 'Client');
    $('#inactiveProfilePhone').text(phone || '-');
    $('#inactiveProfileAddress').text(address || '-');
    $('#inactiveProfilePolicy').text(policyNumber || '-');

    const modalEl = document.getElementById('inactiveClientProfileModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openInactiveClientInforceModal(clientName, company, clientId) {
    const form = $('#clientRequestForm');
    form.attr('action', form.data('action-base') + '/' + clientId + '/inforce');
    form[0].reset();

    $('#reqClientNameHeader').text(clientName || 'Client');
    $('#reqClientNameInput').val(clientName || '');
    $('#reqCompanyInput').val(company || '');
    $('#reqDateInput').val(new Date().toISOString().split('T')[0]);

    const modalEl = document.getElementById('clientRequestModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openAlreadyInactiveClientModal(clientName, company) {
    $('#alreadyInactiveClientName').text(clientName || 'Client');
    $('#alreadyInactiveClientCompany').text(company ? 'Insurance Company: ' + company : '');

    const modalEl = document.getElementById('alreadyInactiveClientModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

        function handleAddNewClient() {
            const name = $('#clientNameInput').val().trim();
            const phone = $('#clientPhoneInput').val().trim();
            const rawDob = $('#clientDobInput').val();
            const dob = rawDob ? rawDob.split('-').reverse().join('/') : '12/05/1988';
            const address = $('#clientAddressInput').val().trim() || 'Wellington, NZ';
            const status = $('#clientStatusSelect').val();
            const source = $('#clientSourceSelect').val();
            const policies = $('#clientPoliciesInput').val() || '1';

            if (!name || !phone) return;

            const statusPill = status === 'Inforce' 
                ? `<span class="status-pill-inforce">Inforce</span>`
                : `<span class="status-pill-inactive">Inactive</span>`;

            if (inactiveClientsTable) {
                inactiveClientsTable.row.add([
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
            if (!$.fn.DataTable.isDataTable('#inactiveClientsTable')) {
                inactiveClientsTable = $('#inactiveClientsTable').DataTable({
                    processing: false,
                    serverSide: true,
                    ajax: {
                        url: '/clients/inactive/data',
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
                            $('#inactiveClientCount').text('(' + json.recordsTotal.toLocaleString() + ' Profiles)');
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
                                let location = row.city || 'N/A';
                                if (row.city && row.post_code) {
                                    location += ', ' + row.post_code;
                                }
                                return '<span class="fs-13 text-muted">' + location + '</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function() {
                                return '<span class="status-pill-inactive">Inactive</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function() {
                                return '<span class="fs-13 text-muted">Login Client</span>';
                            }
                        },
                        { 
                            data: 'policy_no',
                            render: function(data) {
                                return '<span class="fs-13 fw-semibold text-dark text-center">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'updated_at',
                            orderable: false,
                            render: function(data) {
                                return '<span class="fs-13 text-muted">' + data + '</span>';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                const canWrite = $('#inactiveClientsTable').attr('data-can-write') === '1';
                                const writeActions = canWrite ? `
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openInactiveClientInforceModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-check-circle text-success me-1"></i> Move to Inforce</a>
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
                                        data-inactive-admin="${row.inactive_admin}"
                                        data-inactive-date="${row.inactive_date_raw}"
                                        data-inactive-outcome="${row.inactive_outcome}"
                                        data-inactive-comments="${row.inactive_comments}"
                                        data-inactive-new-company="${row.inactive_new_company}"
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
                        searchPlaceholder: "Search inactive clients...",
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                    }
                });

                // Handle custom filter search
                $('#btnExecuteSearch').on('click', function() {
                    inactiveClientsTable.ajax.reload();
                });

                // Handle clear filters
                $('#btnClearSearch').on('click', function() {
                    $('#filterClientSearch').val('');
                    $('#filterNumberSearch').val('');
                    $('#filterAddressSearch').val('');
                    $('#filterDobSearch').val('');
                    inactiveClientsTable.ajax.reload();
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
