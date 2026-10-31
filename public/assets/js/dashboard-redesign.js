/**
 * SS Advisory Dashboard Redesign JavaScript Engine
 * Reliable SVG Donut Charts, DataTables, Preloader Auto-Dismiss, Flatpickr Date Range Filter & Tab Handlers
 */

function hidePreloader() {
    const loader = document.getElementById('preloader');
    if (loader) {
        loader.classList.add('loaded');
        setTimeout(function () {
            loader.style.display = 'none';
        }, 300);
    }
}

window.addEventListener('load', hidePreloader);
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(hidePreloader, 300);
});

var recentClientsDataTable = null;

function renderDashboardCharts() {
    const statusChartEl = document.querySelector("#clientStatusChart");
    const sourceChartEl = document.querySelector("#clientsBySourceChart");

    const data = window.dashboardData || {};
    const totalClients = data.totalClients || 0;
    const inforce = data.inforceClients || 0;
    const inactive = data.inactiveClients || 0;

    const inforcePct = totalClients > 0 ? (inforce / totalClients) : 0;
    const inactivePct = totalClients > 0 ? (inactive / totalClients) : 0;
    const inforceOffset = 238.76 * (1 - inforcePct);

    // 1. Client Status Donut Chart Render
    if (statusChartEl) {
        statusChartEl.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center py-2 w-100">
                <div class="position-relative d-flex align-items-center justify-content-center" style="width: 175px; height: 175px;">
                    <svg viewBox="0 0 100 100" class="w-100 h-100" style="transform: rotate(-90deg);">
                        <circle cx="50" cy="50" r="38" fill="transparent" stroke="#F59E0B" stroke-width="12" stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                        <circle cx="50" cy="50" r="38" fill="transparent" stroke="#10B981" stroke-width="12" stroke-dasharray="238.76" stroke-dashoffset="${inforceOffset}"></circle>
                    </svg>
                    <div class="position-absolute text-center">
                        <div class="fs-18 fw-extrabold text-dark" style="line-height: 1.1;">${totalClients.toLocaleString()}</div>
                        <div class="fs-10 text-muted fw-semibold">Total Clients</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 fs-12 mt-3 fw-bold">
                    <span class="text-success"><i class="fa fa-circle me-1" style="color:#10B981;"></i> Inforce: ${inforce.toLocaleString()} (${(inforcePct * 100).toFixed(1)}%)</span>
                    <span class="text-warning"><i class="fa fa-circle me-1" style="color:#F59E0B;"></i> Inactive: ${inactive.toLocaleString()} (${(inactivePct * 100).toFixed(1)}%)</span>
                </div>
            </div>`;
    }

    // 2. Clients by Source Donut Chart Render
    if (sourceChartEl) {
        const sources = data.sources || {};
        const colors = ['#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#94A3B8'];
        const sourceEntries = Object.entries(sources).map(([name, count]) => [name, Number(count) || 0]);
        const totalSources = sourceEntries.reduce((sum, [, count]) => sum + count, 0);
        const circumference = 238.76;
        let consumed = 0;
        const segments = sourceEntries
            .filter(([, count]) => count > 0 && totalSources > 0)
            .map(([name, count], index) => {
                const length = circumference * count / totalSources;
                const segment = `<circle cx="50" cy="50" r="38" fill="transparent" stroke="${colors[index % colors.length]}" stroke-width="12" stroke-dasharray="${length} ${circumference - length}" stroke-dashoffset="${-consumed}"></circle>`;
                consumed += length;
                return segment;
            }).join('');
        const safeLabel = value => String(value).replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
        const legend = sourceEntries.map(([name, count], index) => {
            const percent = totalSources > 0 ? (count / totalSources * 100).toFixed(1) : '0.0';
            return `<span style="color: ${colors[index % colors.length]};"><i class="fa fa-circle me-1"></i> ${safeLabel(name)}: ${count.toLocaleString()} (${percent}%)</span>`;
        }).join('');

        sourceChartEl.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center py-2 w-100">
                <div class="position-relative d-flex align-items-center justify-content-center" style="width: 175px; height: 175px;">
                    <svg viewBox="0 0 100 100" class="w-100 h-100" style="transform: rotate(-90deg);">
                        <circle cx="50" cy="50" r="38" fill="transparent" stroke="#E2E8F0" stroke-width="12" stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                        ${segments}
                    </svg>
                    <div class="position-absolute text-center">
                        <div class="fs-18 fw-extrabold text-dark" style="line-height: 1.1;">${sourceEntries.length} Sources</div>
                        <div class="fs-10 text-muted fw-semibold">Acquisition</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-2 fs-11 mt-3 fw-bold">
                    ${legend}
                </div>
            </div>`;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    renderDashboardCharts();

    const escapeHtml = value => {
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    };

    const dashboardSearchModal = document.querySelector('#dashboardSearchModal');
    if (dashboardSearchModal) {
        dashboardSearchModal.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        });
    }

    // 1. Initialize Flatpickr Date Range Picker if loaded
    const dateRangeInput = document.querySelector('#filterDateRange');
    if (dateRangeInput && typeof flatpickr !== 'undefined') {
        flatpickr(dateRangeInput, {
            mode: "range",
            dateFormat: "d M Y",
            defaultDate: ["2026-08-12", "2026-08-18"],
            onChange: function (selectedDates, dateStr) {
                if (recentClientsDataTable && selectedDates.length === 2) {
                    recentClientsDataTable.search(dateStr).draw();
                }
            }
        });
    }

    // 2. Initialize DataTables on Recent Clients Table
    const recentTableEl = document.querySelector('#recentClientsTable');
    if (recentTableEl && typeof $.fn.DataTable !== 'undefined') {
        try {
            recentClientsDataTable = $('#recentClientsTable').DataTable({
                responsive: false,
                autoWidth: false,
                pageLength: 10,
                lengthChange: false,
                searching: true,
                info: true,
                paging: true,
                dom: 't<"d-flex align-items-center justify-content-between pt-3 flex-wrap gap-2"ip>',
                order: [],
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        previous: "<",
                        next: ">"
                    }
                }
            });
        } catch (err) {
            console.log('DataTables notice:', err);
        }
    }

    // 3. Tab Filtering for Recent Clients Table
    const clientTabs = document.querySelectorAll('#recentClientsTabs button[data-bs-toggle="tab"]');
    clientTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');
            clientTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            if (recentClientsDataTable) {
                if (filter === 'all') {
                    recentClientsDataTable.column(2).search('').draw();
                } else {
                    recentClientsDataTable.column(2).search(filter).draw();
                }
            }
        });
    });

    // 4. Dashboard Search Button Handler
    const btnSearch = document.querySelector('#btnExecuteSearch');
    if (btnSearch) {
        btnSearch.addEventListener('click', function () {
            const clientVal = document.querySelector('#filterClientSearch')?.value.trim() || '';
            const phoneVal = document.querySelector('#filterNumberSearch')?.value.trim() || '';
            const addressVal = document.querySelector('#filterAddressSearch')?.value.trim() || '';
            const dobVal = document.querySelector('#filterDobSearch')?.value.split('-').reverse().join('/') || '';
            const dateRangeVal = document.querySelector('#filterDateRange')?.value || '';

            const searchTerm = [clientVal, phoneVal, addressVal, dobVal, dateRangeVal].filter(Boolean).join(' ');

            if (recentClientsDataTable) {
                recentClientsDataTable.search(searchTerm).draw();

                // Show a SweetAlert popup with the result and stage
                if (searchTerm !== '') {
                    const filteredRows = recentClientsDataTable.rows({ search: 'applied' }).nodes();
                    if (filteredRows.length > 0) {
                        let popupHtml = `<div class="table-responsive mt-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="fs-12 text-muted text-uppercase">
                                        <th>Client Name</th>
                                        <th>Mobile Number</th>
                                        <th>Email ID</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        let displayCount = Math.min(filteredRows.length, 5);
                        for (let i = 0; i < displayCount; i++) {
                            const row = filteredRows[i];
                            const name = row.cells[0].innerText.trim();
                            const phone = row.cells[1].innerText.trim();
                            const email = row.getAttribute('data-email') || '-';
                            const address = row.getAttribute('data-address') || '-';
                            const stage = row.getAttribute('data-status') || 'Unknown';

                            popupHtml += `<tr>
                                <td class="fw-bold">${escapeHtml(name)}</td>
                                <td>${escapeHtml(phone)}</td>
                                <td>${escapeHtml(email)}</td>
                                <td>${escapeHtml(address)}</td>
                                <td><span class="badge bg-info rounded-pill">${escapeHtml(stage)}</span></td>
                            </tr>`;
                        }
                        popupHtml += `</tbody></table></div>`;
                        if (filteredRows.length > 5) {
                            popupHtml += `<div class="text-center text-muted fs-12 mt-2">Showing 5 of ${filteredRows.length} results</div>`;
                        }

                        const modalEl = document.getElementById('dashboardSearchModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        document.getElementById('dashboardSearchModalBody').innerHTML = popupHtml;
                        modal.show();
                    } else {
                        const modalEl = document.getElementById('dashboardSearchModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        document.getElementById('dashboardSearchModalBody').innerHTML = `
                            <div class="text-center py-4">
                                <i class="feather-info text-muted fs-1 mb-3"></i>
                                <h5 class="fw-bold text-dark">No Results Found</h5>
                                <p class="text-muted mb-0">We could not find any leads matching your search criteria.</p>
                            </div>
                        `;
                        modal.show();
                    }
                }
            }

            btnSearch.classList.add('btn-success');
            btnSearch.innerHTML = '<i class="feather-check me-1"></i> Filtered';
            setTimeout(() => {
                btnSearch.classList.remove('btn-success');
                btnSearch.innerHTML = '<i class="feather-search me-1"></i> Search';
            }, 1200);
        });
    }

    const clearBtn = document.querySelector('#btnClearSearch');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            document.querySelectorAll('.dash-filter-input').forEach(input => input.value = '');
            if (recentClientsDataTable) {
                recentClientsDataTable.search('').columns().search('').draw();
            }
        });
    }
});

window.addEventListener('load', function () {
    renderDashboardCharts();
});
