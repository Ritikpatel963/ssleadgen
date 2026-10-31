/* SS Advisory - Master Interactive & Desktop Navigation Collapsible Logic */

// Suppress DataTables reinitialization warning popups globally
if (typeof $.fn.dataTable !== 'undefined') {
    $.fn.dataTable.ext.errMode = 'none';
    // Disable the processing indicator on all DataTables globally
    $.extend($.fn.dataTable.defaults, { processing: false });
}

if (typeof bootstrap !== 'undefined' && bootstrap.Modal && typeof $.fn.modal === 'undefined') {
    $.fn.modal = function (action) {
        return this.each(function () {
            const modal = bootstrap.Modal.getOrCreateInstance(this);

            if (typeof action === 'string' && typeof modal[action] === 'function') {
                modal[action]();
            }
        });
    };
}

function dismissPreloader() {
    var loader = document.getElementById('preloader');
    if (loader) {
        loader.classList.add('loaded');
        setTimeout(function () { loader.style.display = 'none'; }, 300);
    }
}

window.addEventListener('load', dismissPreloader);
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(dismissPreloader, 400);
});

$(document).ready(function () {
    if (typeof $.fn.dataTable !== 'undefined') {
        $.fn.dataTable.ext.errMode = 'none';
    }



    // Prevent jQuery animations from collapsing/opening the custom clients menu on load
    const originalSlideUp = $.fn.slideUp;
    $.fn.slideUp = function() {
        if (this.hasClass('nxl-submenu') && this.closest('.nxl-custom-hasmenu').length) {
            return this.css('display', 'none');
        }
        return originalSlideUp.apply(this, arguments);
    };

    const originalSlideDown = $.fn.slideDown;
    $.fn.slideDown = function() {
        if (this.hasClass('nxl-submenu') && this.closest('.nxl-custom-hasmenu').length) {
            return this.css('display', 'block');
        }
        return originalSlideDown.apply(this, arguments);
    };

    // Function to sync submenu display with sidebar collapse state
    function syncSidebarSubmenu() {
        $('.nxl-custom-hasmenu .nxl-submenu').each(function () {
            const parentMenu = $(this).closest('.nxl-custom-hasmenu');
            const shouldShow = !$('body').hasClass('nxl-mini-navigation') && parentMenu.hasClass('menu-open');

            // Set explicit display block/none without replacing the entire style attribute
            if (shouldShow) {
                $(this).css('display', 'block');
                this.style.setProperty('display', 'block', 'important');
            } else {
                $(this).css('display', 'none');
                this.style.setProperty('display', 'none', 'important');
            }
        });
    }

    // Open the first custom dropdown by default when the page loads.
    $('.nxl-custom-hasmenu').each(function () {
        const $menu = $(this);
        const hasActiveChild = $menu.find('.nxl-item.active').length > 0;
        if (hasActiveChild || ($('.nxl-custom-hasmenu').length === 1 && !$menu.hasClass('menu-open'))) {
            $menu.addClass('menu-open nxl-trigger');
        }
    });

    // Initial check on load
    syncSidebarSubmenu();

    $(document).on('click', '.nxl-custom-hasmenu > .nxl-link', function (e) {
        e.preventDefault();
        $(this).closest('.nxl-custom-hasmenu').toggleClass('menu-open nxl-trigger');
        syncSidebarSubmenu();
    });

    // Single Universal Navigation Toggle Handler across Desktop & Mobile
    $(document).on('click', '#menu-mini-button, #mobile-collapse', function (e) {
        e.preventDefault();
        if ($(window).width() >= 992) {
            $('body').toggleClass('nxl-mini-navigation');
            localStorage.setItem('nxl-mini-navigation', $('body').hasClass('nxl-mini-navigation'));
            syncSidebarSubmenu();
        } else {
            $('body').toggleClass('nxl-navigation-active');
            $('#mainSidebar, .nxl-navigation').toggleClass('mob-navigation-active');
        }
    });

    // Dismiss mobile drawer on overlay / outer click
    $(document).on('click', function (e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('.nxl-navigation, #menu-mini-button, #mobile-collapse').length) {
                $('body').removeClass('nxl-navigation-active');
                $('#mainSidebar, .nxl-navigation').removeClass('mob-navigation-active');
            }
        }
    });

    // Safe Global Datatables Auto-Initialization
    $('.table').each(function () {
        if (this.id && this.id !== 'recentClientsTable' && this.id !== 'tasksTable' && this.id !== 'leadsMappingTable' && this.id !== 'leadsPreviewTable' && !$(this).is('[data-server-paginated]') && typeof $.fn.DataTable !== 'undefined') {
            if (!$.fn.DataTable.isDataTable('#' + this.id)) {
                let tableObj = $(this);
                tableObj.DataTable({
                    retrieve: true,
                    responsive: false,
                    pageLength: 10,
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search records..."
                    }
                });
            }
        }
    });

    // Auto-attach green + expand buttons to table rows for mobile view
    setupTableExpandButtons();
    formatDataTablesHeader();
    setTimeout(formatDataTablesHeader, 300);

    // Universal Top Filter Search & Clear Handler
    $(document).on('click', '#btnExecuteSearch', function (e) {
        e.preventDefault();
        const clientVal = $('#filterClientSearch').length && $('#filterClientSearch').val() ? $('#filterClientSearch').val().trim() : '';
        const phoneVal = $('#filterNumberSearch').length && $('#filterNumberSearch').val() ? $('#filterNumberSearch').val().trim() : '';
        const addressVal = $('#filterAddressSearch').length && $('#filterAddressSearch').val() ? $('#filterAddressSearch').val().trim() : '';
        const dobVal = $('#filterDobSearch').length && $('#filterDobSearch').val()
            ? $('#filterDobSearch').val().trim().split('-').reverse().join('/')
            : '';

        const searchTerm = [clientVal, phoneVal, addressVal, dobVal].filter(Boolean).join(' ');

        // Search active DataTable on page
        $('.table').each(function () {
            if (this.id && typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#' + this.id)) {
                const dt = $('#' + this.id).DataTable();
                if (dt.page.info().serverSide) {
                    dt.ajax.reload();
                } else {
                    dt.search(searchTerm).draw();
                }
            }
        });

        const $btn = $(this);
        $btn.addClass('btn-success');
        $btn.html('<i class="feather-check me-1"></i> Filtered');
        setTimeout(function () {
            $btn.removeClass('btn-success');
            $btn.html('<i class="feather-search me-1"></i> Search');
        }, 1200);
    });

    $(document).on('click', '#btnClearSearch', function (e) {
        e.preventDefault();
        $('.dash-filter-input').val('');
        $('#filterClientSearch, #filterNumberSearch, #filterAddressSearch, #filterDobSearch').val('');
        $('.table').each(function () {
            if (this.id && typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#' + this.id)) {
                const dt = $('#' + this.id).DataTable();
                if (dt.page.info().serverSide) {
                    dt.search('').columns().search('').ajax.reload();
                } else {
                    dt.search('').columns().search('').draw();
                }
            }
        });
    });

    // Also support pressing Enter inside any .dash-filter-input
    $(document).on('keypress', '.dash-filter-input', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btnExecuteSearch').click();
        }
    });
});

function formatDataTablesHeader() {
    $('.dataTables_wrapper').each(function () {
        let wrapper = $(this);
        let lengthEl = wrapper.find('.dataTables_length');
        let filterEl = wrapper.find('.dataTables_filter');

        if (lengthEl.length && filterEl.length && !wrapper.find('.dt-mobile-header-row').length) {
            let headerRow = $('<div class="dt-mobile-header-row"></div>');
            lengthEl.before(headerRow);
            headerRow.append(lengthEl).append(filterEl);
        }
    });
}

// Universal Mobile Expandable Table Row Toggle Engine (Actions column stays visible on screen!)
$(document).on('click', '.table-expand-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    let btn = $(this);
    let tr = btn.closest('tr');
    let table = tr.closest('table');
    let colHeaders = table.find('thead th');

    if (tr.next('.table-child-row').length) {
        tr.next('.table-child-row').remove();
        btn.removeClass('expanded').text('+');
    } else {
        let cells = tr.children('td');
        let detailsHtml = '<div class="table-child-content">';

        cells.each(function (idx) {
            // Skip first cell (Name) and last cell (Actions) so Actions stays ON SCREEN!
            if (idx === 0 || idx === cells.length - 1) return;

            let label = $(colHeaders[idx]).text().trim();
            let val = $(this).html();
            if (label && val) {
                detailsHtml += `
                    <div class="table-child-item">
                        <span class="table-child-label">${label}:</span>
                        <span class="table-child-val">${val}</span>
                    </div>`;
            }
        });
        detailsHtml += '</div>';

        let colCount = cells.length;
        let childRow = `<tr class="table-child-row"><td colspan="${colCount}">${detailsHtml}</td></tr>`;
        tr.after(childRow);
        btn.addClass('expanded').text('×');
    }
});

function setupTableExpandButtons() {
    $('.table tbody tr').each(function () {
        let tr = $(this);
        if (!tr.hasClass('table-child-row') && !tr.find('.table-expand-btn').length) {
            let firstTd = tr.find('td').first();
            if (firstTd.length) {
                let currentContent = firstTd.html();
                firstTd.html(`<div class="table-cell-title-wrap"><button type="button" class="table-expand-btn" title="Toggle Extra Details">+</button><div>${currentContent}</div></div>`);
            }
        }
    });
}

function openNotesDrawer(idOrName) {
    let targetName = typeof idOrName === 'string' ? idOrName : 'Client Details';
    if (typeof idOrName === 'number' && typeof insuranceLeads !== 'undefined') {
        let found = insuranceLeads.find(l => l.id === idOrName);
        if (found) targetName = found.name;
    } else if (typeof idOrName === 'string' && typeof insuranceLeads !== 'undefined') {
        let found = insuranceLeads.find(l => l.name.toLowerCase() === idOrName.toLowerCase());
        if (found) targetName = found.name;
    }

    if ($('#drawerLeadName').length) {
        $('#drawerLeadName').text(targetName);
    }

    let drawer = $('#notesDrawerBackdrop');
    if (drawer.length) {
        drawer.addClass('show').css({ 'display': 'flex', 'opacity': '1' });
    }

    let modalEl = document.getElementById('addNotesModal') || document.getElementById('notesModal');
    if (modalEl) {
        var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        bsModal.show();
    }
}

function closeNotesDrawer() {
    let drawer = $('#notesDrawerBackdrop');
    if (drawer.length) {
        drawer.removeClass('show').css({ 'display': 'none', 'opacity': '0' });
    }
}

// Action Kebab Menu Click Toggle Handler (Desktop & Mobile)
$(document).on('click', '.action-kebab-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    let wrapper = $(this).closest('.action-kebab-wrapper');
    let dropdown = wrapper.find('.action-kebab-dropdown');
    let isOpen = dropdown.hasClass('show');

    // Close any currently open kebab dropdowns
    $('.action-kebab-dropdown').removeClass('show').css({ 'position': '', 'top': '', 'left': '', 'right': '', 'bottom': '' });
    $('.action-kebab-wrapper').removeClass('show');

    // Toggle current dropdown if it wasn't open
    if (!isOpen) {
        dropdown.addClass('show');
        wrapper.addClass('show');
        
        // Prevent clipping by containers with overflow: hidden/auto (like table-responsive)
        let rect = this.getBoundingClientRect();
        
        // Force layout recalculation or fallback to a default width (150px)
        let dropWidth = dropdown.outerWidth();
        if (dropWidth === 0) dropWidth = 150; 
        
        dropdown.css({
            'position': 'fixed',
            'top': (rect.bottom + 4) + 'px',
            'left': (rect.right - dropWidth) + 'px',
            'bottom': 'auto',
            'right': 'auto',
            'z-index': '999999'
        });
    }
});

// Close open dropdowns when scrolling to prevent floating detached menus
document.addEventListener('scroll', function(e) {
    if ($('.action-kebab-dropdown.show').length > 0) {
        // Only close if scrolling something other than the dropdown itself
        if (!$(e.target).closest('.action-kebab-dropdown').length) {
            $('.action-kebab-dropdown').removeClass('show').css({ 'position': '', 'top': '', 'left': '', 'right': '', 'bottom': '' });
            $('.action-kebab-wrapper').removeClass('show');
        }
    }
}, true);

// Close dropdown when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('.action-kebab-wrapper').length) {
        $('.action-kebab-dropdown').removeClass('show').css({ 'position': '', 'top': '', 'left': '', 'right': '', 'bottom': '' });
        $('.action-kebab-wrapper').removeClass('show');
    }
});

// Close dropdown when an item inside is clicked
$(document).on('click', '.action-kebab-item', function () {
    $('.action-kebab-dropdown').removeClass('show').css({ 'position': '', 'top': '', 'left': '', 'right': '', 'bottom': '' });
    $('.action-kebab-wrapper').removeClass('show');
});

/* Global Helper Functions for Common Kebab Modals */
function openClientRequestModal(clientName, company) {
    $('#reqClientNameHeader').text(clientName || 'Client');
    $('#reqClientNameInput').val(clientName || '');
    $('#reqCompanyInput').val(company || 'AIA Health');
    const today = new Date().toISOString().split('T')[0];
    $('#reqDateInput').val(today);
    $('#reqFinishedDateInput').val('');
    $('#reqOutcomeInput').val('');
    $('#reqCommentsInput').val('');
    
    const modalEl = document.getElementById('clientRequestModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
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
    $('#claimModalTitle').text('New Claim Update - ' + (name || 'Client'));
    if ($('#claimClientSelect').length) $('#claimClientSelect').val(name || '').trigger('change');
    if ($('#claimClientNameDisplay').length) $('#claimClientNameDisplay').val(name || '').trigger('change');
    if ($('#claimCompanySelect').length) {
        if (comp && $('#claimCompanySelect option[value="' + comp + '"]').length > 0) {
            $('#claimCompanySelect').val(comp).trigger('change');
        } else if (comp) {
            $('#claimCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
        }
    }
    if ($('#claimCompanyDisplay').length) $('#claimCompanyDisplay').val(comp || '').trigger('change');
    if ($('#claimClientId').length) $('#claimClientId').val(id || '');
    const admin = adv || '';
    if ($('#claimAdminSelect').length) {
        $('#claimAdminSelect').val(admin);
        if (admin && $('#claimAdminSelect').val() !== admin) {
            $('#claimAdminSelect').append(new Option(admin, admin, true, true)).val(admin);
        }
        $('#claimAdminSelect').trigger('change');
    }
    const modalEl = document.getElementById('lodgeClaimModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openCancellationUpdateModal(clientName, company, clientId, adviser) {
    let name = clientName;
    let comp = company;
    let id = clientId;
    let adv = adviser;
    if (clientName && clientName.dataset) {
        const data = clientName.dataset;
        name = data.clientName;
        comp = data.company;
        id = data.clientId;
        adv = data.cancAdmin || data.adviser;
    }
    $('#cancModalTitle').text('New Cancellation Update - ' + (name || 'Client'));
    if ($('#cancClientSelect').length) $('#cancClientSelect').val(name || '').trigger('change');
    if ($('#cancClientNameDisplay').length) $('#cancClientNameDisplay').val(name || '').trigger('change');
    
    if ($('#cancCompanySelect').length) {
        if (comp && $('#cancCompanySelect option[value="' + comp + '"]').length > 0) {
            $('#cancCompanySelect').val(comp).trigger('change');
        } else if (comp) {
            $('#cancCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
        }
    }
    if ($('#cancCompanyDisplay').length) {
        if (comp && $('#cancCompanyDisplay option[value="' + comp + '"]').length > 0) {
            $('#cancCompanyDisplay').val(comp).trigger('change');
        } else if (comp) {
            $('#cancCompanyDisplay').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
        }
    }
    if (id && $('#addCancellationForm').length && $('#addCancellationForm').data('action-base')) {
        $('#addCancellationForm').attr('action', $('#addCancellationForm').data('action-base') + '/' + id + '/cancellation');
    }
    if (adv && $('#cancAdminSelect').length) {
        if ($('#cancAdminSelect option[value="' + adv + '"]').length === 0) {
            $('#cancAdminSelect').append(new Option(adv, adv, true, true));
        }
        $('#cancAdminSelect').val(adv).trigger('change');
    }
    const modalEl = document.getElementById('addCancellationModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function openNpwDeferredModal(clientName, company, clientId, adviser) {
    let name = clientName;
    let comp = company;
    let id = clientId;
    let adv = adviser;
    if (clientName && clientName.dataset) {
        const data = clientName.dataset;
        name = data.clientName;
        comp = data.company;
        id = data.clientId;
        adv = data.npwAdmin || data.adviser;
    }
    $('#npwModalTitle').text('New NPW Deferred - ' + (name || 'Client'));
    if ($('#npwClientNameDisplay').length) $('#npwClientNameDisplay').val(name || '').trigger('change');
    if ($('#npwClientSelect').length) $('#npwClientSelect').val(name || '').trigger('change');
    if ($('#npwClientInput').length) $('#npwClientInput').val(name || '').trigger('change');

    if (comp) {
        if ($('#npwCompanyDisplay').length) {
            if ($('#npwCompanyDisplay option[value="' + comp + '"]').length > 0) {
                $('#npwCompanyDisplay').val(comp).trigger('change');
            } else {
                $('#npwCompanyDisplay').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
            }
        }
        if ($('#npwCompanySelect').length) {
            if ($('#npwCompanySelect option[value="' + comp + '"]').length > 0) {
                $('#npwCompanySelect').val(comp).trigger('change');
            } else {
                $('#npwCompanySelect').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
            }
        }
        if ($('#npwCompanyInput').length) {
            if ($('#npwCompanyInput option[value="' + comp + '"]').length > 0) {
                $('#npwCompanyInput').val(comp).trigger('change');
            } else {
                $('#npwCompanyInput').append(new Option(comp, comp, true, true)).val(comp).trigger('change');
            }
        }
    }
    if (id) {
        const form = $('#addNpwForm');
        if (form.length && form.data('action-base')) {
            form.attr('action', form.data('action-base') + '/' + id + '/npw-deferred');
        } else if (form.length) {
            form.attr('action', window.location.origin + '/clients/login/' + id + '/npw-deferred');
        }
    }
    if (adv && $('#npwAdminSelect').length) {
        if ($('#npwAdminSelect option[value="' + adv + '"]').length === 0) {
            $('#npwAdminSelect').append(new Option(adv, adv, true, true));
        }
        $('#npwAdminSelect').val(adv).trigger('change');
    }
    const modalEl = document.getElementById('addNpwModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function handleSaveClientRequest() {
    const name = $('#reqClientNameInput').val() || 'Client';
    const requestType = $('#reqTypeSelect').val() || 'Service Request';
    alert(`Client Request "${requestType}" for ${name} saved successfully!`);
    
    const modalEl = document.getElementById('clientRequestModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    if ($('#clientRequestForm').length) $('#clientRequestForm')[0].reset();
}

function handleAddNewClaim() {
    const client = $('#claimClientSelect').val() || 'Client';
    alert(`Claim Update for ${client} logged successfully!`);

    if ($('#lodgeClaimForm').length) $('#lodgeClaimForm')[0].reset();
    const modalEl = document.getElementById('lodgeClaimModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}

function handleAddNewCancellation() {
    const client = $('#cancClientSelect').val() || 'Client';
    alert(`Cancellation Update for ${client} logged successfully!`);

    if ($('#addCancellationForm').length) $('#addCancellationForm')[0].reset();
    const modalEl = document.getElementById('addCancellationModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}
