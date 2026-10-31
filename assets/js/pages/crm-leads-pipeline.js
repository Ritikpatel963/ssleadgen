/* Page script for crm-leads-pipeline */
let leadsDataTable = null;

// Force clear lead notes local storage once to load new mock data
if (!localStorage.getItem('ss_lead_notes_loaded_v4')) {
    localStorage.removeItem('ss_lead_notes');
    localStorage.setItem('ss_lead_notes_loaded_v4', 'true');
}
let draggedKanbanCard = null;

function handleKanbanDragStart(e, el) {
    draggedKanbanCard = el;
    el.style.opacity = '0.5';
    e.dataTransfer.setData('text/plain', 'card');
}

function handleKanbanDragEnd(el) {
    el.style.opacity = '1';
    draggedKanbanCard = null;
}

function handleKanbanDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleKanbanDrop(e, columnEl) {
    e.preventDefault();
    if (draggedKanbanCard) {
        const dropzone = columnEl.querySelector('.kanban-card-dropzone');
        if (dropzone) {
            dropzone.appendChild(draggedKanbanCard);
            draggedKanbanCard.style.opacity = '1';

            const statusClass = columnEl.getAttribute('data-status-class') || 'border-primary';
            draggedKanbanCard.className = `lead-kanban-card p-3 bg-white rounded shadow-sm border-start border-4 ${statusClass}`;
            updateKanbanColumnCounts();
        }
    }
}

function updateKanbanColumnCounts() {
    $('.kanban-column-box').each(function () {
        const count = $(this).find('.lead-kanban-card').length;
        $(this).find('.kanban-count').text(`(${count})`);
    });
}

function getCurrentDateTimeString() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    const strTime = hours + ':' + minutes + ' ' + ampm;
    
    return `${day} ${month} ${year}, ${strTime}`;
}

const defaultLeadNotes = {'Evelyn Te Kuru': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $6100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Joshua Taylor': [{'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $5800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Abigail Grey': [{'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $4300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Ryan Green': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $5200/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Emily Hall': [{'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $8400/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Jacob Allen': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $5800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Elizabeth Young': [{'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $6300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Justin King': [{'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3600/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Sofia Wright': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $6700/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Benjamin Hill': [{'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $5100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Liam Scott': [{'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $8300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Lucas Green': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Mason Adams': [{'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Logan Baker': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $6300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Elijah Nelson': [{'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Oliver Carter': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3200/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Carter Mitchell': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7600/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Alexander Kumar': [{'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $2800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'James Pappula': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3000/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Benjamin Sharma': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Aria Patel': [{'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Chloe Connor': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $4300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Grace Miller': [{'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $1800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Zoey Chang': [{'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7900/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Lily Singh': [{'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $2500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Lillian Cooper': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $5500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Hannah Taylor': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Avery Walker': [{'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Layla Patel': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Brooklyn Smith': [{'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $1700/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Kishore Johnson': [{'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $3400/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}], 'Suman Williams': [{'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM'}, {'text': 'Advisor review initiated. Targeted cover is $2900/yr.', 'timestamp': '14 Aug 2026, 02:45 PM'}, {'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM'}]};

let leadNotes = {};
try {
    const stored = localStorage.getItem('ss_lead_notes');
    if (stored) {
        leadNotes = JSON.parse(stored);
    } else {
        leadNotes = defaultLeadNotes;
        localStorage.setItem('ss_lead_notes', JSON.stringify(leadNotes));
    }
} catch(e) {
    leadNotes = defaultLeadNotes;
}

async function addLeadNote(leadName, leadId) {
    const noteText = $('#newLeadNoteInput').val().trim();
    if (!noteText) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/crm/leads/${leadId}/notes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ note: noteText }),
    });

    if (!response.ok) {
        alert('Unable to save the lead note.');
        return;
    }

    const result = await response.json();
    if (!leadNotes[leadName]) {
        leadNotes[leadName] = [];
    }
    leadNotes[leadName].push(result.note);
    localStorage.setItem('ss_lead_notes', JSON.stringify(leadNotes));
    
    // Re-render notes (recent on top)
    let notesHtml = '';
    const totalNotes = leadNotes[leadName].length;
    for (let i = totalNotes - 1; i >= 0; i--) {
        const note = leadNotes[leadName][i];
        notesHtml += `
            <div class="d-flex align-items-start justify-content-between gap-2 border-bottom py-2">
                <div class="d-flex align-items-start gap-2 flex-grow-1">
                    <div class="avatar-text avatar-xs bg-soft-primary text-primary rounded-circle mt-0.5 flex-shrink-0" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">${i + 1}</div>
                    <div>
                        <div class="text-muted fs-12">${note.text}</div>
                        <div class="text-muted fs-10 mt-1"><i class="feather-clock me-1"></i>${note.timestamp}</div>
                    </div>
                </div>
                <button class="btn btn-xs btn-outline-info py-0.5 px-2 fs-10 fw-bold align-self-center flex-shrink-0" onclick="convertNoteToTask('${leadName.replace(/'/g, "\\'")}', ${i})" type="button"><i class="feather-plus me-1"></i>Convert to Task</button>
            </div>`;
    }
    $('#leadNotesContainer').html(notesHtml);
    $('#newLeadNoteInput').val('');
}

function convertNoteToTask(leadName, noteIndex) {
    const notes = leadNotes[leadName];
    if (!notes || !notes[noteIndex]) return;
    
    const noteText = notes[noteIndex].text;
    
    let tasksList = [];
    try {
        const stored = localStorage.getItem('ss_tasks');
        if (stored) {
            tasksList = JSON.parse(stored);
        } else {
            tasksList = [
                { title: 'Annual Review Call', client: 'Kishore Kumar', date: '18 Aug 2026', priority: 'High', status: 'Pending', id: 'task-status-1' },
                { title: 'Send Policy Renewal Document', client: 'Vandana Singh', date: '19 Aug 2026', priority: 'Medium', status: 'Pending', id: 'task-status-2' }
            ];
        }
    } catch(e) {
        tasksList = [];
    }
    
    const randomId = 'task-status-' + Math.floor(Math.random() * 1000);
    const today = new Date();
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const formattedDate = today.getDate() + ' ' + months[today.getMonth()] + ' ' + today.getFullYear();
    
    const newTask = {
        title: `Follow up: ${noteText}`,
        client: leadName,
        date: formattedDate,
        priority: 'High',
        status: 'Pending',
        id: randomId
    };
    
    tasksList.push(newTask);
    localStorage.setItem('ss_tasks', JSON.stringify(tasksList));
    
    let taskNotes = {};
    try {
        const storedNotes = localStorage.getItem('ss_task_notes');
        if (storedNotes) taskNotes = JSON.parse(storedNotes);
    } catch(e) {}
    
    taskNotes[newTask.title] = [`Converted from Lead Note (${leadName}): "${noteText}"`];
    localStorage.setItem('ss_task_notes', JSON.stringify(taskNotes));
    
    alert(`Successfully converted note to a task for "${leadName}"! It has been added to Tasks.`);
}

function openLeadDetailModal(name, phone, email, source, cover, stage, advisor, notes, leadId) {
    const convertForm = document.getElementById('leadConvertForm');
    if (convertForm && leadId) {
        convertForm.action = `/crm/leads/${leadId}/convert`;
    }
    let databaseNotes = [];
    try {
        databaseNotes = JSON.parse(notes || '[]');
    } catch (e) {
        databaseNotes = notes ? [{ text: notes, timestamp: '' }] : [];
    }

    if (databaseNotes.length) {
        leadNotes[name] = databaseNotes;
    } else if (!leadNotes[name]) {
        leadNotes[name] = [{ text: 'Newly added prospect lead.', timestamp: getCurrentDateTimeString() }];
        localStorage.setItem('ss_lead_notes', JSON.stringify(leadNotes));
    }

    let notesHtml = '';
    const totalNotes = leadNotes[name].length;
    for (let i = totalNotes - 1; i >= 0; i--) {
        const note = leadNotes[name][i];
        notesHtml += `
            <div class="d-flex align-items-start justify-content-between gap-2 border-bottom py-2">
                <div class="d-flex align-items-start gap-2 flex-grow-1">
                    <div class="avatar-text avatar-xs bg-soft-primary text-primary rounded-circle mt-0.5 flex-shrink-0" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">${i + 1}</div>
                    <div>
                        <div class="text-muted fs-12">${note.text}</div>
                        <div class="text-muted fs-10 mt-1"><i class="feather-clock me-1"></i>${note.timestamp}</div>
                    </div>
                </div>
                <button class="btn btn-xs btn-outline-info py-0.5 px-2 fs-10 fw-bold align-self-center flex-shrink-0" onclick="convertNoteToTask('${name.replace(/'/g, "\\'")}', ${i})" type="button"><i class="feather-plus me-1"></i>Convert to Task</button>
            </div>`;
    }

    $('#leadDetailModalTitle').html(`<i class="feather-user me-2"></i> Prospect Lead Overview: ${name}`);
    $('#leadDetailModalBody').html(`
        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">${name}</h5>
                <div class="text-muted fs-12">${phone} | ${email}</div>
            </div>
            <div>
                <span class="badge bg-primary fs-11">${stage}</span>
                <span class="badge bg-soft-info text-info fs-11 ms-1">${source}</span>
            </div>
        </div>

        <div class="row g-3 fs-13 mb-3">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-white">
                    <div class="text-muted fs-11 fw-bold text-uppercase">Target Cover Premium</div>
                    <div class="fs-16 fw-extrabold text-success mt-1">${cover}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded bg-white">
                    <div class="text-muted fs-11 fw-bold text-uppercase">Assigned Insurance Advisor</div>
                    <div class="fs-15 fw-bold text-dark mt-1">${advisor}</div>
                </div>
            </div>
        </div>

        <div class="card-widget p-3 mb-3">
            <h6 class="fw-bold text-dark mb-2"><i class="feather-file-text me-1 text-primary"></i> Advisor Underwriting Notes</h6>
            <div id="leadNotesContainer" class="d-flex flex-column gap-1 mb-3" style="max-height: 150px; overflow-y: auto; padding-right: 5px;">
                ${notesHtml}
            </div>
            <div class="d-flex flex-column gap-2 mt-2 pt-2 border-top">
                <textarea id="newLeadNoteInput" class="form-control fs-12" placeholder="Write a new note..." rows="2"></textarea>
                <button class="btn btn-primary btn-sm align-self-end fw-bold px-3 py-1.5" onclick="addLeadNote('${name.replace(/'/g, "\\'")}', ${leadId || 0})" type="button"><i class="feather-plus me-1"></i> Add Note</button>
            </div>
        </div>

        <div class="card-widget p-3">
            <h6 class="fw-bold text-dark mb-2"><i class="feather-clock me-1 text-primary"></i> Activity & Engagement History</h6>
            <div class="d-flex flex-column gap-2 fs-12">
                <div class="d-flex justify-content-between border-bottom pb-2"><span><i class="feather-check-circle text-success me-1"></i> Lead captured via ${source}</span><span class="text-muted">10 Aug 2026</span></div>
                <div class="d-flex justify-content-between border-bottom pb-2"><span><i class="feather-phone text-primary me-1"></i> Consultation Call Completed with ${advisor}</span><span class="text-muted">12 Aug 2026</span></div>
                <div class="d-flex justify-content-between"><span><i class="feather-send text-info me-1"></i> Terms illustration dispatched</span><span class="text-muted">15 Aug 2026</span></div>
            </div>
        </div>
    `);
    $('#leadDetailModal').modal('show');
}

function openLeadConvertModal() {
    const name = $('#leadDetailModalTitle').text().replace('Prospect Lead Overview: ', '').trim();
    $('#leadConvertConfirmName').text(name ? `Lead: ${name}` : '');

    const modalEl = document.getElementById('leadConvertConfirmModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
}

function confirmLeadConversion() {
    const confirmModalEl = document.getElementById('leadConvertConfirmModal');
    const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
    if (confirmModal) confirmModal.hide();
    document.getElementById('leadConvertForm').submit();
}

function switchLeadView(viewMode, btn) {
    $('#leadViewToggle button').removeClass('btn-primary').addClass('btn-outline-primary');
    $(btn).removeClass('btn-outline-primary').addClass('btn-primary');

    if (viewMode === 'kanban') {
        $('#kanbanViewContainer').removeClass('d-none');
        $('#listViewContainer').addClass('d-none');
    } else {
        $('#kanbanViewContainer').addClass('d-none');
        $('#listViewContainer').removeClass('d-none');
    }
}

function handleAddNewLead() {
    const firstName = $('#loginFirstNameInput').val().trim();
    const lastName = $('#loginLastNameInput').val().trim();
    const fullName = (firstName + ' ' + lastName).trim() || 'New Client Lead';
    const policyNo = $('#loginPolicyNoInput').val().trim() || 'POL-2026-NEW';
    const company = $('#loginCompanyInput').val() || 'AIA Health';
    const mobile = $('#loginMobileInput').val().trim() || '021 XXX XXXX';
    const email = $('#loginEmailInput').val().trim() || 'client@example.com';
    const anp = $('#loginAnpInput').val() || '3500';
    const adviser = $('#loginAdviserInput').val() || 'Sushant Yadav';
    const status = $('#loginStatusComplianceSelect').val() || 'Sent to Compliance';
    const outcome = $('#loginOutcomeInput').val().trim() || 'Newly logged client policy.';
    const coverFormatted = '$' + Number(anp).toLocaleString() + '/yr';

    let borderClass = 'primary';
    let targetDropzone = '#kanbanViewContainer > div:first-child .kanban-card-dropzone';

    if (status === 'In Review') {
        borderClass = 'info';
        targetDropzone = '#kanbanViewContainer > div:nth-child(2) .kanban-card-dropzone';
    } else if (status === 'Sent to Compliance') {
        borderClass = 'warning';
        targetDropzone = '#kanbanViewContainer > div:nth-child(3) .kanban-card-dropzone';
    } else if (status === 'Approved' || status === 'Completed') {
        borderClass = 'success';
        targetDropzone = '#kanbanViewContainer > div:nth-child(4) .kanban-card-dropzone';
    }

    $(targetDropzone).prepend(`
        <div class="lead-kanban-card p-3 bg-white rounded shadow-sm border-start border-4 border-${borderClass}" draggable="true" ondragstart="handleKanbanDragStart(event, this)" ondragend="handleKanbanDragEnd(this)" style="cursor: grab;" onclick="openLeadDetailModal('${fullName.replace(/'/g, "\'")}', '${mobile}', '${email}', '${company}', '${coverFormatted}', '${status}', '${adviser}', '${outcome.replace(/'/g, "\'")}')">
            <div class="d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark fs-13">${fullName}</div>
                <span class="badge bg-soft-${borderClass} text-${borderClass} fs-10">${company}</span>
            </div>
            <div class="text-muted fs-12 mt-1">ANP (${coverFormatted}) &bull; ${policyNo}</div>
            <div class="fs-11 text-muted mt-2 d-flex justify-content-between align-items-center"><span><i class="feather-clock me-1"></i> ${status}</span><span class="text-primary fw-semibold fs-11">Click for Details &gt;</span></div>
        </div>
    `);

    updateKanbanColumnCounts();

    // Reset form and close modal
    $('#createLeadForm')[0].reset();
    const modalEl = document.getElementById('createLeadModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function handleBatchImportLeads() {
    const format = $('#importFormatSelect').val();
    const fileInput = document.getElementById('importFileInput');
    const file = fileInput.files[0];

    if (!file) return;

    alert(`Successfully imported 24 leads using ${format} template (${file.name})!`);

    $('#importLeadsForm')[0].reset();
    const modalEl = document.getElementById('importLeadsModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    if (!$.fn.DataTable.isDataTable('#leadsDataTable')) {
        leadsDataTable = $('#leadsDataTable').DataTable({
            retrieve: true,
            responsive: true,
            pageLength: 10,
            language: { search: "_INPUT_", searchPlaceholder: "Search prospect leads..." }
        });
    }
});
