/* Page script for clients-cancellation */
let cancellationDataTable = null;

function openAlreadyCancellationModal(clientName, company, dateSent, completedDate, outcome) {
    $('#alreadyCancellationClient').text(clientName || 'Client');
    $('#alreadyCancellationCompany').text(company || '-');
    $('#alreadyCancellationDateSent').text(dateSent || '-');
    $('#alreadyCancellationCompleted').text(completedDate || 'Pending');
    $('#alreadyCancellationOutcome').text(outcome || '-');

    const modalEl = document.getElementById('alreadyCancellationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openCancellationClientInforceModal(clientName, company, clientId) {
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

function openCancellationClientInactiveModal(clientName, clientId) {
    const form = $('#addInactiveForm');
    form.attr('action', form.data('action-base') + '/' + clientId + '/inactive');
    $('#inactiveClientNameDisplay').text(clientName || 'this client');

    const modalEl = document.getElementById('addInactiveModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function viewCancellationDetails(clientName, company, dateSent, completedDate, outcome, admin, comments) {
    $('#viewCancClientHeader').text(clientName);
    $('#viewCancClientName').val(clientName);
    $('#viewCancCompany').val(company);
    $('#viewCancDateSent').val(dateSent);
    $('#viewCancCompletedDate').val(completedDate);
    $('#viewCancOutcome').val(outcome);
    $('#viewCancAdmin').val(admin);
    $('#viewCancComments').val(comments);

    const modalEl = document.getElementById('viewCancellationModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function editCancellationDetails(clientName, company, dateSent, completedDate, outcome, admin, comments) {
    $('#cancModalTitle').text('Edit Cancellation Record');
    $('#cancClientSelect').val(clientName).trigger('change');
    if (company && $('#cancCompanySelect option[value="' + company + '"]').length > 0) {
        $('#cancCompanySelect').val(company).trigger('change');
    } else if (company) {
        $('#cancCompanySelect').append(new Option(company, company, true, true)).val(company).trigger('change');
    }
    $('#cancDateSentInput').val(dateSent.includes('/') ? dateSent.split('/').reverse().join('-') : dateSent);
    $('#cancCompletedInput').val(completedDate);
    $('#cancOutcomeInput').val(outcome);
    if (admin && $('#cancAdminSelect option[value="' + admin + '"]').length > 0) {
        $('#cancAdminSelect').val(admin).trigger('change');
    } else if (admin) {
        $('#cancAdminSelect').append(new Option(admin, admin, true, true)).val(admin).trigger('change');
    }
    $('#cancCommentsInput').val(comments);

    const modalEl = document.getElementById('addCancellationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function handleAddNewCancellation() {
    const client = $('#cancClientSelect').val() || 'Vandana Singh';
    const company = $('#cancCompanySelect').val() || 'AIA Health';
    const rawDateSent = $('#cancDateSentInput').val();
    const dateSent = rawDateSent ? rawDateSent.split('-').reverse().join('/') : '15/08/2026';
    const completedDate = $('#cancCompletedInput').val().trim() || 'Pending';
    const outcome = $('#cancOutcomeInput').val().trim() || 'Cancellation In Progress';
    const admin = $('#cancAdminSelect').val() || 'Sushant Yadav';
    const comments = $('#cancCommentsInput').val().trim() || 'Client submitted cancellation request.';

    if (!client) {
        alert('Please select a Client Name.');
        return;
    }

    let completedBadgeClass = 'bg-soft-warning text-warning';
    if (completedDate.includes('/') || completedDate === 'Completed') completedBadgeClass = 'bg-soft-success text-success';
    const completedBadge = `<span class="badge ${completedBadgeClass} fs-11">${completedDate}</span>`;

    const actionParams = `'${client}', '${company}', '${dateSent}', '${completedDate}', '${outcome}', '${admin}', '${comments}'`;

    if (cancellationDataTable) {
        cancellationDataTable.row.add([
            `<span class="fw-bold text-dark fs-13">${client}</span>`,
            `<span class="fs-13 fw-semibold text-dark">${company}</span>`,
            `<span class="fs-13 text-muted">${dateSent}</span>`,
            completedBadge,
            `<span class="fs-13 text-muted">${outcome}</span>`,
            `<span class="fs-13 text-muted">${admin}</span>`,
            `<span class="fs-13 text-muted">${comments}</span>`,
            `<div class="text-center">
                <div class="action-kebab-wrapper">
                    <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                    <div class="action-kebab-dropdown">
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="viewCancellationDetails(${actionParams})"><i class="feather-eye text-primary me-1"></i> View Details</a>
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="editCancellationDetails(${actionParams})"><i class="feather-edit text-info me-1"></i> Edit Record</a>
                    </div>
                </div>
            </div>`
        ]).draw(false);
    }

    $('#addCancellationForm')[0].reset();
    const modalEl = document.getElementById('addCancellationModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#cancellationTable')) {
        cancellationDataTable = $('#cancellationTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/clients/cancellation/data',
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
                    $('#cancellationClientCount').text('(' + json.recordsTotal.toLocaleString() + ' Records)');
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
                    data: 'company',
                    render: function(data) {
                        return '<span class="fs-13 fw-semibold text-dark">' + data + '</span>';
                    }
                },
                { 
                    data: 'canc_date_sent',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'canc_completed_date',
                    orderable: false,
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="badge bg-soft-success text-success fs-11">' + data + '</span>';
                        }
                        return '<span class="badge bg-soft-secondary text-secondary fs-11">Pending</span>';
                    }
                },
                { 
                    data: 'canc_outcome',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'canc_admin',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'canc_comments',
                    render: function(data) {
                        const truncated = data.length > 50 ? data.substring(0, 50) + '...' : data;
                        return '<span class="fs-13 text-muted">' + truncated + '</span>';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        const canWrite = $('#cancellationTable').attr('data-can-write') === '1';
                        const writeActions = canWrite ? `
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationClientInforceModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-git-pull-request text-warning me-1"></i> Inforce Clients</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModalFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-shield text-info me-1"></i> Claim Update</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationClientInactiveModal('${row.full_name}', '${row.id}')"><i class="feather-user-x text-secondary me-1"></i> Inactive Clients</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwDeferredModal('${row.full_name}', '${row.company}', '${row.id}', '${row.adviser}')"><i class="feather-clock text-purple me-1"></i> NPW Deferred</a>
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
                                data-canc-admin="${row.canc_admin}"
                                data-canc-date-sent="${row.canc_date_sent_raw}"
                                data-canc-completed-date="${row.canc_completed_date_raw}"
                                data-canc-outcome="${row.canc_outcome}"
                                data-canc-comments="${row.canc_comments}"
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
                searchPlaceholder: "Search cancellations...",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });

        // Handle custom filter search
        $('#btnExecuteSearch').on('click', function() {
            cancellationDataTable.ajax.reload();
        });

        // Handle clear filters
        $('#btnClearSearch').on('click', function() {
            $('#filterClientSearch').val('');
            $('#filterNumberSearch').val('');
            $('#filterAddressSearch').val('');
            $('#filterDobSearch').val('');
            cancellationDataTable.ajax.reload();
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
