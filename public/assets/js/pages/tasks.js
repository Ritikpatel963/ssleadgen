/* Page script for tasks */
let tasksDataTable = null;
let activeTaskNotes = [];

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}

function formatTaskDate(date) {
    if (!date) return '';
    const parsedDate = String(date).includes('T') ? new Date(date) : new Date(`${date}T00:00:00`);
    return parsedDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatTaskTime(time) {
    if (!time) return '';
    return new Date(`1970-01-01T${time}`).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function priorityBadge(priority) {
    const styles = { High: 'bg-soft-danger text-danger', Medium: 'bg-soft-primary text-primary', Low: 'bg-soft-info text-info' };
    return `<span class="badge ${styles[priority] || styles.Medium} fs-11">${escapeHtml(priority)}</span>`;
}

function taskRowCells(task) {
    const completed = task.status === 'Completed';
    const taskNotes = task.notes || [];
    const notes = encodeURIComponent(JSON.stringify(taskNotes));
    const title = escapeHtml(task.title);
    const clientName = escapeHtml(task.client_name || 'General Client');
    const taskId = Number(task.id);
    const noteCount = taskNotes.length;
    const latestNote = taskNotes[0]?.note || '';
    const time = task.due_time ? `<br><small class="text-primary"><i class="feather-clock"></i> ${formatTaskTime(task.due_time)}</small>` : '';

    return [
        `<span class="fs-13 text-muted">${clientName}</span>`,
        `<span class="fw-bold text-dark fs-13"><i class="feather-check-circle text-primary me-2"></i> ${title}</span>
            <div class="mt-1 task-note-preview-container ${noteCount ? '' : 'd-none'}">
                <a href="javascript:void(0);" class="task-notes-btn text-decoration-none" data-task-id="${taskId}" data-title="${title}" data-client-name="${clientName}" data-notes="${notes}" data-due="${formatTaskDate(task.due_date)}${task.due_time ? ' at ' + formatTaskTime(task.due_time) : ''}" data-status="${escapeHtml(task.status)}">
                    <span class="badge bg-soft-primary text-primary fs-11"><i class="feather-message-square me-1"></i><span class="task-note-count">${noteCount} ${noteCount === 1 ? 'note' : 'notes'}</span></span>
                    <span class="fs-11 text-muted ms-1 task-note-preview">${escapeHtml(latestNote.length > 45 ? `${latestNote.slice(0, 42)}...` : latestNote)}</span>
                </a>
            </div>`,
        `<span class="fs-13 text-muted">${formatTaskDate(task.due_date)}${time}</span>`,
        `<span class="task-status-cell"><span class="badge ${completed ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning'} fs-11">${completed ? 'Completed' : 'Pending'}</span></span>`,
        `<div class="text-center"><div class="action-kebab-wrapper">
            <button class="action-kebab-btn" type="button"><i class="feather-more-vertical"></i></button>
            <div class="action-kebab-dropdown">
                <form action="/utilities/tasks/${taskId}/status" method="POST" class="m-0 task-status-form">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}"><input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2"><i class="${completed ? 'feather-clock text-warning' : 'feather-check-circle text-success'} me-2"></i> Mark as ${completed ? 'Pending' : 'Completed'}</button>
                </form>
                <button type="button" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2 task-notes-btn" data-task-id="${taskId}" data-title="${escapeHtml(task.title)}" data-client-name="${clientName}" data-notes="${notes}" data-due="${formatTaskDate(task.due_date)}${task.due_time ? ' at ' + formatTaskTime(task.due_time) : ''}" data-status="${escapeHtml(task.status)}"><i class="feather-file-text text-primary me-2"></i> Task Notes</button>
                <form action="/utilities/tasks/${taskId}" method="POST" class="m-0 task-delete-form">
                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}"><input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2 text-danger"><i class="feather-trash-2 text-danger me-2"></i> Delete Task</button>
                </form>
            </div>
        </div></div>`
    ];
}

async function submitAjaxForm(form) {
    const response = await fetch(form.action, {
        method: 'POST', body: new FormData(form),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to complete the request.');
    return data;
}

function showTaskMessage(message, type = 'success') {
    let messageElement = document.getElementById('taskAjaxMessage');

    if (!messageElement) {
        messageElement = document.createElement('div');
        messageElement.id = 'taskAjaxMessage';
        const tabs = document.getElementById('taskFilterTabs');
        tabs?.parentNode.insertBefore(messageElement, tabs);
    }

    messageElement.className = `alert alert-${type} py-2 px-3 mb-2`;
    messageElement.textContent = message;
    messageElement.classList.remove('d-none');

    window.clearTimeout(messageElement.hideTimer);
    messageElement.hideTimer = window.setTimeout(() => {
        messageElement.classList.add('d-none');
    }, 3500);
}

function closeTaskModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return;

    const cleanup = () => {
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    };

    modalElement.addEventListener('hidden.bs.modal', cleanup, { once: true });
    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    window.setTimeout(cleanup, 400);
}

function confirmTaskDeletion() {
    const modalElement = document.getElementById('globalConfirmModal');
    const confirmButton = document.getElementById('globalConfirmBtn');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    document.getElementById('globalConfirmMessage').textContent = 'Delete this task?';

    return new Promise(resolve => {
        let resolved = false;
        const finish = confirmed => {
            if (resolved) return;
            resolved = true;
            resolve(confirmed);
        };

        confirmButton.addEventListener('click', () => {
            modal.hide();
            finish(true);
        }, { once: true });
        modalElement.addEventListener('hidden.bs.modal', () => finish(false), { once: true });
        modal.show();
    });
}

function renderTaskNotes(notes, searchTerm = '') {
    if (!notes || !notes.length) {
        return `
            <div class="text-center py-5 my-auto">
                <div class="avatar-text avatar-lg bg-soft-primary text-primary mx-auto mb-3 rounded-circle" style="width:48px;height:48px;display:inline-flex;align-items:center;justify-content:center;">
                    <i class="feather-file-text fs-24"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">No Notes Added Yet</h6>
                <p class="text-muted fs-12 mb-0">Use the note composer on the right to add the first update for this task.</p>
            </div>
        `;
    }

    const term = (searchTerm || '').trim().toLowerCase();
    const filteredNotes = term 
        ? notes.filter(n => (n.note || '').toLowerCase().includes(term))
        : notes;

    if (!filteredNotes.length) {
        return `
            <div class="text-center py-5 text-muted my-auto">
                <i class="feather-search fs-24 mb-2 d-block"></i>
                <span class="fs-13">No notes found matching "<strong>${escapeHtml(searchTerm)}</strong>"</span>
            </div>
        `;
    }

    return filteredNotes.map((note, idx) => {
        const noteText = note.note || '';
        const isLeadNote = noteText.includes('Converted from lead note');
        const isStatusUpdate = noteText.includes('Status updated');
        
        let typeBadge = '';
        let cardBorder = 'border';
        let iconBg = 'bg-soft-primary text-primary';
        let iconClass = 'feather-message-square';

        if (isLeadNote) {
            typeBadge = '<span class="badge bg-soft-purple text-purple fs-11 d-inline-flex align-items-center" style="padding:2px 8px;"><i class="feather-git-pull-request" style="font-size:10px;line-height:1;margin-right:4px;"></i>Lead Note</span>';
            cardBorder = 'border border-primary-subtle bg-soft-light';
            iconBg = 'bg-soft-purple text-purple';
            iconClass = 'feather-git-merge';
        } else if (isStatusUpdate) {
            typeBadge = '<span class="badge bg-soft-warning text-warning fs-11 d-inline-flex align-items-center" style="padding:2px 8px;"><i class="feather-refresh-cw" style="font-size:10px;line-height:1;margin-right:4px;"></i>Status Change</span>';
            iconBg = 'bg-soft-warning text-warning';
            iconClass = 'feather-check-circle';
        } else {
            typeBadge = '<span class="badge bg-soft-info text-info fs-11 d-inline-flex align-items-center" style="padding:2px 8px;"><i class="feather-user" style="font-size:10px;line-height:1;margin-right:4px;"></i>Task Update</span>';
        }

        const createdAt = note.created_at ? new Date(note.created_at) : new Date();
        const formattedDate = !isNaN(createdAt.getTime()) 
            ? createdAt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
            : '';
        const formattedTime = !isNaN(createdAt.getTime())
            ? createdAt.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: true })
            : '';

        let displayText = escapeHtml(noteText);

        return `
            <div class="shadow-none ${cardBorder} rounded-3 hover-shadow-sm" style="box-sizing:border-box; display:block; width:100%; height:auto; min-height:0; margin:0 0 8px; padding:12px; border:1px solid #e2e8f0 !important; background:#ffffff;">
                <div class="d-flex align-items-center justify-content-between" style="margin-bottom:6px;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="${iconBg} rounded-circle flex-shrink-0" style="width:28px;height:28px;min-width:28px;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="${iconClass}" style="font-size:12px;line-height:1;"></i>
                        </div>
                        <span class="fw-bold text-dark fs-12" style="min-width:32px;">#${notes.length - idx}</span>
                        ${typeBadge}
                    </div>
                    <span class="fs-11 text-muted d-flex align-items-center gap-1 flex-shrink-0">
                        <i class="feather-clock"></i> ${formattedDate}${formattedTime ? ' at ' + formattedTime : ''}
                    </span>
                </div>
                ${displayText ? `
                    <div class="fs-13 text-dark fw-normal" style="display:block; margin:0; padding:0; text-indent:0; white-space:pre-wrap; line-height:1.3; word-break:break-word;">${displayText}</div>
                ` : `
                    <div class="fs-12 text-muted fst-italic" style="display:block; margin:0; padding:0; text-indent:0; line-height:1.3;">(No additional text)</div>
                `}
            </div>
        `;
    }).join('');
}

function openTaskNotesModal(taskId, title, clientName, notes, extra = {}) {
    activeTaskNotes = Array.isArray(notes) ? notes : [];
    $('#taskNotesTitleHeader').text(title || 'Task Notes');
    $('#taskNotesClientSpan').text(clientName || 'General Client');
    $('#taskNotesCountBadge').text(`${activeTaskNotes.length} ${activeTaskNotes.length === 1 ? 'Note' : 'Notes'}`);
    $('#taskNotesShowingCount').text(`Showing ${activeTaskNotes.length} ${activeTaskNotes.length === 1 ? 'entry' : 'entries'}`);
    $('#taskNotesSearchInput').val('');
    $('#newTaskNoteInput').val('');

    if (extra.due) {
        $('#taskNotesDueSpan').text(extra.due);
        $('#taskNotesDueWrapper').attr('style', 'display: flex !important;');
    } else {
        $('#taskNotesDueWrapper').attr('style', 'display: none !important;');
    }

    if (extra.status) {
        const isCompleted = extra.status === 'Completed';
        $('#taskNotesStatusBadge').html(`<span class="badge ${isCompleted ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning'} fs-11">${extra.status}</span>`);
        $('#taskNotesStatusWrapper').attr('style', 'display: flex !important;');
    } else {
        $('#taskNotesStatusWrapper').attr('style', 'display: none !important;');
    }

    $('#taskNoteForm').attr('action', `/utilities/tasks/${taskId}/notes`).attr('data-task-id', taskId);
    $('#taskNotesListContainer').html(renderTaskNotes(activeTaskNotes));
     const modalDialog = $('#taskNotesModal .modal-dialog');
    modalDialog.addClass('modal-fullscreen');
    $('#toggleTaskNotesFullscreenBtn').attr('title', 'Exit Fullscreen')
        .find('i').removeClass('feather-maximize-2').addClass('feather-minimize-2');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('taskNotesModal')).show();
}

function initializeTaskPage() {
    try {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#tasksTable')) {
            tasksDataTable = $('#tasksTable').DataTable({
                responsive: false,
                pageLength: 10,
                order: [[0, 'asc']],
                orderFixed: { pre: [[0, 'asc']] },
                language: { search: '_INPUT_', searchPlaceholder: 'Search tasks...' },
                columnDefs: [{ targets: [4], searchable: false, orderable: false }],
                drawCallback: function () {
                    const rows = this.api().rows({ page: 'current' }).nodes().toArray();
                    const groups = [];

                    rows.forEach(row => {
                        const clientCell = row.cells[0];
                        clientCell.rowSpan = 1;
                        clientCell.style.display = '';

                        const clientName = clientCell.textContent.trim();
                        const lastGroup = groups[groups.length - 1];
                        if (lastGroup && lastGroup.name === clientName) {
                            lastGroup.rows.push(row);
                        } else {
                            groups.push({ name: clientName, rows: [row] });
                        }
                    });

                    groups.forEach(group => {
                        if (group.rows.length < 2) return;
                        group.rows[0].cells[0].rowSpan = group.rows.length;
                        group.rows.slice(1).forEach(row => {
                            row.cells[0].style.display = 'none';
                        });
                    });
                }
            });
        } else if ($.fn.DataTable) {
            tasksDataTable = $('#tasksTable').DataTable();
        }
    } catch (error) {
        console.error('Tasks table initialization failed:', error);
    }

    $('#newTaskForm').on('submit', async function (event) {
        event.preventDefault();
        const button = this.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = await submitAjaxForm(this);
            this.reset();
            $('#taskClientInput').val(null).trigger('change');
            closeTaskModal('addTaskModal');
            if (tasksDataTable) {
                const row = tasksDataTable.row.add(taskRowCells(data.task)).draw(false).node();
                row.dataset.taskId = data.task.id;
            }
            showTaskMessage(data.message);
        } catch (error) { showTaskMessage(error.message, 'danger'); } finally { button.disabled = false; }
    });

    $('#taskNoteForm').on('submit', async function (event) {
        event.preventDefault();
        const button = this.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = await submitAjaxForm(this);
            activeTaskNotes.unshift(data.note);
            $('#taskNotesCountBadge').text(`${activeTaskNotes.length} ${activeTaskNotes.length === 1 ? 'Note' : 'Notes'}`);
            $('#taskNotesShowingCount').text(`Showing ${activeTaskNotes.length} ${activeTaskNotes.length === 1 ? 'entry' : 'entries'}`);
            $('#taskNotesSearchInput').val('');
            $('#taskNotesListContainer').html(renderTaskNotes(activeTaskNotes));

            const taskId = this.dataset.taskId;
            document.querySelectorAll(`.task-notes-btn[data-task-id="${taskId}"]`).forEach(btn => {
                btn.dataset.notes = encodeURIComponent(JSON.stringify(activeTaskNotes));
                const count = btn.querySelector('.task-note-count');
                if (count) {
                    count.textContent = `${activeTaskNotes.length} ${activeTaskNotes.length === 1 ? 'note' : 'notes'}`;
                }
                const preview = btn.querySelector('.task-note-preview');
                if (preview) {
                    const latestNote = activeTaskNotes[0]?.note || '';
                    preview.textContent = latestNote.length > 45 ? `${latestNote.slice(0, 42)}...` : latestNote;
                    btn.closest('.task-note-preview-container')?.classList.remove('d-none');
                }
            });

            const container = document.getElementById('taskNotesListContainer');
            if (container) container.scrollTop = 0;
            this.reset();
            showTaskMessage(data.message);
        } catch (error) { 
            showTaskMessage(error.message, 'danger'); 
        } finally { 
            button.disabled = false; 
        }
    });

    $(document).on('click', '.task-notes-btn', function () {
        let notes = [];
        try {
            notes = JSON.parse(decodeURIComponent(this.dataset.notes || '%5B%5D'));
        } catch (e) {
            notes = [];
        }
        openTaskNotesModal(
            this.dataset.taskId,
            this.dataset.title,
            this.dataset.clientName,
            notes,
            {
                due: this.dataset.due || '',
                status: this.dataset.status || ''
            }
        );
    });

    $(document).on('input', '#taskNotesSearchInput', function () {
        const query = this.value;
        $('#taskNotesListContainer').html(renderTaskNotes(activeTaskNotes, query));
    });

    $(document).on('keydown', '#newTaskNoteInput', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            $('#taskNoteForm').trigger('submit');
        }
    });

    $(document).on('click', '#toggleTaskNotesFullscreenBtn', function (e) {
        e.preventDefault();
        const modalDialog = $('#taskNotesModal .modal-dialog');
        const icon = $(this).find('i');
        modalDialog.toggleClass('modal-fullscreen');
        if (modalDialog.hasClass('modal-fullscreen')) {
            icon.removeClass('feather-maximize-2').addClass('feather-minimize-2');
            this.setAttribute('title', 'Exit Fullscreen');
        } else {
            icon.removeClass('feather-minimize-2').addClass('feather-maximize-2');
            this.setAttribute('title', 'Toggle Fullscreen');
        }
    });

    $(document).on('submit', '.task-status-form', async function (event) {
        event.preventDefault();
        const form = this;
        const rowElement = form.closest('tr');
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = await submitAjaxForm(form);
            const row = tasksDataTable.row(rowElement);
            row.data(taskRowCells(data.task)).draw(false).node().dataset.taskId = data.task.id;
            showTaskMessage(data.message);
        } catch (error) { showTaskMessage(error.message, 'danger'); } finally { button.disabled = false; }
    });

    $(document).on('submit', '.task-delete-form', async function (event) {
        event.preventDefault();
        if (!await confirmTaskDeletion()) return;
        const form = this;
        const rowElement = form.closest('tr');
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const data = await submitAjaxForm(form);
            tasksDataTable.row(rowElement).remove().draw(false);
            showTaskMessage(data.message);
        } catch (error) { showTaskMessage(error.message, 'danger'); button.disabled = false; }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTaskPage);
} else {
    initializeTaskPage();
}

function filterTaskTable(filter) {
    document.querySelectorAll('#taskFilterTabs .nav-link').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.filter === filter);
    });

    if (!tasksDataTable) return;
    tasksDataTable.search('').columns().search('');
    if (filter === 'Completed' || filter === 'Pending') tasksDataTable.column(3).search(filter).draw();
    else tasksDataTable.draw();
}

document.getElementById('mobile-collapse')?.addEventListener('click', function () {
    document.getElementById('mainSidebar')?.classList.toggle('mob-navigation-active');
});
