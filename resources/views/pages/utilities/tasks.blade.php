@extends('layouts.app')
@section('title', 'Tasks & Follow ups')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Tasks & Advisory Follow-ups</h4>
                    <p class="text-muted fs-13 mb-0">Organize client call-backs, renewal check-ins, and claim
                        verifications.</p>
                </div>
                <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal"
                    data-bs-target="#addTaskModal"><i class="feather-plus me-1"></i> Add Task</button>
            </div>

            <!-- Task Category Tabs -->
            <ul class="nav nav-tabs task-filter-tabs mb-2" id="taskFilterTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" data-filter="all" onclick="filterTaskTable('all')">All Tasks</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" data-filter="Pending" onclick="filterTaskTable('Pending')">Pending</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold text-success" data-filter="Completed" onclick="filterTaskTable('Completed')">Completed
                        Tasks</button>
                </li>
            </ul>

            <!-- Task Table Card -->
            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="tasksTable">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Client Name</th>
                                <th>Task Description</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                            <tr data-task-id="{{ $task->id }}">
                                <td class="fs-13 text-muted">{{ $task->client_name ?? 'General Client' }}</td>
                                <td class="fs-13">
                                    <div class="fw-bold text-dark"><i class="feather-check-circle text-primary me-2"></i>{{ $task->title }}</div>
                                    <div class="mt-1 task-note-preview-container {{ $task->notes->isEmpty() ? 'd-none' : '' }}">
                                        <a href="javascript:void(0);" class="task-notes-btn text-decoration-none" data-task-id="{{ $task->id }}" data-title="{{ e($task->title) }}" data-client-name="{{ e($task->client_name ?? 'General Client') }}" data-notes="{{ rawurlencode($task->notes->toJson()) }}" data-due="{{ $task->due_date->format('d M Y') . ($task->due_time ? ' at ' . \Carbon\Carbon::parse($task->due_time)->format('h:i A') : '') }}" data-status="{{ $task->status }}">
                                            <span class="badge bg-soft-primary text-primary fs-11"><i class="feather-message-square me-1"></i><span class="task-note-count">{{ $task->notes->count() }} {{ Str::plural('note', $task->notes->count()) }}</span></span>
                                            <span class="fs-11 text-muted ms-1 task-note-preview">{{ Str::limit($task->notes->first()?->note, 45) }}</span>
                                        </a>
                                    </div>
                                </td>
                                <td class="fs-13 text-muted">
                                    {{ $task->due_date->format('d M Y') }}
                                    @if($task->due_time)
                                        <br><small class="text-primary"><i class="feather-clock"></i> {{ \Carbon\Carbon::parse($task->due_time)->format('h:i A') }}</small>
                                    @endif
                                </td>

                                <td>
                                    @if($task->status === 'Completed')
                                        <span class="badge bg-soft-success text-success fs-11">Completed</span>
                                    @else
                                        <span class="badge bg-soft-warning text-warning fs-11">Pending</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="action-kebab-wrapper">
                                        <button class="action-kebab-btn" type="button">
                                            <i class="feather-more-vertical"></i>
                                        </button>
                                        <div class="action-kebab-dropdown">
                                            <!-- Toggle Status -->
                                            <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST" class="m-0 task-status-form">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2">
                                                    @if($task->status === 'Completed')
                                                        <i class="feather-clock text-warning me-2"></i> Mark as Pending
                                                    @else
                                                        <i class="feather-check-circle text-success me-2"></i> Mark as Completed
                                                    @endif
                                                </button>
                                            </form>
                                            
                                            <!-- Task Notes -->
                                            <button type="button" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2 task-notes-btn"
                                                data-task-id="{{ $task->id }}" data-title="{{ e($task->title) }}" data-client-name="{{ e($task->client_name ?? 'General Client') }}" data-notes="{{ rawurlencode($task->notes->toJson()) }}" data-due="{{ $task->due_date->format('d M Y') . ($task->due_time ? ' at ' . \Carbon\Carbon::parse($task->due_time)->format('h:i A') : '') }}" data-status="{{ $task->status }}">
                                                <i class="feather-file-text text-primary me-2"></i> Task Notes
                                            </button>
                                            
                                            <!-- Delete Task -->
                                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="m-0 task-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-kebab-item border-0 bg-transparent text-start w-100 fs-12 fw-semibold py-2 text-danger">
                                                    <i class="feather-trash-2 text-danger me-2"></i> Delete Task
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No tasks found. Click "Add Task" to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            

</div>
    </div>

    <!-- Modal: Add New Task -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-square me-2"></i> Add New Task</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="newTaskForm" action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Task Title *</label>
                            <input type="text" name="title" class="form-control" id="taskTitleInput"
                                placeholder="e.g. Schedule Income Protection Review" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Associated Client</label>
                            <select name="client_name" class="form-select client-name-search" id="taskClientInput"
                                data-ajax-url="{{ route('tasks.clients.search') }}"
                                data-placeholder="Select or search client...">
                                <option value=""></option>
                                @foreach($initialClients as $client)
                                    @php $clientFullName = trim($client->first_name . ' ' . $client->last_name); @endphp
                                    @if(!empty($clientFullName))
                                        <option value="{{ $clientFullName }}">{{ $clientFullName }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Due Date</label>
                                <input type="date" name="due_date" class="form-control" id="taskDateInput" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Time</label>
                                <input type="time" name="due_time" class="form-control" id="taskTimeInput">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold fs-13 text-dark">Priority</label>
                                <select name="priority" class="form-select" id="taskPriorityInput">
                                    <option value="High">High</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Task Notes (Single-Column, Clean & Structured) -->
    <div class="modal fade" id="taskNotesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen task-notes-dialog">
            <div class="modal-content border-0 shadow-lg task-notes-content">
                <div class="modal-header text-white py-3 px-4" style="background-color: var(--color-navy-dark, #0A192F);">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="avatar-text avatar-sm bg-soft-primary text-primary rounded-circle" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="feather-file-text fs-16"></i>
                        </div>
                        <div>
                            <h5 class="modal-title text-white mb-0 fw-bold d-flex align-items-center flex-wrap gap-2">
                                <span>Notes for Task:</span>
                                <span id="taskNotesTitleHeader" class="text-info fw-bold"></span>
                            </h5>
                        </div>
                        <span class="badge bg-primary text-white ms-2 fs-12" id="taskNotesCountBadge">0 Notes</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light d-none d-md-inline-flex align-items-center gap-1" id="toggleTaskNotesFullscreenBtn" title="Exit Fullscreen">
                            <i class="feather-minimize-2"></i>
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 bg-light-subtle">
                    <!-- Top Meta Bar (clean, no highlight boxes) -->
                    <div class="bg-white rounded-3 mb-3 px-3 py-2 border d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-1 fs-13">
                                <i class="feather-user text-primary fs-14"></i>
                                <span class="text-muted fw-semibold">Client:</span>
                                <span id="taskNotesClientSpan" class="fw-bold text-dark ms-1"></span>
                            </div>
                            <div class="d-flex align-items-center gap-1 fs-13" id="taskNotesDueWrapper" style="display: none !important;">
                                <i class="feather-calendar text-muted fs-14"></i>
                                <span class="text-muted fw-semibold">Due:</span>
                                <span id="taskNotesDueSpan" class="text-dark fw-medium ms-1"></span>
                            </div>
                            <div class="d-flex align-items-center gap-1 fs-13" id="taskNotesStatusWrapper" style="display: none !important;">
                                <span id="taskNotesStatusBadge"></span>
                            </div>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="feather-search fs-12"></i></span>
                            <input type="text" id="taskNotesSearchInput" class="form-control border-start-0 fs-12" placeholder="Search in task notes..." />
                        </div>
                    </div>

                    <!-- Single-Column Notes Card (full width, matches Lead Overview style) -->
                    <div class="bg-white rounded-3 border shadow-sm p-3">
                        <!-- Notes History Section -->
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="feather-clock text-primary"></i> Notes History Timeline
                            </h6>
                            <span class="fs-12 text-muted fw-medium badge bg-light text-dark border" id="taskNotesShowingCount">Showing 0 entries</span>
                        </div>
                         <div id="taskNotesListContainer" class="task-notes-scroll-container pe-1 mb-3">
                            <!-- Notes loaded dynamically -->
                        </div>

                        <!-- Add Note Composer (same full width, directly below) -->
                        <div class="border-top pt-3">
                            <form id="taskNoteForm" method="POST" action="">
                                @csrf
                                <div class="mb-2">
                                    <textarea name="note" id="newTaskNoteInput" class="form-control fs-13 w-100" style="min-height: 80px; max-height: 160px; resize: vertical;" placeholder="Write a new note... (Ctrl + Enter to save)" required></textarea>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-muted fs-11"><i class="feather-info me-1"></i>Ctrl+Enter to save</small>
                                    <button class="btn btn-primary btn-sm fw-bold px-3" id="addTaskNoteBtn" type="submit">
                                        <i class="feather-plus me-1"></i> Add Note
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between flex-shrink-0">
                    <span class="fs-12 text-muted">All notes are saved permanently to this task timeline.</span>
                    <button type="button" class="btn btn-light btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@push('styles')
     <style>
        .task-notes-dialog {
            transition: all 0.25s ease;
        }
        .task-notes-dialog.modal-fullscreen {
            width: 100vw !important;
            max-width: 100vw !important;
            height: 100% !important;
            margin: 0 !important;
        }
        .task-notes-dialog.modal-fullscreen .modal-content {
            height: 100vh !important;
            border-radius: 0 !important;
        }
        .task-notes-dialog:not(.modal-fullscreen) {
            max-width: 1200px;
            width: 95vw;
            margin: 1.5rem auto;
        }
        .task-notes-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .task-notes-content .modal-body {
            overflow-y: auto;
            flex: 1 1 auto;
        }
        .task-notes-scroll-container {
            min-height: 220px;
            max-height: 380px;
            overflow-y: auto;
        }
        .task-notes-dialog.modal-fullscreen .task-notes-scroll-container {
            min-height: 260px;
            max-height: calc(100vh - 390px);
        }
        /* Prominent Smooth Vertical Scroll Slider */
        .task-notes-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #64748b #f1f5f9;
        }
        .task-notes-scroll-container::-webkit-scrollbar {
            width: 9px;
        }
        .task-notes-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
            margin: 3px 0;
            border: 1px solid #e2e8f0;
        }
        .task-notes-scroll-container::-webkit-scrollbar-thumb {
            background: #64748b;
            border-radius: 8px;
            border: 2px solid #f1f5f9;
        }
        .task-notes-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
        .bg-soft-purple {
            background-color: rgba(147, 51, 234, 0.12) !important;
        }
        .text-purple {
            color: #8b5cf6 !important;
        }
        .hover-shadow-sm {
            transition: all 0.2s ease-in-out;
        }
        .hover-shadow-sm:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08) !important;
            border-color: #94a3b8 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/pages/tasks.js?v=' . filemtime(public_path('assets/js/pages/tasks.js'))) }}"></script>
@endpush

@endsection
