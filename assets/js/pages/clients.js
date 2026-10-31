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
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/clients/data',
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
                            $('#allClientsCount').text('(' + json.recordsTotal.toLocaleString() + ' Profiles)');
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
                            data: 'address',
                            render: function(data) {
                                return '<span class="fs-13 text-muted">' + data + '</span>';
                            }
                        },
                        { 
                            data: 'status',
                            orderable: false,
                            render: function(data) {
                                let badge = '';
                                switch(data) {
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
                            render: function() {
                                return '<span class="fs-13 text-muted">Direct</span>';
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
                                const canWrite = $('#clientsDirectoryTable').attr('data-can-write') === '1';
                                const writeActions = canWrite ? `
                                    <a href="javascript:void(0);" class="action-kebab-item"><i class="feather-edit text-info me-1"></i> Edit Client</a>
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
                $('#btnExecuteSearch').on('click', function() {
                    clientsDataTable.ajax.reload();
                });

                // Handle clear filters
                $('#btnClearSearch').on('click', function() {
                    $('#filterClientSearch').val('');
                    $('#filterNumberSearch').val('');
                    $('#filterAddressSearch').val('');
                    $('#filterDobSearch').val('');
                    clientsDataTable.ajax.reload();
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
