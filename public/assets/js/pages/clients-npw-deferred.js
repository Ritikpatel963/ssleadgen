/* Page script for clients-npw-deferred */
let npwDeferredDataTable = null;

function openAlreadyNpwDeferredModal(wrapper) {
    const data = wrapper.dataset;

    $('#alreadyNpwClientName').text(data.clientName || 'Client');
    $('#alreadyNpwCompany').text(data.company || '-');
    $('#alreadyNpwIssueDate').text(data.npwIssueDate || '-');
    $('#alreadyNpwPremium').text(data.npwPremium ? data.npwPremium + ' (' + (data.npwPremiumMode || 'Monthly') + ')' : '-');
    $('#alreadyNpwAdmin').text(data.npwAdmin || '-');
    $('#alreadyNpwPending').text(data.npwPending || '-');
    $('#alreadyNpwComments').text(data.npwComments || 'No comments.');

    const modalEl = document.getElementById('alreadyNpwDeferredModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function openNpwClientInforceModal(clientName, company, clientId) {
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

function openNpwCancellationModal(wrapper) {
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

function openClaimUpdateModal(clientName, company, clientId, adviser) {
    let name = clientName;
    let comp = company;
    let id = clientId;
    let adv = adviser;
    if (clientName && clientName.dataset) {
        const data = clientName.dataset;
        name = data.clientName;
        comp = data.company;
        id = data.clientId;
        adv = data.claimAdmin || data.adviser;
    }
    const form = $('#lodgeClaimForm');
    if (form.length && form.data('action-base')) {
        form.attr('action', form.data('action-base') + '/' + (id || '') + '/claim');
    }
    if (form.length) form[0].reset();

    $('#claimModalTitle').text('New Claim Update - ' + (name || 'Client'));
    $('#claimClientSelect').val(name || '').trigger('change');
    if (comp && $('#claimCompanySelect option[value="' + comp + '"]').length > 0) {
        $('#claimCompanySelect').val(comp).trigger('change');
    } else if (comp) {
        $('#claimCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
    }

    const admin = adv || '';
    if (admin && $('#claimAdminSelect').length) {
        if ($('#claimAdminSelect option[value="' + admin + '"]').length === 0) {
            $('#claimAdminSelect').append(new Option(admin, admin, true, true));
        }
        $('#claimAdminSelect').val(admin).trigger('change');
    }

    const modalEl = document.getElementById('lodgeClaimModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function viewNpwDetails(firstName, lastName, policyNo, company, dob, mobile, email, address, suburb, city, postCode, issueDate, premium, premiumMode, admin, notesComments, pending, comments) {
    $('#viewNpwClientHeader').text(firstName + ' ' + lastName);
    $('#viewNpwFirstName').val(firstName);
    $('#viewNpwLastName').val(lastName);
    $('#viewNpwPolicyNo').val(policyNo);
    $('#viewNpwCompany').val(company);
    $('#viewNpwDob').val(dob);
    $('#viewNpwMobile').val(mobile);
    $('#viewNpwEmail').val(email);
    $('#viewNpwAddress').val(address);
    $('#viewNpwSuburb').val(suburb);
    $('#viewNpwCity').val(city);
    $('#viewNpwPostCode').val(postCode);
    $('#viewNpwIssueDate').val(issueDate);
    $('#viewNpwPremium').val(premium);
    $('#viewNpwPremiumMode').val(premiumMode);
    $('#viewNpwAdmin').val(admin);
    $('#viewNpwNotesComments').val(notesComments);
    $('#viewNpwPending').val(pending);
    $('#viewNpwComments').val(comments);

    const modalEl = document.getElementById('viewNpwModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function editNpwDetails(firstName, lastName, policyNo, company, dob, mobile, email, address, suburb, city, postCode, issueDate, premium, premiumMode, admin, notesComments, pending, comments) {
    $('#npwModalTitle').text('Edit NPW Deferred Record');
    $('#npwFirstNameInput').val(firstName);
    $('#npwLastNameInput').val(lastName);
    $('#npwPolicyNoInput').val(policyNo);
    $('#npwCompanyInput').val(company);
    $('#npwDobInput').val(dob.includes('/') ? dob.split('/').reverse().join('-') : '');
    $('#npwMobileInput').val(mobile);
    $('#npwEmailInput').val(email);
    $('#npwAddressInput').val(address);
    $('#npwSuburbInput').val(suburb);
    $('#npwCityInput').val(city);
    $('#npwPostCodeInput').val(postCode);
    $('#npwIssueDateInput').val(issueDate.includes('/') ? issueDate.split('/').reverse().join('-') : '');
    $('#npwPremiumInput').val(premium.replace(/[^0-9.]/g, ''));
    $('#npwPremiumModeSelect').val(premiumMode);
    $('#npwAdminSelect').val(admin);
    $('#npwNotesCommentsInput').val(notesComments);
    $('#npwPendingSelect').val(pending);
    $('#npwCommentsInput').val(comments);

    const modalEl = document.getElementById('addNpwModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function handleAddNewNpw() {
    const firstName = $('#npwFirstNameInput').val().trim() || 'Amit';
    const lastName = $('#npwLastNameInput').val().trim() || 'Sharma';
    const policyNo = $('#npwPolicyNoInput').val().trim() || 'POL-9921';
    const company = $('#npwCompanyInput').val().trim() || 'AIA Health';
    const rawDob = $('#npwDobInput').val();
    const dob = rawDob ? rawDob.split('-').reverse().join('/') : '12/04/1987';
    const mobile = $('#npwMobileInput').val().trim() || '021 111 2222';
    const email = $('#npwEmailInput').val().trim() || 'amit.sharma@example.com';
    const address = $('#npwAddressInput').val().trim() || '12 Queen Street';
    const suburb = $('#npwSuburbInput').val().trim() || 'Auckland Central';
    const city = $('#npwCityInput').val().trim() || 'Auckland';
    const postCode = $('#npwPostCodeInput').val().trim() || '1010';
    const rawIssueDate = $('#npwIssueDateInput').val();
    const issueDate = rawIssueDate ? rawIssueDate.split('-').reverse().join('/') : '15/07/2026';
    const premiumVal = $('#npwPremiumInput').val().trim() || '150.00';
    const premium = premiumVal.startsWith('$') ? premiumVal : '$' + parseFloat(premiumVal).toFixed(2);
    const premiumMode = $('#npwPremiumModeSelect').val() || 'Monthly';
    const admin = $('#npwAdminSelect').val() || 'Sushant Yadav';
    const notesComments = $('#npwNotesCommentsInput').val().trim() || 'Deferred pending medical check.';
    const pending = $('#npwPendingSelect').val() || 'Yes';
    const comments = $('#npwCommentsInput').val().trim() || 'Follow up with insurer on Monday.';

    const actionParams = `
        '${firstName.replace(/'/g, "\\'")}',
        '${lastName.replace(/'/g, "\\'")}',
        '${policyNo.replace(/'/g, "\\'")}',
        '${company.replace(/'/g, "\\'")}',
        '${dob}',
        '${mobile.replace(/'/g, "\\'")}',
        '${email.replace(/'/g, "\\'")}',
        '${address.replace(/'/g, "\\'")}',
        '${suburb.replace(/'/g, "\\'")}',
        '${city.replace(/'/g, "\\'")}',
        '${postCode.replace(/'/g, "\\'")}',
        '${issueDate}',
        '${premium}',
        '${premiumMode}',
        '${admin.replace(/'/g, "\\'")}',
        '${notesComments.replace(/'/g, "\\'")}',
        '${pending}',
        '${comments.replace(/'/g, "\\'")}'
    `.replace(/\s+/g, ' ').trim();

    if (npwDeferredDataTable) {
        npwDeferredDataTable.row.add([
            `<span class="fw-bold text-dark fs-13">${firstName} ${lastName}</span>`,
            `<span class="fs-13 text-muted">${policyNo}</span>`,
            `<span class="fs-13 fw-semibold text-dark">${company}</span>`,
            `<span class="fs-13 text-muted">${mobile}</span>`,
            `<span class="fs-13 text-muted">${issueDate}</span>`,
            `<span class="fs-13 fw-bold text-dark">${premium} <small class="text-muted">(${premiumMode})</small></span>`,
            `<span class="fs-13 text-muted">${admin}</span>`,
            `<span class="fs-13 text-muted">${comments}</span>`,
            `<div class="text-center">
                <div class="action-kebab-wrapper">
                    <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                    <div class="action-kebab-dropdown">
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="viewNpwDetails(${actionParams})"><i class="feather-eye text-primary me-1"></i> View Details</a>
                        <a href="javascript:void(0);" class="action-kebab-item" onclick="editNpwDetails(${actionParams})"><i class="feather-edit text-info me-1"></i> Edit Record</a>
                    </div>
                </div>
            </div>`
        ]).draw(false);
    }

    $('#addNpwDeferredForm')[0].reset();
    const modalEl = document.getElementById('addNpwModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#npwDeferredTable')) {
        npwDeferredDataTable = $('#npwDeferredTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: '/clients/npw-deferred/data',
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
                    $('#npwDeferredClientCount').text('(' + json.recordsTotal.toLocaleString() + ' Records)');
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
                    data: 'policy_no',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'company',
                    render: function(data) {
                        return '<span class="fs-13 fw-semibold text-dark">' + data + '</span>';
                    }
                },
                { 
                    data: 'phone',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'npw_issue_date',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        const premium = row.npw_premium || row.anp_raw || 0;
                        const mode = row.npw_premium_mode || 'Monthly';
                        const formattedPremium = '$' + parseFloat(premium).toFixed(2);
                        return '<span class="fs-13 fw-bold text-dark">' + formattedPremium + ' <small class="text-muted">(' + mode + ')</small></span>';
                    }
                },
                { 
                    data: 'npw_admin',
                    render: function(data) {
                        return '<span class="fs-13 text-muted">' + data + '</span>';
                    }
                },
                { 
                    data: 'npw_comments',
                    render: function(data) {
                        const truncated = data.length > 50 ? data.substring(0, 50) + '...' : data;
                        return '<span class="fs-13 text-muted">' + truncated + '</span>';
                    }
                },
                { 
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        const canWrite = $('#npwDeferredTable').attr('data-can-write') === '1';
                        const writeActions = canWrite ? `
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwClientInforceModal('${row.full_name}', '${row.company}', '${row.id}')"><i class="feather-git-pull-request text-warning me-1"></i> Move to Inforce</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModal('${row.full_name}', '${row.company}', '${row.id}', '${row.adviser}')"><i class="feather-shield text-info me-1"></i> Claim Update</a>
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwCancellationModal(this.closest('.action-kebab-wrapper'))"><i class="feather-file-minus text-danger me-1"></i> Cancellation</a>
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
                                data-npw-admin="${row.npw_admin}"
                                data-npw-issue-date="${row.npw_issue_date_raw}"
                                data-npw-outcome="${row.npw_outcome}"
                                data-npw-comments="${row.npw_comments}"
                                data-npw-date-deferred="${row.npw_date_deferred_raw}"
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
            columnDefs: [
                { responsivePriority: 1, targets: -1 }, // Keep Actions column always visible
                { responsivePriority: 2, targets: 0 },  // Keep Client Name always visible
                { responsivePriority: 3, targets: 1 }   // Keep Policy No always visible
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });

        // Handle custom filter search
        $('#btnExecuteSearch').on('click', function() {
            npwDeferredDataTable.ajax.reload();
        });

        // Handle clear filters
        $('#btnClearSearch').on('click', function() {
            $('#filterClientSearch').val('');
            $('#filterNumberSearch').val('');
            $('#filterAddressSearch').val('');
            $('#filterDobSearch').val('');
            npwDeferredDataTable.ajax.reload();
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
