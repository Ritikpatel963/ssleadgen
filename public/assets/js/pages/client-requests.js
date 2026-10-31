let clientRequestsTable = null;

function editClientServiceRequest(wrapper) {
    const form = document.getElementById('clientRequestForm');
    const data = wrapper.dataset;
    const setSelectValue = (select, value) => {
        if (!select) return;
        if (value && !Array.from(select.options).some(option => option.value === value)) {
            select.add(new Option(value, value));
        }
        select.value = value || '';
    };
    form.reset();
    form.action = data.updateUrl;
    form.dataset.moveUrl = data.moveUrl;
    document.getElementById('clientRequestMethod').value = 'PATCH';
    document.getElementById('clientRequestId').value = data.requestId;
    document.getElementById('reqClientId').value = data.clientId;
    document.getElementById('reqClientNameHeader').textContent = data.clientName;
    document.getElementById('reqDateInput').value = data.requestDate;
    document.getElementById('reqClientNameInput').value = data.clientName;
    setSelectValue(document.getElementById('reqCompanyInput'), data.company);
    setSelectValue(document.getElementById('reqTypeSelect'), data.requestType);
    setSelectValue(document.getElementById('reqProcessSelect'), data.processStatus);
    setSelectValue(document.getElementById('reqProcessBySelect'), data.processBy);
    document.getElementById('reqOutcomeInput').value = data.outcome;
    document.getElementById('reqCommentsInput').value = data.comments;
    document.getElementById('reqFinishedDateInput').value = data.finishedDate;
    document.getElementById('clientRequestSaveButton').innerHTML = '<i class="feather-save me-1"></i> Save Changes';
    const moveButton = document.getElementById('clientRequestMoveButton');
    moveButton.type = 'button';
    moveButton.onclick = moveCurrentClientRequestToInforce;
    moveButton.classList.toggle('d-none', data.clientStatus !== 'Login Client');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('clientRequestModal')).show();
}

function confirmClientRequestAction(message, action) {
    const modalElement = document.getElementById('globalConfirmModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    document.getElementById('globalConfirmMessage').textContent = message;
    $('#globalConfirmBtn').off('click.clientRequest').one('click.clientRequest', async () => {
        modal.hide();
        await action();
    });
    modal.show();
}

function resolveClientServiceRequest(wrapper) {
    confirmClientRequestAction(`Mark the request for ${wrapper.dataset.clientName} as Resolved?`, async () => {
        try {
            const response = await fetch(wrapper.dataset.resolveUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not resolve the request.');
            if (clientRequestsTable && $.fn.DataTable.isDataTable('#clientRequestsTable')) {
                clientRequestsTable.ajax.reload(null, false);
            } else {
                window.location.reload();
            }
        } catch (error) {
            alert(error.message || 'Could not resolve the request.');
        }
    });
}

function moveCurrentClientRequestToInforce() {
    const form = document.getElementById('clientRequestForm');
    confirmClientRequestAction('Move this client to Inforce?', async () => {
        const body = new FormData(form);
        body.delete('_method');
        try {
            const response = await fetch(form.dataset.moveUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body,
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not move the client to Inforce.');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('clientRequestModal')).hide();
            if (clientRequestsTable && $.fn.DataTable.isDataTable('#clientRequestsTable')) {
                clientRequestsTable.ajax.reload(null, false);
            } else {
                window.location.reload();
            }
        } catch (error) {
            alert(error.message || 'Could not move the client to Inforce.');
        }
    });
}

document.addEventListener('submit', async event => {
    const form = event.target;
    if (form.id !== 'clientRequestForm' || !form.dataset.moveUrl) return;

    event.preventDefault();
    const button = document.getElementById('clientRequestSaveButton');
    button.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new FormData(form),
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            const validation = Object.values(result.errors || {}).flat().join('\n');
            throw new Error(validation || result.message || 'Could not save the request.');
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('clientRequestModal')).hide();
        if (clientRequestsTable && $.fn.DataTable.isDataTable('#clientRequestsTable')) {
            clientRequestsTable.ajax.reload(null, false);
        } else {
            window.location.reload();
        }
    } catch (error) {
        alert(error.message || 'Could not save the request.');
    } finally {
        button.disabled = false;
    }
});

$(document).ready(function () {
    if (document.getElementById('clientRequestsTable') && !$.fn.DataTable.isDataTable('#clientRequestsTable')) {
        clientRequestsTable = $('#clientRequestsTable').DataTable({
            processing: false,
            serverSide: true,
            retrieve: true,
            responsive: false,
            pageLength: 10,
            order: [[0, 'desc']],
            ajax: {
                url: '/clients/requests/data',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    d.client_search = $('#filterClientSearch').val();
                    d.phone_search = $('#filterNumberSearch').val();
                    d.address_search = $('#filterAddressSearch').val();
                    d.dob_search = $('#filterDobSearch').val();
                },
                dataSrc: function (json) {
                    $('#clientRequestsCount').text('(' + (json.recordsTotal || 0).toLocaleString() + ')');
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'request_date',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'client_name',
                    render: function (data) {
                        return '<span class="fw-semibold text-dark fs-13">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'company',
                    render: function (data) {
                        return '<span class="fs-13 text-dark">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'request_type',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'process_status',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'status',
                    render: function (data) {
                        if (data === 'Resolved') {
                            return '<span class="badge bg-soft-success text-success">Resolved</span>';
                        }
                        return '<span class="badge bg-soft-warning text-warning">Pending</span>';
                    }
                },
                {
                    data: 'phone',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'address',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: 'dob',
                    render: function (data) {
                        return '<span class="fs-13 text-muted">' + (data || '-') + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        const canViewProfile = row.client_id ? `
                            <a href="javascript:void(0);" class="action-kebab-item" onclick="viewSharedClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                        ` : '';

                        const resolveAction = row.status === 'Pending'
                            ? `<a href="javascript:void(0);" class="action-kebab-item" onclick="resolveClientServiceRequest(this.closest('.action-kebab-wrapper'))"><i class="feather-check-circle text-primary me-1"></i> Resolve</a>`
                            : `<span class="action-kebab-item text-muted"><i class="feather-check-circle me-1"></i> Resolved</span>`;

                        return `
                            <div class="action-kebab-wrapper"
                                data-update-url="${row.update_url || ''}"
                                data-resolve-url="${row.resolve_url || ''}"
                                data-move-url="${row.move_url || ''}"
                                data-request-id="${row.id}"
                                data-client-id="${row.client_id || ''}"
                                data-request-date="${row.request_date_raw || ''}"
                                data-client-name="${(row.client_name || '').replace(/"/g, '&quot;')}"
                                data-company="${(row.company || '').replace(/"/g, '&quot;')}"
                                data-request-type="${(row.request_type || '').replace(/"/g, '&quot;')}"
                                data-process-status="${(row.process_status || '').replace(/"/g, '&quot;')}"
                                data-process-by="${(row.process_by || '').replace(/"/g, '&quot;')}"
                                data-outcome="${(row.outcome || '').replace(/"/g, '&quot;')}"
                                data-comments="${(row.comments || '').replace(/"/g, '&quot;')}"
                                data-finished-date="${row.finished_date_raw || ''}"
                                data-status="${row.status || ''}"
                                data-client-status="${row.client_status || ''}">
                                <button type="button" class="action-kebab-btn" aria-label="Client request actions"><i class="feather-more-vertical"></i></button>
                                <div class="action-kebab-dropdown">
                                    ${canViewProfile}
                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="editClientServiceRequest(this.closest('.action-kebab-wrapper'))"><i class="feather-edit text-success me-1"></i> Edit</a>
                                    ${resolveAction}
                                </div>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search client requests...',
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            }
        });

        $('#btnExecuteSearch').on('click', function () {
            clientRequestsTable.ajax.reload();
        });

        $('#btnClearSearch').on('click', function () {
            $('#filterClientSearch').val('');
            $('#filterNumberSearch').val('');
            $('#filterAddressSearch').val('');
            $('#filterDobSearch').val('');
            clientRequestsTable.ajax.reload();
        });

        $('#filterClientSearch, #filterNumberSearch, #filterAddressSearch, #filterDobSearch').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btnExecuteSearch').click();
            }
        });
    }
});
