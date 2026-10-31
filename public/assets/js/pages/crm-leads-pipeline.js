/* Page script for crm-leads-pipeline */
let leadsDataTable = null;
let currentLeadImportPath = null;
let currentLeadImportSheets = [];
let currentLeadImportDbFields = {};
let currentLeadPreviewOffset = 0;
const currentLeadPreviewLimit = 25;
let kanbanLeadsLoaded = false;

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

async function handleKanbanDrop(e, columnEl) {
    e.preventDefault();
    if (!draggedKanbanCard) return;

    const card = draggedKanbanCard;
    const dropzone = columnEl.querySelector('.kanban-card-dropzone');
    if (!dropzone) return;

    const newStatus = columnEl.getAttribute('data-status');
    const leadId = card.getAttribute('data-lead-id');
    const previousStatus = card.closest('.card-widget-kanban')?.getAttribute('data-status');
    if (!leadId || !newStatus || newStatus === previousStatus) return;

    const previousDropzone = card.parentElement;
    const previousNextSibling = card.nextElementSibling;
    const previousClassName = card.className;
    const statusClass = columnEl.closest('.kanban-column-box').getAttribute('data-status-class') || 'border-primary';

    dropzone.prepend(card);
    card.style.opacity = '1';
    card.className = `lead-kanban-card border-start border-4 ${statusClass}`;
    updateKanbanColumnCounts();

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`/crm/leads/${leadId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ status: newStatus }),
        });
        if (!response.ok) throw new Error('Unable to save the lead stage.');
    } catch (error) {
        if (previousNextSibling?.parentElement === previousDropzone) {
            previousDropzone.insertBefore(card, previousNextSibling);
        } else {
            previousDropzone.appendChild(card);
        }
        card.className = previousClassName;
        updateKanbanColumnCounts();
        window.alert('The lead stage could not be saved. Please try again.');
    }
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

const defaultLeadNotes = { 'Evelyn Te Kuru': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $6100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Joshua Taylor': [{ 'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $5800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Abigail Grey': [{ 'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $4300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Ryan Green': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $5200/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Emily Hall': [{ 'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $8400/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Jacob Allen': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $5800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Elizabeth Young': [{ 'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $6300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Justin King': [{ 'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3600/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Sofia Wright': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $6700/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Benjamin Hill': [{ 'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $5100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Liam Scott': [{ 'text': 'Captured from source: Existing Client.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $8300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Lucas Green': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Mason Adams': [{ 'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7100/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Logan Baker': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $6300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Elijah Nelson': [{ 'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Oliver Carter': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3200/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Carter Mitchell': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7600/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Alexander Kumar': [{ 'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $2800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'James Pappula': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3000/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Benjamin Sharma': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Aria Patel': [{ 'text': 'Captured from source: Meta Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Chloe Connor': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $4300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Grace Miller': [{ 'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $1800/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Zoey Chang': [{ 'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7900/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Lily Singh': [{ 'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $2500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Lillian Cooper': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $5500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Hannah Taylor': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3300/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Avery Walker': [{ 'text': 'Captured from source: Facebook Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Layla Patel': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $7500/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Brooklyn Smith': [{ 'text': 'Captured from source: Referral.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $1700/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Kishore Johnson': [{ 'text': 'Captured from source: Door to Door.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $3400/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }], 'Suman Williams': [{ 'text': 'Captured from source: Google Ads.', 'timestamp': '12 Aug 2026, 09:30 AM' }, { 'text': 'Advisor review initiated. Targeted cover is $2900/yr.', 'timestamp': '14 Aug 2026, 02:45 PM' }, { 'text': 'Consultation reminder sent via email.', 'timestamp': '16 Aug 2026, 11:00 AM' }] };

let leadNotes = {};
try {
    const stored = localStorage.getItem('ss_lead_notes');
    if (stored) {
        leadNotes = JSON.parse(stored);
    } else {
        leadNotes = defaultLeadNotes;
        localStorage.setItem('ss_lead_notes', JSON.stringify(leadNotes));
    }
} catch (e) {
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
        const noteNum = totalNotes - i;
        notesHtml += `
            <div class="p-3 shadow-none border rounded-3 mb-2" style="flex-shrink:0; border: 1px solid #e2e8f0 !important; background: #ffffff; overflow: visible;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-soft-primary text-primary rounded-circle fw-bold flex-shrink-0" style="width:22px;height:22px;min-width:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">
                            <i class="feather-file-text" style="line-height:1;"></i>
                        </div>
                        <span class="fw-bold text-dark fs-12">#${noteNum}</span>
                        <span class="badge bg-soft-primary text-primary fs-11 px-2 d-inline-flex align-items-center" style="padding-top:2px;padding-bottom:2px;"><i class="feather-edit-2 me-1"></i>Lead Note</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="fs-11 text-muted d-flex align-items-center gap-1"><i class="feather-clock" style="line-height:1;"></i> ${note.timestamp || ''}</span>
                        <button class="btn btn-outline-info btn-sm fw-bold flex-shrink-0 js-convert-note-to-task" data-lead-name="${escapeHtml(leadName)}" data-note-index="${i}" data-lead-id="${leadId}" style="font-size:10px;padding:2px 8px;" type="button"><i class="feather-plus me-1"></i>Convert to Task</button>
                    </div>
                </div>
                <div class="fs-13 text-dark fw-normal" style="white-space: pre-wrap; line-height: 1.6; word-break: break-word;">${note.text || ''}</div>
            </div>`;
    }
    $('#leadNotesContainer').html(notesHtml);
    $('#newLeadNoteInput').val('');
}

// async function convertNoteToTask(leadName, noteIndex, leadId) {
//     const notes = leadNotes[leadName];
//     if (!notes || !notes[noteIndex] || !leadId) return;

//     const noteText = notes[noteIndex].text;

//     const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     const dueDate = new Date().toISOString().slice(0, 10);
//     try {
//         const response = await fetch('/utilities/tasks', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'Accept': 'application/json',
//                 'X-Requested-With': 'XMLHttpRequest',
//                 'X-CSRF-TOKEN': csrfToken,
//             },
//             body: JSON.stringify({
//                 title: `Follow up: ${noteText}`.slice(0, 255),
//                 client_name: leadName,
//                 due_date: dueDate,
//                 priority: 'High',
//                 note: `Converted from lead note (${leadName}): "${noteText}"`,
//             }),
//         });

//         let result = {};
//         try {
//             result = await response.json();
//         } catch (error) {
//             // Keep the generic error below for non-JSON responses.
//         }

//         if (!response.ok) {
//             const validationMessage = result.errors ? Object.values(result.errors).flat()[0] : null;
//             throw new Error(validationMessage || result.message || 'Unable to convert the note to a task.');
//         }

//         alert(`Successfully converted note to a task for "${leadName}"! It has been added to Tasks.`);
//     } catch (error) {
//         alert(error.message || 'Unable to convert the note to a task.');
//     }
// }
// Remembers which lead notes have already been converted (or are being converted)
const convertedNotes = new Set();

async function convertNoteToTask(leadName, noteIndex, leadId) {
    const notes = leadNotes[leadName];
    if (!notes || !notes[noteIndex] || !leadId) return;

    // Block repeat clicks: one task per lead note
    const convertKey = `${leadId}:${noteIndex}`;
    if (convertedNotes.has(convertKey)) {
        alert('This note has already been converted to a task.');
        return;
    }
    convertedNotes.add(convertKey);

    const noteText = notes[noteIndex].text;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Local date (toISOString() uses UTC and can return yesterday's date in India)
    const today = new Date();
    const dueDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    try {
        const response = await fetch('/utilities/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                title: `Follow up: ${noteText}`.slice(0, 255),
                client_name: leadName,
                due_date: dueDate,
                priority: 'High',
                note: `Converted from lead note (${leadName}): "${noteText}"`,
            }),
        });

        let result = {};
        try {
            result = await response.json();
        } catch (error) {
            // Keep the generic error below for non-JSON responses.
        }

        if (!response.ok) {
            const validationMessage = result.errors ? Object.values(result.errors).flat()[0] : null;
            throw new Error(validationMessage || result.message || 'Unable to convert the note to a task.');
        }

        alert(`Successfully converted note to a task for "${leadName}"! It has been added to Tasks.`);
    } catch (error) {
        // Allow a retry only if the request actually failed
        convertedNotes.delete(convertKey);
        alert(error.message || 'Unable to convert the note to a task.');
    }
}
function openLeadDetailModal(name, phone, email, source, cover, stage, advisor, notes, leadId, leadStatus) {
    window.currentViewingLeadId = leadId;
    const phoneValue = String(phone || '').trim();
    const emailValue = String(email || '').trim();
    const contactDetails = [
        phoneValue ? `<div>Phone number: <a href="tel:${phoneValue.replace(/[^0-9+]/g, '')}">${escapeHtml(phoneValue)}</a></div>` : '',
        emailValue ? `<div>Email ID: <a href="mailto:${escapeHtml(emailValue)}">${escapeHtml(emailValue)}</a></div>` : '',
    ].filter(Boolean).join('');

    // Wire the convert form in the footer
    const convertForm = document.getElementById('leadConvertForm');
    const convertBtn = document.getElementById('leadConvertBtn');
    if (convertForm) {
        convertForm.action = `/crm/leads/${leadId}/convert`;
        if (convertBtn) {
            convertBtn.style.display = leadStatus === 'won' ? 'none' : '';
        }
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
        const noteNum = totalNotes - i;
        notesHtml += `
            <div class="p-3 shadow-none border rounded-3 mb-2" style="flex-shrink:0; border: 1px solid #e2e8f0 !important; background: #ffffff; overflow: visible;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-soft-primary text-primary rounded-circle fw-bold flex-shrink-0" style="width:22px;height:22px;min-width:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">
                            <i class="feather-file-text" style="line-height:1;"></i>
                        </div>
                        <span class="fw-bold text-dark fs-12">#${noteNum}</span>
                        <span class="badge bg-soft-primary text-primary fs-11 px-2 d-inline-flex align-items-center" style="padding-top:2px;padding-bottom:2px;"><i class="feather-edit-2 me-1"></i>Lead Note</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="fs-11 text-muted d-flex align-items-center gap-1"><i class="feather-clock" style="line-height:1;"></i> ${note.timestamp || ''}</span>
                        <button class="btn btn-outline-info btn-sm fw-bold flex-shrink-0 js-convert-note-to-task" data-lead-name="${escapeHtml(name)}" data-note-index="${i}" data-lead-id="${leadId}" style="font-size:10px;padding:2px 8px;" type="button"><i class="feather-plus me-1"></i>Convert to Task</button>
                    </div>
                </div>
                <div class="fs-13 text-dark fw-normal" style="white-space: pre-wrap; line-height: 1.6; word-break: break-word;">${note.text || ''}</div>
            </div>`;
    }

    $('#leadDetailModalTitle').html(`<i class="feather-user me-2"></i> Prospect Lead Overview: ${name}`);
    $('#leadDetailModalBody').html(`
        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">${name}</h5>
                <div class="text-muted fs-12">${contactDetails}</div>
            </div>
            <div>
                <span class="badge bg-primary fs-11">${stage}</span>
            </div>
        </div>

        <div class="row g-3 fs-13 mb-3">
            <div class="col-md-12">
                <div class="p-3 border rounded bg-white">
                    <div class="text-muted fs-11 fw-bold text-uppercase">Assigned Insurance Advisor</div>
                    <div class="fs-15 fw-bold text-dark mt-1">${advisor}</div>
                </div>
            </div>
        </div>

        <div class="card-widget p-3 mb-3">
            <h6 class="fw-bold text-dark mb-2"><i class="feather-file-text me-1 text-primary"></i> Advisor Underwriting Notes</h6>
            <div id="leadNotesContainer" class="d-flex flex-column gap-2 mb-3" style="max-height: 320px; overflow-y: auto; padding-right: 4px;">
                ${notesHtml}
            </div>
            <div class="d-flex flex-column gap-2 mt-2 pt-2 border-top">
                <textarea id="newLeadNoteInput" class="form-control fs-12" placeholder="Write a new note..." rows="2"></textarea>
                <button class="btn btn-primary btn-sm align-self-end fw-bold px-3 js-add-lead-note" data-lead-name="${escapeHtml(name)}" data-lead-id="${leadId || 0}" style="padding-top:6px;padding-bottom:6px;" type="button"><i class="feather-plus me-1"></i> Add Note</button>
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
        if (!kanbanLeadsLoaded) {
            kanbanLeadsLoaded = true;
            document.querySelectorAll('#kanbanViewContainer .kanban-card-dropzone').forEach(dropzone => {
                loadMoreStageLeads(dropzone);
            });
        }
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
    const numAnp = Number(anp);
    const coverFormatted = '$' + (isNaN(numAnp) ? '0.00' : numAnp.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })) + '/yr';

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
        <div class="lead-kanban-card p-3 bg-white rounded shadow-sm border-start border-4 border-${borderClass}" data-lead-id="" data-lead-name="${escapeHtml(fullName)}" data-lead-phone="${escapeHtml(mobile)}" data-lead-email="${escapeHtml(email)}" data-lead-source="${escapeHtml(company)}" data-lead-cover="${escapeHtml(coverFormatted)}" data-lead-stage="${escapeHtml(status)}" data-lead-adviser="${escapeHtml(adviser)}" data-lead-notes="${escapeHtml(outcome)}" data-lead-status="${escapeHtml(status)}" draggable="true" ondragstart="handleKanbanDragStart(event, this)" ondragend="handleKanbanDragEnd(this)" style="cursor: grab;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark fs-13">${escapeHtml(fullName)}</div>
                <span class="badge bg-soft-${borderClass} text-${borderClass} fs-10">${escapeHtml(company)}</span>
            </div>
            <div class="text-muted fs-12 mt-1">ANP (${escapeHtml(coverFormatted)}) &bull; ${escapeHtml(policyNo)}</div>
            <div class="fs-11 text-muted mt-2 d-flex justify-content-between align-items-center"><span><i class="feather-clock me-1"></i> ${escapeHtml(status)}</span><span class="text-primary fw-semibold fs-11">Click for Details &gt;</span></div>
        </div>
    `);

    updateKanbanColumnCounts();

    // Reset form and close modal
    $('#createLeadForm')[0].reset();
    const modalEl = document.getElementById('createLeadModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function showLeadImportStep(step) {
    [1, 2, 3].forEach(index => {
        document.getElementById(`leadsImportStep${index}`).classList.toggle('d-none', index !== step);
        document.getElementById(`leadsImportFooterStep${index}`).classList.toggle('d-none', index !== step);
    });
}

function leadImportError(message, target = 'leadImportError') {
    const element = document.getElementById(target);
    element.textContent = message || '';
    element.classList.toggle('d-none', !message);
}

async function leadImportPost(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: data,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || result.success === false) throw new Error(result.message || 'Lead import request failed.');
    return result;
}

function handleLeadImportFileSelected(input) {
    currentLeadImportPath = null;
    currentLeadImportSheets = [];
    document.getElementById('leadsImportFilePath').value = '';
    document.getElementById('leadsImportSheetSelector').classList.add('d-none');
    document.getElementById('leadsImportSheetSelect').replaceChildren();
    document.getElementById('leadsFileNameDisplay').textContent = input.files[0] ? `Selected: ${input.files[0].name}` : '';
    document.getElementById('importLeadsSubmitBtn').disabled = !input.files.length;
    leadImportError('');
}

async function loadLeadImportHeaders(sheetIndex) {
    const data = new FormData();
    data.set('file_path', currentLeadImportPath);
    data.set('destination', 'leads');
    data.set('sheet_index', sheetIndex);
    const result = await leadImportPost('/import/headers', data);
    currentLeadImportDbFields = result.db_fields;
    document.getElementById('leadsImportSheetIndex').value = result.sheet_index;
    document.getElementById('leadsSelectedSheetName').textContent = `(${result.sheet_name})`;

    const body = document.querySelector('#leadsMappingTable tbody');
    body.replaceChildren();
    result.mapping.forEach((mapping, index) => {
        const row = document.createElement('tr');
        const headerCell = document.createElement('td');
        const label = document.createElement('span');
        label.className = 'fw-semibold text-dark';
        label.textContent = mapping.original_header;
        headerCell.append(label);
        const originalHeader = document.createElement('input');
        originalHeader.type = 'hidden';
        originalHeader.name = `mapping[${index}][original_header]`;
        originalHeader.value = mapping.original_header;
        headerCell.append(originalHeader);

        const fieldCell = document.createElement('td');
        const fieldSelect = document.createElement('select');
        fieldSelect.className = 'form-select form-select-sm lead-mapping-select';
        fieldSelect.name = `mapping[${index}][db_field]`;
        fieldSelect.add(new Option('-- Do Not Import --', '--ignore--'));
        Object.entries(result.db_fields).forEach(([field, config]) => {
            const option = new Option(`${config.label}${config.required ? ' *' : ''}`, field);
            option.selected = mapping.suggested_field === field;
            fieldSelect.add(option);
        });
        fieldCell.append(fieldSelect);
        row.append(headerCell, fieldCell);
        body.append(row);
        if ($.fn.select2) {
            $(fieldSelect).select2({ width: '100%', minimumResultsForSearch: 0, dropdownParent: $('#importLeadsModal') });
            $(fieldSelect).on('select2:open', () => $('.select2-container--open .select2-search__field').attr('placeholder', 'Search fields...'));
        }
    });
    showLeadImportStep(2);
}

async function handleBatchImportLeads() {
    const input = document.getElementById('importLeadsFileInput');
    const button = document.getElementById('importLeadsSubmitBtn');
    if (!currentLeadImportPath && !input.files.length) return leadImportError('Please select a CSV or Excel file.');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Reading file...';

    try {
        if (!currentLeadImportPath) {
            const upload = new FormData();
            upload.set('import_file', input.files[0]);
            upload.set('destination', 'leads');
            const uploaded = await leadImportPost('/import/upload', upload);
            currentLeadImportPath = uploaded.file_path;
            currentLeadImportSheets = uploaded.sheets || [];
            document.getElementById('leadsImportFilePath').value = currentLeadImportPath;
            if (currentLeadImportSheets.length > 1) {
                const selector = document.getElementById('leadsImportSheetSelect');
                selector.replaceChildren(...currentLeadImportSheets.map(sheet => new Option(sheet.name, sheet.index)));
                document.getElementById('leadsImportSheetSelector').classList.remove('d-none');
                button.innerHTML = 'Continue with Selected Worksheet <i class="feather-arrow-right ms-1"></i>';
                return;
            }
        }
        const sheetIndex = currentLeadImportSheets.length > 1
            ? document.getElementById('leadsImportSheetSelect').value
            : (currentLeadImportSheets[0]?.index ?? 0);
        await loadLeadImportHeaders(sheetIndex);
    } catch (error) {
        leadImportError(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = currentLeadImportPath && currentLeadImportSheets.length > 1
            ? 'Continue with Selected Worksheet <i class="feather-arrow-right ms-1"></i>'
            : 'Continue to Mapping <i class="feather-arrow-right ms-1"></i>';
    }
}

async function handleLeadImportPreview(offset = 0) {
    const button = document.getElementById('leadsPreviewButton');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading Preview...';
    leadImportError('', 'leadsMappingError');
    try {
        const data = new FormData(document.getElementById('importLeadsForm'));
        data.set('file_path', currentLeadImportPath);
        data.set('destination', 'leads');
        data.set('preview_offset', offset);
        data.set('preview_limit', currentLeadPreviewLimit);
        data.set('default_lead_source', document.getElementById('leadImportSourceSelect').value);
        const result = await leadImportPost('/import/preview', data);
        currentLeadPreviewOffset = result.preview_offset;
        const head = document.querySelector('#leadsPreviewTable thead');
        const body = document.querySelector('#leadsPreviewTable tbody');
        const fields = [...document.querySelectorAll('.lead-mapping-select')].map(select => select.value).filter(value => value !== '--ignore--');
        if (document.getElementById('leadImportSourceSelect').value && !fields.includes('lead_source')) fields.push('lead_source');
        head.replaceChildren();
        body.replaceChildren();
        const header = document.createElement('tr');
        ['Row', ...fields.map(field => currentLeadImportDbFields[field]?.label || field), 'Validation'].forEach(text => {
            const cell = document.createElement('th');
            cell.textContent = text;
            header.append(cell);
        });
        head.append(header);
        result.preview_data.forEach(item => {
            const row = document.createElement('tr');
            if (!item.success) row.classList.add('table-danger');
            [item.row_number, ...fields.map(field => item.raw[field] ?? '-'), item.success ? 'Valid' : item.errors.join(', ')].forEach(value => {
                const cell = document.createElement('td');
                cell.textContent = String(value);
                row.append(cell);
            });
            body.append(row);
        });
        const start = result.total_rows ? result.preview_offset + 1 : 0;
        const end = Math.min(result.preview_offset + result.preview_limit, result.total_rows);
        document.getElementById('leadsPreviewPageSummary').textContent = `Showing ${start}-${end} of ${result.total_rows} rows`;
        document.getElementById('leadsPreviewPreviousButton').disabled = result.preview_offset === 0;
        document.getElementById('leadsPreviewNextButton').disabled = !result.has_more;
        showLeadImportStep(3);
    } catch (error) {
        leadImportError(error.message, 'leadsMappingError');
    } finally {
        button.disabled = false;
        button.innerHTML = 'Generate Preview <i class="feather-arrow-right ms-1"></i>';
    }
}

async function handleLeadImportProcess() {
    const button = document.getElementById('leadsProcessButton');
    leadImportError('');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Queuing Import...';
    try {
        const data = new FormData(document.getElementById('importLeadsForm'));
        data.set('file_path', currentLeadImportPath);
        data.set('destination', 'leads');
        data.set('default_lead_source', document.getElementById('leadImportSourceSelect').value);
        data.set('skip_errors', document.getElementById('leadsSkipErrorsCheck').checked ? '1' : '0');
        const result = await leadImportPost('/import/process', data);
        const modalElement = document.getElementById('importLeadsModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        if (modalElement.classList.contains('show')) {
            await new Promise(resolve => {
                modalElement.addEventListener('hidden.bs.modal', resolve, { once: true });
                modal.hide();
            });
        }
        showLeadImportProgress('Lead import queued. You can continue working while it processes.', result.tracking_id, { processed: 0, total: 0 });
        pollLeadImportStatus(result.tracking_id).catch(error => console.error('Unable to monitor lead import:', error));
    } catch (error) {
        leadImportError(error.message, 'leadsProcessError');
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="feather-check-circle me-1"></i> Confirm &amp; Import';
    }
}

function showLeadImportProgress(message, trackingId, progress = null, result = null) {
    let container = document.getElementById('leadImportToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'leadImportToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1090';
        container.style.width = 'min(380px, calc(100vw - 24px))';
        document.body.appendChild(container);
    }

    const toastId = `leadImportToast-${trackingId}`;
    let toast = document.getElementById(toastId);
    if (!toast) {
        toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast border-0 shadow';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.style.backgroundColor = 'var(--ss-navy-dark, #0A192F)';
        toast.style.color = '#F8FAFC';
        toast.style.width = '100%';
        const body = document.createElement('div');
        body.className = 'toast-body';
        body.style.paddingRight = '40px';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white position-absolute top-0 end-0 m-3';
        close.setAttribute('aria-label', 'Dismiss import progress');
        close.addEventListener('click', () => bootstrap.Toast.getOrCreateInstance(toast).hide());
        toast.append(body, close);
        container.appendChild(toast);
    }

    clearTimeout(toast.dismissTimer);
    const body = toast.querySelector('.toast-body');
    body.replaceChildren();
    const text = document.createElement('div');
    text.textContent = message;
    body.appendChild(text);

    if (result) {
        const summary = document.createElement('div');
        summary.className = 'fs-11 mt-1';
        summary.style.color = '#CBD5E1';
        summary.textContent = `${result.imported_count || 0} imported, ${result.skipped_count || 0} skipped, ${result.failed_count || 0} failed.${result.error_message ? ` ${result.error_message}` : ''}`;
        body.appendChild(summary);
        if (result.error_csv_url) {
            const link = document.createElement('a');
            link.href = result.error_csv_url;
            link.textContent = 'Download failed rows CSV';
            link.className = 'd-inline-block mt-2 text-info fw-semibold';
            body.appendChild(link);
        }
        toast.style.borderLeft = `4px solid ${result.status === 'failed' ? '#F87171' : (result.failed_count ? '#FBBF24' : '#34D399')}`;
        toast.dismissTimer = setTimeout(() => bootstrap.Toast.getOrCreateInstance(toast).hide(), 10000);
    } else {
        const total = Number(progress?.total) || 0;
        const processed = Math.min(total || Number.MAX_SAFE_INTEGER, Number(progress?.processed) || 0);
        const percent = total > 0 ? Math.floor((processed / total) * 100) : 32;
        let styles = document.getElementById('leadImportProgressAnimation');
        if (!styles) {
            styles = document.createElement('style');
            styles.id = 'leadImportProgressAnimation';
            styles.textContent = '@keyframes leadImportToastSweep { from { transform: translateX(-120%); } to { transform: translateX(320%); } }';
            document.head.appendChild(styles);
        }
        const track = document.createElement('div');
        track.style.cssText = 'height:6px;width:100%;margin-top:8px;background:rgba(248,250,252,.2);border-radius:999px;overflow:hidden';
        track.setAttribute('role', 'progressbar');
        track.setAttribute('aria-valuemin', '0');
        if (total > 0) {
            track.setAttribute('aria-valuemax', String(total));
            track.setAttribute('aria-valuenow', String(processed));
        } else {
            track.setAttribute('aria-label', 'Lead import waiting for worker');
        }
        const bar = document.createElement('div');
        bar.style.cssText = `height:100%;width:${percent}%;background:#00A8B5;border-radius:999px;transition:width .35s ease`;
        if (!total) bar.style.animation = 'leadImportToastSweep 1.4s ease-in-out infinite';
        track.appendChild(bar);
        body.appendChild(track);
        const detail = document.createElement('div');
        detail.className = 'fs-11 mt-1';
        detail.style.color = '#CBD5E1';
        detail.textContent = total > 0 ? `${processed} / ${total} rows processed` : 'Waiting for the import worker';
        body.appendChild(detail);
        toast.style.borderLeft = '4px solid #00A8B5';
    }

    bootstrap.Toast.getOrCreateInstance(toast, { autohide: false }).show();
}

async function pollLeadImportStatus(trackingId) {
    let failures = 0;
    while (failures < 5) {
        try {
            const response = await fetch(`/import/status/${encodeURIComponent(trackingId)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(`Status request failed (${response.status}).`);
            const result = await response.json();
            failures = 0;
            if (['done', 'done_with_errors', 'failed'].includes(result.status)) {
                await refreshLeadPipeline().catch(error => console.error('Unable to refresh leads after import:', error));
                showLeadImportProgress(`Lead import ${result.status === 'failed' ? 'failed' : 'finished'}.`, trackingId, null, result);
                return;
            }
            showLeadImportProgress(result.status === 'pending' ? 'Lead import is queued.' : 'Lead import is in progress.', trackingId, {
                processed: result.processed_count,
                total: result.total_rows,
            });
        } catch (error) {
            if (++failures >= 5) return console.error('Lead import status polling paused:', error);
        }
        await new Promise(resolve => setTimeout(resolve, 5000));
    }
}

$(document).on('hidden.bs.modal', '#importLeadsModal', function () {
    document.getElementById('importLeadsForm').reset();
    document.getElementById('leadsFileNameDisplay').textContent = '';
    document.getElementById('leadsImportSheetSelector').classList.add('d-none');
    document.getElementById('leadsImportSheetSelect').replaceChildren();
    document.getElementById('leadsImportFilePath').value = '';
    document.getElementById('leadsImportSheetIndex').value = '0';
    document.getElementById('importLeadsSubmitBtn').disabled = true;
    document.getElementById('importLeadsSubmitBtn').innerHTML = 'Continue to Mapping <i class="feather-arrow-right ms-1"></i>';
    document.querySelector('#leadsMappingTable tbody').replaceChildren();
    leadImportError('');
    leadImportError('', 'leadsMappingError');
    leadImportError('', 'leadsProcessError');
    currentLeadImportPath = null;
    currentLeadImportSheets = [];
    currentLeadImportDbFields = {};
    currentLeadPreviewOffset = 0;
    showLeadImportStep(1);
});

async function refreshLeadPipeline() {
    const response = await fetch(window.location.href, {
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to refresh the Leads pipeline.');
    }

    const responseHtml = await response.text();
    const refreshedDocument = new DOMParser().parseFromString(responseHtml, 'text/html');
    const currentKanban = document.getElementById('kanbanViewContainer');
    const currentList = document.getElementById('listViewContainer');
    const refreshedKanban = refreshedDocument.getElementById('kanbanViewContainer');
    const refreshedList = refreshedDocument.getElementById('listViewContainer');

    if (!currentKanban || !currentList || !refreshedKanban || !refreshedList) {
        throw new Error('The Leads pipeline could not be refreshed.');
    }

    const listViewWasVisible = !currentList.classList.contains('d-none');

    if (leadsDataTable) {
        leadsDataTable.destroy();
        leadsDataTable = null;
    }

    currentKanban.replaceWith(refreshedKanban);
    currentList.replaceWith(refreshedList);

    if (listViewWasVisible) {
        document.getElementById('kanbanViewContainer').classList.add('d-none');
        document.getElementById('listViewContainer').classList.remove('d-none');
    }

    initializeLeadsDataTable();
    initKanbanInfiniteScroll();
}

function updateKanbanColumnCounts() {
    $('.kanban-column-box').each(function () {
        const dropzone = $(this).find('.kanban-card-dropzone');
        let count = dropzone.find('.lead-kanban-card').length;
        const total = parseInt(dropzone.attr('data-total') || count, 10);
        $(this).find('.kanban-count').text(`(${Math.max(count, total)})`);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderKanbanCardHtml(lead, stageLabel, stageColor, canWrite) {
    const safeFirstName = escapeHtml(lead.first_name || '');
    const safeLastName = escapeHtml(lead.last_name || '');
    const safePhone = escapeHtml(lead.phone || '');
    const safeEmail = escapeHtml(lead.email || '');
    const safeSource = escapeHtml(lead.source || 'Unknown');
    const safeCover = escapeHtml(lead.estimated_cover ? `$${lead.estimated_cover}/yr` : '$0/yr');
    const safeNotes = escapeHtml(lead.notes || '');
    const safeStage = escapeHtml(stageLabel || '');
    const safeStatus = escapeHtml(lead.status || 'new');
    const safeTime = escapeHtml(lead.created_at_human || 'N/A');
    const safeName = escapeHtml(`${lead.first_name || ''} ${lead.last_name || ''}`.trim());
    const safeAdviser = escapeHtml(lead.adviser || '-');
    const safeLeadId = escapeHtml(lead.id);

    const cursorStyle = canWrite ? 'grab' : 'pointer';
    const draggable = canWrite ? 'true' : 'false';

    return `
        <div class="lead-kanban-card border-start border-4 border-${stageColor}" 
             data-lead-id="${safeLeadId}"
             data-lead-name="${safeName}"
             data-lead-phone="${safePhone}"
             data-lead-email="${safeEmail}"
             data-lead-source="${safeSource}"
             data-lead-cover="${safeCover}"
             data-lead-stage="${safeStage}"
             data-lead-adviser="${safeAdviser}"
             data-lead-notes="${safeNotes}"
             data-lead-status="${safeStatus}"
             draggable="${draggable}" 
             ondragstart="handleKanbanDragStart(event, this)" 
             ondragend="handleKanbanDragEnd(this)" 
             style="cursor: ${cursorStyle};">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div class="lead-name-text">${safeFirstName} ${safeLastName}</div>
                <span class="badge bg-soft-${stageColor} text-${stageColor} lead-source-badge">${safeSource}</span>
            </div>
            <div class="lead-meta-row">
                <span><i class="feather-clock me-1"></i> ${safeTime}</span>
                <span class="lead-details-link">Click for Details &gt;</span>
            </div>
        </div>
    `;
}

async function loadMoreStageLeads(dropzoneEl) {
    const $dropzone = $(dropzoneEl);
    if ($dropzone.data('loading') || $dropzone.attr('data-has-more') === 'false') {
        return;
    }

    $dropzone.data('loading', true);
    const $loader = $dropzone.find('.kanban-scroll-loader');
    $loader.removeClass('d-none');

    const status = $dropzone.attr('data-status');
    const offset = parseInt($dropzone.attr('data-offset') || '0', 10);
    const stageLabel = $dropzone.attr('data-stage-label') || 'New Leads';
    const stageColor = $dropzone.attr('data-stage-color') || 'primary';
    const canWrite = $('#kanbanViewContainer').attr('data-can-write-leads') === '1';

    // Grab existing filters from top bar if any
    const search = $('#leadFilterSearch').val() || '';
    const source = $('#leadFilterSource').val() || '';
     const adviser = $('#leadFilterAdviser').val() || '';
    const createdFrom = $('#leadCreatedFrom').val() || '';
    const createdTo = $('#leadCreatedTo').val() || '';

    const params = new URLSearchParams({
        status: status,
        offset: offset,
        limit: 20
    });
    if (search) params.append('search', search);
    if (source) params.append('source', source);
    if (adviser) params.append('adviser', adviser);
    if (createdFrom) params.append('created_from', createdFrom);
    if (createdTo) params.append('created_to', createdTo);

    try {
        const response = await fetch(`/crm/pipeline/stage?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to load leads (${response.status})`);
        }

        const data = await response.json();
        if (data.success && Array.isArray(data.leads) && data.leads.length > 0) {
            let cardsHtml = '';
            data.leads.forEach(lead => {
                cardsHtml += renderKanbanCardHtml(lead, stageLabel, stageColor, canWrite);
            });

            // Insert cards before the loader
            $loader.before(cardsHtml);

            const newOffset = offset + data.leads.length;
            $dropzone.attr('data-offset', newOffset);
            $dropzone.attr('data-total', data.total);
            $dropzone.attr('data-has-more', data.has_more ? 'true' : 'false');

            // Keep column count header in sync
            $dropzone.closest('.kanban-column-box').find('.kanban-count').text(`(${data.total})`);
        } else {
            $dropzone.attr('data-has-more', 'false');
        }
    } catch (err) {
        console.error('Error fetching stage leads:', err);
    } finally {
        $dropzone.data('loading', false);
        $loader.addClass('d-none');
    }
}

function initKanbanInfiniteScroll() {
    $('.kanban-card-dropzone').each(function () {
        const dropzoneEl = this;
        $(dropzoneEl).off('scroll.kanbanLazy').on('scroll.kanbanLazy', function () {
            const scrollTop = this.scrollTop;
            const scrollHeight = this.scrollHeight;
            const clientHeight = this.clientHeight;

            // Trigger when within 80px of bottom
            if (scrollTop + clientHeight >= scrollHeight - 80) {
                loadMoreStageLeads(dropzoneEl);
            }
        });
    });
}

function initializeLeadsDataTable() {
    if ($.fn.DataTable.isDataTable('#leadsDataTable')) {
        const existingTable = $('#leadsDataTable').DataTable();
        if (existingTable.page.info().serverSide) {
            leadsDataTable = existingTable;
            return;
        }
        existingTable.destroy();
        document.querySelector('#leadsDataTable tbody').replaceChildren();
    }

    const table = document.getElementById('leadsDataTable');
    const canWrite = table.dataset.canWrite === '1';
    const isAdmin = table.dataset.isAdmin === '1';
    const stageLabels = { new: 'New Leads', contacted: 'Contacted', proposal: 'Underwriting', won: 'Policy Issued' };
    const stageColors = { new: 'primary', contacted: 'info', proposal: 'warning', won: 'success' };
    $('#leadFilterStatus option').each(function () {
        const val = $(this).val();
        const txt = $(this).text().trim();
        if (val && txt && txt !== 'All stages') {
            stageLabels[val] = txt;
        }
    });

    leadsDataTable = $('#leadsDataTable').DataTable({
        responsive: false,
        pageLength: 10,
        processing: false,
        serverSide: true,
        deferRender: true,
        searchDelay: 350,
        order: [[0, 'desc']],
        language: { search: "_INPUT_", searchPlaceholder: "Search prospect leads..." },
        ajax: {
            url: table.dataset.source,
            data: data => {
                data.filter_search = document.getElementById('leadFilterSearch').value;
                data.status = document.getElementById('leadFilterStatus').value;
                data.source = document.getElementById('leadFilterSource').value;
                data.adviser = document.getElementById('leadFilterAdviser')?.value || '';
                data.created_from = document.getElementById('leadCreatedFrom').value;
                data.created_to = document.getElementById('leadCreatedTo').value;
            }
        },
        columns: [
            { data: 'full_name', render: (value, type, lead) => type !== 'display' ? lead.updated_at : `<span class="fw-bold text-dark fs-13">${escapeHtml(value)}</span>` },
            { data: 'phone', render: value => value ? `<a href="tel:${escapeHtml(String(value).replace(/[^0-9+]/g, ''))}">${escapeHtml(value)}</a>` : '' },
            { data: 'email', render: value => value ? `<a href="mailto:${escapeHtml(value)}">${escapeHtml(value)}</a>` : '' },
            { data: 'source', render: (value, type, lead) => `<span class="badge bg-soft-${stageColors[lead.status] || 'primary'} text-${stageColors[lead.status] || 'primary'} fs-11">${escapeHtml(value || 'Unknown')}</span>` },
            { data: 'adviser', orderable: false, searchable: false, render: value => `<span class="fs-13 text-dark fw-medium">${escapeHtml(value || '-')}</span>` },
            { data: 'status', render: value => `<span class="badge bg-${stageColors[value] || 'primary'} fs-11">${escapeHtml(stageLabels[value] || 'New')}</span>` },
            { data: null, searchable: false, orderable: false, className: 'text-center', render: (value, type, lead) => renderLeadActions(lead, canWrite, isAdmin) }
        ]
    });

    $('#leadsDataTable').off('.leadRowActions')
        .on('click.leadRowActions', '.js-lead-view-details', function () {
            const lead = leadsDataTable.row($(this).closest('tr')).data();
            if (!lead) return;
            const stage = stageLabels[lead.status] || 'New';
            const cover = lead.estimated_cover ? `$${Number(lead.estimated_cover).toLocaleString()}/yr` : '$0/yr';
            openLeadDetailModal(lead.full_name, lead.phone, lead.email, lead.source || 'Unknown', cover, stage, lead.adviser || '-', lead.notes, lead.id, lead.status);
        })
        .on('click.leadRowActions', '.js-lead-edit', function () {
            openEditLeadModal(Number(this.dataset.leadId));
        });
}

function renderLeadActions(lead, canWrite, isAdmin = false) {
    const id = Number(lead.id);
    const csrfToken = escapeHtml(document.querySelector('meta[name="csrf-token"]')?.content || '');
    return `<div class="action-kebab-wrapper">
        <button class="action-kebab-btn" type="button"><i class="feather-more-vertical"></i></button>
        <div class="action-kebab-dropdown">
            <a href="javascript:void(0);" class="action-kebab-item js-lead-view-details"><i class="feather-eye text-primary me-1"></i> View Details</a>
            ${canWrite ? `<a href="javascript:void(0);" class="action-kebab-item js-lead-edit" data-lead-id="${id}"><i class="feather-edit text-info me-1"></i> Edit Lead</a>
            ${isAdmin ? `<form action="/crm/leads/${id}" method="POST" class="d-inline"><input type="hidden" name="_token" value="${csrfToken}"><input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="action-kebab-item border-0 bg-transparent w-100 text-start text-danger" onclick="confirmFormSubmit(event, 'Delete this lead? This action cannot be undone.', this.closest('form'))"><i class="feather-trash-2 me-1"></i> Delete Lead</button>
            </form>` : ''}
            ${lead.status !== 'won' ? `<form action="/crm/leads/${id}/convert" method="POST" class="d-inline"><input type="hidden" name="_token" value="${csrfToken}">
                <button type="submit" class="action-kebab-item border-0 bg-transparent w-100 text-start" onclick="confirmFormSubmit(event, 'Convert this lead to a client?', this.closest('form'))"><i class="feather-arrow-right text-success me-1"></i> Convert to Client</button>
            </form>` : ''}` : ''}
        </div>
    </div>`;
}

function downloadLeadsTemplate(event) {
    event.preventDefault();
    window.location.href = '/import/templates/leads';
}

$(document).ready(function () {
    initializeLeadsDataTable();
    initKanbanInfiniteScroll();

    $(document)
        .off('click.leadCardActions')
        .on('click.leadCardActions', '.lead-kanban-card', function () {
            const card = this;
            openLeadDetailModal(
                card.dataset.leadName,
                card.dataset.leadPhone,
                card.dataset.leadEmail,
                card.dataset.leadSource,
                card.dataset.leadCover,
                card.dataset.leadStage,
                card.dataset.leadAdviser,
                card.dataset.leadNotes,
                card.dataset.leadId || 0,
                card.dataset.leadStatus
            );
        })
        .on('click.leadCardActions', '.js-convert-note-to-task', function () {
            convertNoteToTask(this.dataset.leadName, Number(this.dataset.noteIndex), Number(this.dataset.leadId));
        })
        .on('click.leadCardActions', '.js-add-lead-note', function () {
            addLeadNote(this.dataset.leadName, Number(this.dataset.leadId) || 0);
        });
});

function editLeadFromDetailModal() {
    const detailModalEl = document.getElementById('leadDetailModal');
    const detailModal = bootstrap.Modal.getInstance(detailModalEl);
    if (detailModal) detailModal.hide();

    if (window.currentViewingLeadId) {
        setTimeout(() => {
            openEditLeadModal(window.currentViewingLeadId);
        }, 300);
    }
}

async function openEditLeadModal(leadId) {
    if (!leadId) return;

    try {
        const response = await fetch(`/crm/leads/${leadId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            alert('Unable to load lead details. Please try again.');
            return;
        }

        const data = await response.json();
        if (!data.success || !data.lead) {
            alert('Lead record not found.');
            return;
        }

        const lead = data.lead;

        $('#editLeadId').val(lead.id);
        $('#editLeadForm').attr('action', `/crm/leads/${lead.id}`);
        $('#editLeadNameHeader').text(lead.full_name || `${lead.first_name || ''} ${lead.last_name || ''}`.trim() || `Lead #${lead.id}`);

        $('#editLeadAlertError').addClass('d-none').empty();

        // 1. Policy & Provider Info
        $('#editLeadPolicyNo').val(lead.policy_no || '');
        if (lead.company && $('#editLeadCompany option[value="' + lead.company + '"]').length > 0) {
            $('#editLeadCompany').val(lead.company);
        } else if (lead.company) {
            $('#editLeadCompany').append(new Option(lead.company, lead.company, true, true)).val(lead.company);
        } else {
            $('#editLeadCompany').val('');
        }
        $('#editLeadLoginDate').val(lead.login_date || '');
        $('#editLeadAnp').val(lead.anp || '');

        // 2. Client Contact Information
        $('#editLeadFirstName').val(lead.first_name || '');
        $('#editLeadLastName').val(lead.last_name || '');
        $('#editLeadDob').val(lead.dob || '');
        $('#editLeadPhone').val(lead.phone || '');
        $('#editLeadEmail').val(lead.email || '');

        if (lead.lead_source && $('#editLeadSource option[value="' + lead.lead_source + '"]').length > 0) {
            $('#editLeadSource').val(lead.lead_source);
        } else if (lead.lead_source) {
            $('#editLeadSource').append(new Option(lead.lead_source, lead.lead_source, true, true)).val(lead.lead_source);
        } else {
            $('#editLeadSource').val('');
        }

        $('#editLeadEstimatedCover').val(lead.estimated_cover || '');
        const statusVal = lead.status || 'new';
        if ($('#editLeadStatus option[value="' + statusVal + '"]').length > 0) {
            $('#editLeadStatus').val(statusVal);
        } else if ($('#editLeadStatus option').filter(function () { return $(this).text().trim().toLowerCase() === String(statusVal).toLowerCase(); }).length > 0) {
            const matched = $('#editLeadStatus option').filter(function () { return $(this).text().trim().toLowerCase() === String(statusVal).toLowerCase(); }).val();
            $('#editLeadStatus').val(matched);
        } else if (statusVal) {
            $('#editLeadStatus').append(new Option(statusVal, statusVal, true, true)).val(statusVal);
        } else {
            $('#editLeadStatus').val('new');
        }

        // 3. Address Details
        $('#editLeadAddress').val(lead.address || '');
        $('#editLeadSuburb').val(lead.suburb || '');
        $('#editLeadCity').val(lead.city || '');
        $('#editLeadPostCode').val(lead.post_code || '');

        // 4. Compliance & Status
        if (lead.adviser && $('#editLeadAdviser option[value="' + lead.adviser + '"]').length > 0) {
            $('#editLeadAdviser').val(lead.adviser);
        } else if (lead.adviser) {
            $('#editLeadAdviser').append(new Option(lead.adviser, lead.adviser, true, true)).val(lead.adviser);
        } else {
            $('#editLeadAdviser').val('');
        }

        $('#editLeadNotCounting').val(lead.not_counting || '');
        $('#editLeadComplianceBy').val(lead.compliance_by || '');
        $('#editLeadRoaDueDate').val(lead.roa_due_date || '');
        $('#editLeadStatusCompliance').val(lead.status_compliance || '');
        $('#editLeadSentToClient').val(lead.sent_to_client || '');
        $('#editLeadOutcome').val(lead.outcome || '');

        const modalEl = document.getElementById('editLeadModal');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    } catch (err) {
        console.error('Error fetching lead details:', err);
        alert('An error occurred while fetching lead information.');
    }
}

async function handleEditLeadSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('editLeadForm');
    const submitBtn = document.getElementById('editLeadSubmitBtn');
    const errAlert = document.getElementById('editLeadAlertError');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Updating...';
    errAlert.classList.add('d-none');
    errAlert.innerHTML = '';

    try {
        const formData = new FormData(form);
        const actionUrl = form.action;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        const data = await response.json();

        if (response.ok && data.success) {
            window.location.reload();
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="feather-check me-1"></i> Update Lead Record';

            let message = data.message || 'Validation error occurred.';
            if (data.errors) {
                const list = Object.values(data.errors).flat().map(err => `<li>${err}</li>`).join('');
                message = `<ul class="mb-0 ps-3">${list}</ul>`;
            }
            errAlert.innerHTML = message;
            errAlert.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Error updating lead:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="feather-check me-1"></i> Update Lead Record';
        errAlert.innerText = 'Network error or server error occurred. Please try again.';
        errAlert.classList.remove('d-none');
    }
}
