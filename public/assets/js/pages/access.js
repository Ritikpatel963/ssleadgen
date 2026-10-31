let usersTable = null;
let rolesTable = null;

function initializeAccessTables() {
    if (!window.jQuery || !$.fn.DataTable) {
        return;
    }

    usersTable = $.fn.DataTable.isDataTable('#usersTable')
        ? $('#usersTable').DataTable()
        : $('#usersTable').DataTable({
            responsive: false,
            pageLength: 10,
            language: { search: '_INPUT_', searchPlaceholder: 'Search users...' },
        });

    rolesTable = $.fn.DataTable.isDataTable('#rolesTable')
        ? $('#rolesTable').DataTable()
        : $('#rolesTable').DataTable({
            responsive: false,
            pageLength: 10,
            language: { search: '_INPUT_', searchPlaceholder: 'Search roles...' },
        });
}

function showAccessMessage(message, type = 'success') {
    let alert = document.getElementById('accessAjaxMessage');

    if (!alert) {
        alert = document.createElement('div');
        alert.id = 'accessAjaxMessage';
        document.getElementById('accessTabs')?.before(alert);
    }

    alert.className = `alert alert-${type} alert-dismissible fade show fs-13 d-flex align-items-center gap-2`;
    alert.setAttribute('role', 'alert');
    alert.innerHTML = `<i class="feather-${type === 'success' ? 'check-circle' : 'alert-circle'} fs-5"></i><span></span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>`;
    alert.querySelector('span').textContent = message;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function submitAccessForm(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.success) {
        const error = new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to complete the request.');
        error.errors = data.errors || {};
        throw error;
    }

    return data;
}

async function refreshAccessTables() {
    const response = await fetch(window.location.href, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const page = new DOMParser().parseFromString(await response.text(), 'text/html');

    ['usersTable', 'rolesTable'].forEach((tableId) => {
        const currentTable = document.getElementById(tableId);
        const incomingTable = page.getElementById(tableId);

        if (!currentTable || !incomingTable) {
            return;
        }

        if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
            $(`#${tableId}`).DataTable().destroy();
        }

        currentTable.closest('.table-responsive').replaceWith(incomingTable.closest('.table-responsive'));
    });

    const currentModals = document.getElementById('accessModals');
    const incomingModals = page.getElementById('accessModals');

    if (currentModals && incomingModals) {
        currentModals.replaceWith(incomingModals);
    }

    initializeAccessTables();
}

async function refreshDropdownManager(url = null) {
    const container = document.getElementById('dropdownsTabContent');
    const filter = document.getElementById('dropdownGroupFilter');
    if (!container || !filter) return;

    const response = await fetch(url || filter.value, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error('Could not refresh categories.');

    if ($.fn.DataTable.isDataTable('#dropdownValuesTable')) {
        $('#dropdownValuesTable').DataTable().destroy();
    }
    container.innerHTML = await response.text();
    initializeDropdownTable();
}

function initializeDropdownTable() {
    if (!document.getElementById('dropdownValuesTable') || $.fn.DataTable.isDataTable('#dropdownValuesTable')) return;

    $('#dropdownValuesTable').DataTable({
        responsive: false,
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: 0, type: 'num' },
            { targets: 3, orderable: false }
        ],
        language: {
            search: '_INPUT_',
            searchPlaceholder: 'Search categories...',
            emptyTable: document.getElementById('dropdownGroupFilter')?.value ? 'No values have been added for this dropdown yet.' : 'Please select a Category Type from above to view values.'
        },
    });
}

function closeAccessModal(form) {
    const modalElement = form.closest('.modal');

    if (!modalElement) {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        let isClosed = false;
        const cleanup = () => {
            document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        };
        const finish = () => {
            if (!isClosed) {
                isClosed = true;
                cleanup();
                resolve();
            }
        };

        modalElement.addEventListener('hidden.bs.modal', finish, { once: true });
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
        window.setTimeout(finish, 500);
    });
}

function clearAccessFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    form.querySelectorAll('.access-form-error').forEach((message) => message.remove());
}

function showAccessFormErrors(form, error) {
    clearAccessFormErrors(form);

    const messages = Object.entries(error.errors || {}).flatMap(([field, fieldMessages]) => {
        const input = form.querySelector(`[name="${CSS.escape(field)}"]`);

        if (input) {
            input.classList.add('is-invalid');
        }

        return fieldMessages;
    });
    const message = messages[0] || error.message;
    const container = document.createElement('div');
    container.className = 'alert alert-danger fs-13 py-2 mb-3 access-form-error';
    container.setAttribute('role', 'alert');
    container.textContent = message;
    form.querySelector('.modal-body')?.prepend(container);
}

function initializeAccessPage() {
    try {
        initializeAccessTables();
        initializeDropdownTable();
    } catch (error) {
        console.error('Access Control table initialization failed:', error);
    }

    document.addEventListener('change', (event) => {
        if (event.target.id === 'dropdownGroupFilter') {
            refreshDropdownManager(event.target.value).catch(error => showAccessMessage(error.message, 'danger'));
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('form[action*="/settings/"]')) {
            return;
        }
        if (form.action.includes('/settings/dropdowns')) return;

        event.preventDefault();

        const submitButton = form.querySelector('[type="submit"]');
        const originalButtonContent = submitButton?.innerHTML;
        clearAccessFormErrors(form);

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';
        }

        try {
            const data = await submitAccessForm(form);
            await closeAccessModal(form);
            form.reset();
            await refreshAccessTables();
            showAccessMessage(data.message, data.messageType || 'success');
        } catch (error) {
            showAccessFormErrors(form, error);
            showAccessMessage(error.message, 'danger');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonContent;
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAccessPage);
} else {
    initializeAccessPage();
}
