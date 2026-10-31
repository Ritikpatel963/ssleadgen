/* Page script for claims */
let claimsDataTable = null;

function openAlreadyClaimedModal(wrapper) {
    const data = wrapper.dataset;

    $('#alreadyClaimedClientName').text(data.clientName || 'Client');
    $('#alreadyClaimedCompany').text(data.company || '-');
    $('#alreadyClaimedType').text(data.claimType || '-');
    $('#alreadyClaimedStatus').text(data.claimUpdateStatus || 'Pending');
    $('#alreadyClaimedResult').text(data.claimResult || '-');

    const modalEl = document.getElementById('alreadyClaimedModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openClaimsClientInforceModal(clientName, company, clientId) {
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

function openClaimsCancellationModal(wrapper) {
    const data = wrapper.dataset;
    const form = $('#addCancellationForm');

    form.attr('action', form.data('action-base') + '/' + data.clientId + '/cancellation');
    form[0].reset();

    $('#cancModalTitle').text('Edit Cancellation Update - ' + (data.clientName || 'Client'));
    $('#cancClientSelect').val(data.clientName || '').trigger('change');
    const comp = data.company || '';
    if (comp) {
        if ($('#cancCompanySelect option[value="' + comp + '"]').length > 0) {
            $('#cancCompanySelect').val(comp).trigger('change');
        } else {
            $('#cancCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
        }
    }
    $('#cancDateSentInput').val(data.cancDateSent || '');
    $('#cancCompletedInput').val(data.cancCompletedDate || '');
    const admin = data.cancAdmin || data.adviser || '';
    if (admin) {
        if ($('#cancAdminSelect option[value="' + admin + '"]').length === 0) {
            $('#cancAdminSelect').append(new Option(admin, admin, true, true));
        }
        $('#cancAdminSelect').val(admin).trigger('change');
    }
    $('#cancOutcomeInput').val(data.cancOutcome || '');
    $('#cancCommentsInput').val(data.cancComments || '');

    const modalEl = document.getElementById('addCancellationModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openClaimUpdateModalFromRow(wrapper) {
    const data = wrapper.dataset;
    const form = $('#lodgeClaimForm');

    form.attr('action', form.data('action-base') + '/' + data.clientId + '/claim');
    form[0].reset();

    $('#claimModalTitle').text('Edit Claim Record - ' + (data.clientName || 'Client'));
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

function viewClaimDetails(clientName, company, claimType, processedDate, updateStatus, approvedDate, outcome, admin) {
    $('#viewClaimClientHeader').text(clientName);
    $('#viewClaimClientName').val(clientName);
    $('#viewClaimCompany').val(company);
    $('#viewClaimType').val(claimType);
    $('#viewClaimProcessedDate').val(processedDate);
    $('#viewClaimUpdate').val(updateStatus);
    $('#viewClaimApprovedDate').val(approvedDate);
    $('#viewClaimOutcome').val(outcome);
    $('#viewClaimAdmin').val(admin);

    const modalEl = document.getElementById('viewClaimModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function editClaimDetails(clientName, company, claimType, processedDate, updateStatus, approvedDate, outcome, admin) {
    $('#claimModalTitle').text('Edit Claim Record');
    $('#claimClientSelect').val(clientName);
    $('#claimCompanySelect').val(company);
    $('#claimTypeInput').val(claimType);
    
    // Format date string for date inputs if needed
    $('#claimProcessedDateInput').val(processedDate.includes('/') ? processedDate.split('/').reverse().join('-') : '');
    $('#claimUpdateSelect').val(updateStatus);
    $('#claimApprovedDateInput').val(approvedDate.includes('/') ? approvedDate.split('/').reverse().join('-') : '');
    $('#claimOutcomeInput').val(outcome);
    $('#claimAdminSelect').val(admin);

    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function handleAddNewClaim() {
    const client = $('#claimClientSelect').val() || 'Kishore Kumar';
    const company = $('#claimCompanySelect').val() || 'AIA Living';
    const claimType = $('#claimTypeInput').val().trim() || 'Medical Claim';
    const rawProcessedDate = $('#claimProcessedDateInput').val();
    const processedDate = rawProcessedDate ? rawProcessedDate.split('-').reverse().join('/') : '20/08/2026';
    const updateStatus = $('#claimUpdateSelect').val() || 'Under Assessment';
    const rawApprovedDate = $('#claimApprovedDateInput').val();
    const approvedDate = rawApprovedDate ? rawApprovedDate.split('-').reverse().join('/') : 'Pending';
    const outcome = $('#claimOutcomeInput').val().trim() || 'Claim Under Assessment';
    const admin = $('#claimAdminSelect').val() || 'Sushant Yadav';

    if (!client) {
        alert('Please select a Client Name.');
        return;
    }

    let badgeClass = 'bg-soft-warning text-warning';
    if (updateStatus === 'Approved') badgeClass = 'bg-soft-success text-success';
    if (updateStatus === 'Medical Review') badgeClass = 'bg-soft-info text-info';
    if (updateStatus === 'Document Verification') badgeClass = 'bg-soft-primary text-primary';
    const updateBadge = `<span class="badge ${badgeClass} fs-11">${updateStatus}</span>`;

    const actionParams = `'${client}', '${company}', '${claimType}', '${processedDate}', '${updateStatus}', '${approvedDate}', '${outcome}', '${admin}'`;

    if (claimsDataTable) {
        claimsDataTable.row.add([
            `<span class="fw-bold text-dark fs-13">${client}</span>`,
            `<span class="fs-13 fw-semibold text-dark">${company}</span>`,
            `<span class="fs-13 text-muted">${claimType}</span>`,
            `<span class="fs-13 text-muted">${processedDate}</span>`,
            updateBadge,
            `<span class="fs-13 text-muted">${approvedDate}</span>`,
            `<span class="fs-13 text-muted">${outcome}</span>`,
            `<span class="fs-13 text-muted">${admin}</span>`,
            `<div class="text-center">
                <div class="action-kebab-wrapper">
                    <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                    <div class="action-kebab-dropdown">
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="viewClaimDetails(${actionParams})"><i class="feather-eye text-primary me-1"></i> View Details</a>
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="editClaimDetails(${actionParams})"><i class="feather-edit text-info me-1"></i> Edit Claim</a>
                    </div>
                </div>
            </div>`
        ]).draw(false);
    }

    $('#lodgeClaimForm')[0].reset();
    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#claimsTable')) {
        claimsDataTable = $('#claimsTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: '/clients/claims/data',
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
                    $('#claimsClientCount').text('(' + json.recordsTotal.toLocaleString() + ' Records)');
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
                    data: 'claim_type',
                    render: function(data) {
                        return '<span class="fs-13 text-muted text-wrap text-break" style="max-width: 200px;">' + data + '</span>';
                    }
                },
                { 
                    data: 'claim_processed_date',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'claim_update_status',
                    orderable: false,
                    render: function(data) {
                        let badgeClass = 'bg-soft-warning text-warning';
                        if (data === 'Approved') badgeClass = 'bg-soft-success text-success';
                        else if (data === 'Declined') badgeClass = 'bg-soft-danger text-danger';
                        else if (data === 'Medical Review') badgeClass = 'bg-soft-info text-info';
                        else if (data === 'Document Verification') badgeClass = 'bg-soft-primary text-primary';
                        return '<span class="badge ' + badgeClass + ' fs-11 text-wrap text-start lh-base">' + data + '</span>';
                    }
                },
                { 
                    data: 'claim_approved_date',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'claim_result',
                    render: function(data) {
                        return '<span class="fs-13 text-muted text-wrap text-break" style="max-width: 200px;">' + data + '</span>';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        const canWrite = $('#claimsTable').attr('data-can-write') === '1';
                        const writeActions = canWrite ? `
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModalFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-edit text-info me-1"></i> Edit Claim</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimsClientInforceModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-check-circle text-success me-1"></i> Move to Inforce</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimsCancellationModal(this.closest('.action-kebab-wrapper'))"><i class="feather-file-minus text-danger me-1"></i> Cancellation</a>
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
                                data-claim-type="${row.claim_type}"
                                data-claim-admin="${row.claim_admin}"
                                data-claim-processed-date="${row.claim_processed_date_raw}"
                                data-claim-update-status="${row.claim_update_status}"
                                data-claim-approved-date="${row.claim_approved_date_raw}"
                                data-claim-result="${row.claim_result}"
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
                searchPlaceholder: "Search claims...",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });

        // Handle custom filter search
        $('#btnExecuteSearch').on('click', function() {
            claimsDataTable.ajax.reload();
        });

        // Handle clear filters
        $('#btnClearSearch').on('click', function() {
            $('#filterClientSearch').val('');
            $('#filterNumberSearch').val('');
            $('#filterAddressSearch').val('');
            $('#filterDobSearch').val('');
            claimsDataTable.ajax.reload();
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
