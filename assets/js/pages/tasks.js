/* Page script for tasks */
let tasksDataTable = null;

// Force clear task local storage once to load new mock data
if (!localStorage.getItem('ss_tasks_loaded_v5')) {
    localStorage.removeItem('ss_tasks');
    localStorage.removeItem('ss_task_notes');
    localStorage.setItem('ss_tasks_loaded_v5', 'true');
}

// Get current date time string helper
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

const defaultTasks = [{'title': 'Annual Review Call', 'client': 'Kishore Johnson', 'date': '08 Aug 2026', 'priority': 'Medium', 'status': 'Pending', 'id': 'task-status-1'}, {'title': 'Send Policy Renewal Document', 'client': 'Suman Williams', 'date': '02 Aug 2026', 'priority': 'High', 'status': 'Pending', 'id': 'task-status-2'}, {'title': 'Task: Follow up cover illustration #3', 'client': 'Rahul Brown', 'date': '21 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-3'}, {'title': 'Task: Follow up cover illustration #4', 'client': 'Priya Jones', 'date': '03 Aug 2026', 'priority': 'High', 'status': 'Pending', 'id': 'task-status-4'}, {'title': 'Task: Follow up cover illustration #5', 'client': 'Sarah Garcia', 'date': '20 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-5'}, {'title': 'Task: Follow up renewal #6', 'client': 'Amit Miller', 'date': '21 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-6'}, {'title': 'Task: Follow up claim docs #7', 'client': 'David Davis', 'date': '23 Aug 2026', 'priority': 'High', 'status': 'Pending', 'id': 'task-status-7'}, {'title': 'Task: Follow up medical test result #8', 'client': 'Michael Rodriguez', 'date': '14 Aug 2026', 'priority': 'High', 'status': 'Pending', 'id': 'task-status-8'}, {'title': 'Task: Follow up medical test result #9', 'client': 'Aarav Martinez', 'date': '08 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-9'}, {'title': 'Task: Follow up cover illustration #10', 'client': 'Vandana Hernandez', 'date': '06 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-10'}, {'title': 'Task: Follow up renewal #11', 'client': 'James Lopez', 'date': '17 Aug 2026', 'priority': 'Medium', 'status': 'Pending', 'id': 'task-status-11'}, {'title': 'Task: Follow up renewal #12', 'client': 'Olivia Gonzalez', 'date': '17 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-12'}, {'title': 'Task: Follow up renewal #13', 'client': 'Ethan Wilson', 'date': '13 Aug 2026', 'priority': 'Medium', 'status': 'Pending', 'id': 'task-status-13'}, {'title': 'Task: Follow up renewal #14', 'client': 'Arjun Anderson', 'date': '21 Aug 2026', 'priority': 'Medium', 'status': 'Pending', 'id': 'task-status-14'}, {'title': 'Task: Follow up medical test result #15', 'client': 'Neha Thomas', 'date': '09 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-15'}, {'title': 'Task: Follow up renewal #16', 'client': 'John Taylor', 'date': '12 Aug 2026', 'priority': 'High', 'status': 'Pending', 'id': 'task-status-16'}, {'title': 'Task: Follow up medical test result #17', 'client': 'Emma Moore', 'date': '08 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-17'}, {'title': 'Task: Follow up renewal #18', 'client': 'Robert Jackson', 'date': '25 Aug 2026', 'priority': 'Low', 'status': 'Pending', 'id': 'task-status-18'}, {'title': 'Task: Follow up medical test result #19', 'client': 'Sophia Martin', 'date': '05 Aug 2026', 'priority': 'High', 'status': 'Completed', 'id': 'task-status-19'}, {'title': 'Task: Follow up medical test result #20', 'client': 'William Lee', 'date': '18 Aug 2026', 'priority': 'Medium', 'status': 'Completed', 'id': 'task-status-20'}, {'title': 'Task: Follow up claim docs #21', 'client': 'Isabella Perez', 'date': '27 Aug 2026', 'priority': 'Low', 'status': 'Completed', 'id': 'task-status-21'}, {'title': 'Task: Follow up cover illustration #22', 'client': 'Daniel Thompson', 'date': '23 Aug 2026', 'priority': 'Medium', 'status': 'Completed', 'id': 'task-status-22'}, {'title': 'Task: Follow up claim docs #23', 'client': 'Mia White', 'date': '26 Aug 2026', 'priority': 'High', 'status': 'Completed', 'id': 'task-status-23'}, {'title': 'Task: Follow up renewal #24', 'client': 'Joseph Harris', 'date': '23 Aug 2026', 'priority': 'Medium', 'status': 'Completed', 'id': 'task-status-24'}, {'title': 'Task: Follow up renewal #25', 'client': 'Charlotte Sanchez', 'date': '10 Aug 2026', 'priority': 'High', 'status': 'Completed', 'id': 'task-status-25'}, {'title': 'Task: Follow up renewal #26', 'client': 'Matthew Clark', 'date': '26 Aug 2026', 'priority': 'High', 'status': 'Completed', 'id': 'task-status-26'}, {'title': 'Task: Follow up renewal #27', 'client': 'Amelia Ramirez', 'date': '11 Aug 2026', 'priority': 'Medium', 'status': 'Completed', 'id': 'task-status-27'}, {'title': 'Task: Follow up cover illustration #28', 'client': 'David Chen', 'date': '27 Aug 2026', 'priority': 'Low', 'status': 'Completed', 'id': 'task-status-28'}, {'title': 'Task: Follow up cover illustration #29', 'client': 'Harper Davis', 'date': '09 Aug 2026', 'priority': 'High', 'status': 'Completed', 'id': 'task-status-29'}, {'title': 'Task: Follow up claim docs #30', 'client': 'Andrew Evans', 'date': '10 Aug 2026', 'priority': 'Medium', 'status': 'Completed', 'id': 'task-status-30'}];

let tasksList = [];
try {
    const stored = localStorage.getItem('ss_tasks');
    if (stored) {
        tasksList = JSON.parse(stored);
    } else {
        tasksList = defaultTasks;
        localStorage.setItem('ss_tasks', JSON.stringify(tasksList));
    }
} catch(e) {
    tasksList = defaultTasks;
}

const defaultTaskNotes = {'Annual Review Call': [{'text': 'Advisory task created for Kishore Johnson.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Send Policy Renewal Document': [{'text': 'Advisory task created for Suman Williams.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #3': [{'text': 'Advisory task created for Rahul Brown.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #4': [{'text': 'Advisory task created for Priya Jones.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #5': [{'text': 'Advisory task created for Sarah Garcia.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #6': [{'text': 'Advisory task created for Amit Miller.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up claim docs #7': [{'text': 'Advisory task created for David Davis.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #8': [{'text': 'Advisory task created for Michael Rodriguez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #9': [{'text': 'Advisory task created for Aarav Martinez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #10': [{'text': 'Advisory task created for Vandana Hernandez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #11': [{'text': 'Advisory task created for James Lopez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #12': [{'text': 'Advisory task created for Olivia Gonzalez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #13': [{'text': 'Advisory task created for Ethan Wilson.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #14': [{'text': 'Advisory task created for Arjun Anderson.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #15': [{'text': 'Advisory task created for Neha Thomas.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #16': [{'text': 'Advisory task created for John Taylor.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #17': [{'text': 'Advisory task created for Emma Moore.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #18': [{'text': 'Advisory task created for Robert Jackson.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Pending.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #19': [{'text': 'Advisory task created for Sophia Martin.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up medical test result #20': [{'text': 'Advisory task created for William Lee.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up claim docs #21': [{'text': 'Advisory task created for Isabella Perez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #22': [{'text': 'Advisory task created for Daniel Thompson.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up claim docs #23': [{'text': 'Advisory task created for Mia White.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #24': [{'text': 'Advisory task created for Joseph Harris.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #25': [{'text': 'Advisory task created for Charlotte Sanchez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #26': [{'text': 'Advisory task created for Matthew Clark.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up renewal #27': [{'text': 'Advisory task created for Amelia Ramirez.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #28': [{'text': 'Advisory task created for David Chen.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up cover illustration #29': [{'text': 'Advisory task created for Harper Davis.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}], 'Task: Follow up claim docs #30': [{'text': 'Advisory task created for Andrew Evans.', 'timestamp': '10 Aug 2026, 09:00 AM'}, {'text': 'Sent follow-up reminder to client.', 'timestamp': '12 Aug 2026, 02:30 PM'}, {'text': 'Status updated: Completed.', 'timestamp': '15 Aug 2026, 04:00 PM'}]};

let taskNotes = {};
try {
    const stored = localStorage.getItem('ss_task_notes');
    if (stored) {
        taskNotes = JSON.parse(stored);
    } else {
        taskNotes = defaultTaskNotes;
        localStorage.setItem('ss_task_notes', JSON.stringify(taskNotes));
    }
} catch(e) {
    taskNotes = defaultTaskNotes;
}

function openTaskNotesModal(title, client) {
    if (!taskNotes[title]) {
        taskNotes[title] = [{ text: 'Task created.', timestamp: getCurrentDateTimeString() }];
        localStorage.setItem('ss_task_notes', JSON.stringify(taskNotes));
    }
    
    $('#taskNotesTitleHeader').text(title);
    $('#taskNotesClientSpan').text(client);
    
    let notesHtml = '';
    const totalNotes = taskNotes[title].length;
    for (let i = totalNotes - 1; i >= 0; i--) {
        const note = taskNotes[title][i];
        const text = typeof note === 'string' ? note : note.text;
        const timestamp = typeof note === 'string' ? '18 Aug 2026, 09:00 AM' : note.timestamp;
        
        notesHtml += `
            <div class="d-flex align-items-start gap-2 border-bottom py-2">
                <div class="avatar-text avatar-xs bg-soft-primary text-primary rounded-circle mt-0.5 flex-shrink-0" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">${i + 1}</div>
                <div>
                    <div class="text-muted fs-12">${text}</div>
                    <div class="text-muted fs-10 mt-1"><i class="feather-clock me-1"></i>${timestamp}</div>
                </div>
            </div>`;
    }
    $('#taskNotesListContainer').html(notesHtml);
    
    // Set the click event handler on the Add Note button dynamically
    $('#addTaskNoteBtn').off('click').on('click', function() {
        addTaskNote(title);
    });
    
    $('#taskNotesModal').modal('show');
}

function addTaskNote(title) {
    const noteText = $('#newTaskNoteInput').val().trim();
    if (!noteText) return;
    
    if (!taskNotes[title]) {
        taskNotes[title] = [];
    }
    const timestamp = getCurrentDateTimeString();
    taskNotes[title].push({ text: noteText, timestamp: timestamp });
    localStorage.setItem('ss_task_notes', JSON.stringify(taskNotes));
    
    // Re-render notes list (recent on top)
    let notesHtml = '';
    const totalNotes = taskNotes[title].length;
    for (let i = totalNotes - 1; i >= 0; i--) {
        const note = taskNotes[title][i];
        const text = typeof note === 'string' ? note : note.text;
        const ts = typeof note === 'string' ? '18 Aug 2026, 09:00 AM' : note.timestamp;
        notesHtml += `
            <div class="d-flex align-items-start gap-2 border-bottom py-2">
                <div class="avatar-text avatar-xs bg-soft-primary text-primary rounded-circle mt-0.5 flex-shrink-0" style="width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">${i + 1}</div>
                <div>
                    <div class="text-muted fs-12">${text}</div>
                    <div class="text-muted fs-10 mt-1"><i class="feather-clock me-1"></i>${ts}</div>
                </div>
            </div>`;
    }
    $('#taskNotesListContainer').html(notesHtml);
    $('#newTaskNoteInput').val('');
}

function toggleTaskStatus(taskId) {
    tasksList.forEach(function(t) {
        if (t.id === taskId) {
            t.status = (t.status === 'Completed') ? 'Pending' : 'Completed';
        }
    });
    localStorage.setItem('ss_tasks', JSON.stringify(tasksList));
    renderTasksTable();
}

function deleteTask(taskId) {
    if (confirm("Are you sure you want to delete this task?")) {
        tasksList = tasksList.filter(t => t.id !== taskId);
        localStorage.setItem('ss_tasks', JSON.stringify(tasksList));
        renderTasksTable();
    }
}

function renderTasksTable() {
    if (!tasksDataTable) return;
    tasksDataTable.clear();
    tasksList.forEach(function(task) {
        const priorityBadge = task.priority === 'High' ? '<span class="badge bg-soft-danger text-danger fs-11">High</span>' : '<span class="badge bg-soft-primary text-primary fs-11">Medium</span>';
        
        let statusBadge = '';
        let toggleLabel = '';
        let toggleIcon = '';
        if (task.status === 'Completed') {
            statusBadge = `<span class="badge bg-soft-success text-success fs-11" id="${task.id}">Completed</span>`;
            toggleLabel = 'Mark as Pending';
            toggleIcon = 'feather-clock text-warning';
        } else {
            statusBadge = `<span class="badge bg-soft-warning text-warning fs-11" id="${task.id}">Pending</span>`;
            toggleLabel = 'Mark as Completed';
            toggleIcon = 'feather-check-circle text-success';
        }

        const actionBtn = `
        <div class="text-center">
            <div class="action-kebab-wrapper">
                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                <div class="action-kebab-dropdown">
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="toggleTaskStatus('${task.id}')"><i class="${toggleIcon} me-1"></i> ${toggleLabel}</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openTaskNotesModal('${task.title.replace(/'/g, "\\'")}', '${task.client.replace(/'/g, "\\'")}')"><i class="feather-file-text text-primary me-1"></i> Task Notes</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="deleteTask('${task.id}')"><i class="feather-trash-2 text-danger me-1"></i> Delete Task</a>
                </div>
            </div>
        </div>`;

        tasksDataTable.row.add([
            `<span class="fw-bold text-dark fs-13"><i class="feather-check-circle text-primary me-2"></i>${task.title}</span>`,
            `<span class="fs-13 text-muted">${task.client}</span>`,
            `<span class="fs-13 text-muted">${task.date}</span>`,
            priorityBadge,
            statusBadge,
            actionBtn
        ]);
    });
    tasksDataTable.draw(false);
}

function filterTaskTable(filter) {
    if (!tasksDataTable) return;
    if (filter === 'all') {
        tasksDataTable.search('').columns().search('').draw();
    } else {
        tasksDataTable.search(filter).draw();
    }
}

function handleAddNewTask() {
    const title = $('#taskTitleInput').val().trim();
    const client = $('#taskClientInput').val().trim() || 'General Client';
    const date = $('#taskDateInput').val() || '18 Aug 2026';
    const priority = $('#taskPriorityInput').val();

    if (!title) return;

    const randomId = 'task-status-' + Math.floor(Math.random() * 1000);

    const newTask = {
        title: title,
        client: client,
        date: date,
        priority: priority,
        status: 'Pending',
        id: randomId
    };

    tasksList.push(newTask);
    localStorage.setItem('ss_tasks', JSON.stringify(tasksList));

    // Initialize notes array for the new task
    taskNotes[title] = [{ text: 'Task created.', timestamp: getCurrentDateTimeString() }];
    localStorage.setItem('ss_task_notes', JSON.stringify(taskNotes));

    renderTasksTable();

    $('#newTaskForm')[0].reset();
    $('#taskClientInput').val(null).trigger('change');
    const modalEl = document.getElementById('addTaskModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

$(document).ready(function () {
    tasksDataTable = $('#tasksTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search tasks..."
        }
    });
    
    // Render all tasks from tasksList
    renderTasksTable();
});

document.getElementById('mobile-collapse')?.addEventListener('click', function () {
    document.getElementById('mainSidebar')?.classList.toggle('mob-navigation-active');
});
