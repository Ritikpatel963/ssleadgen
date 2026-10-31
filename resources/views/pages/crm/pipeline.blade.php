@extends('layouts.app')
@section('title', 'Leads Management')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Leads Pipeline & Acquisition Hub</h4>
                    <p class="text-muted fs-13 mb-0">Drag and drop prospect cards between columns to advance lead stages, or click any card for full details.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- View Toggle -->
                    <div class="btn-group btn-group-sm" role="group" id="leadViewToggle">
                        <button type="button" class="btn btn-primary fw-bold" onclick="switchLeadView('list', this)"><i class="feather-list me-1"></i> List View</button>
                        <button type="button" class="btn btn-outline-primary fw-bold" onclick="switchLeadView('kanban', this)"><i class="feather-columns me-1"></i> Kanban</button>
                    </div>
                    @if(auth()->user()->canWrite('leads'))
                        <button type="button" class="btn btn-light btn-sm px-3 fw-bold text-success border-success" data-bs-toggle="modal" data-bs-target="#importLeadsModal"><i class="feather-upload me-1"></i> Import Leads</button>
                        <a href="{{ route('settings.dropdowns', ['group' => 'lead_category']) }}" class="btn btn-outline-primary btn-sm px-3 fw-bold"><i class="feather-list me-1"></i> Manage Dropdowns</a>
                        <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createLeadModal"><i class="feather-plus me-1"></i> Add Lead</button>
                    @endif
                </div>
            </div>

            <form method="GET" action="{{ route('crm.pipeline') }}" class="dash-filter-card">
     <div class="row g-2 align-items-end">
                    <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadFilterSearch" class="form-label fs-12 fw-semibold text-muted mb-1">Lead Search</label>
                        <input id="leadFilterSearch" name="search" type="search" class="form-control fs-13" value="{{ request('search') }}" placeholder="Name, email, or phone">
                    </div>
                   <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadFilterStatus" class="form-label fs-12 fw-semibold text-muted mb-1">Stage</label>
                        <select id="leadFilterStatus" name="status" class="form-select fs-13">
                            <option value="">All stages</option>
                            @foreach($pipelineStages as $stage)
                                <option value="{{ $stage->stage_key }}" @selected(request('status') === $stage->stage_key)>{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadFilterSource" class="form-label fs-12 fw-semibold text-muted mb-1">Lead Source</label>
                        <select id="leadFilterSource" name="source" class="form-select fs-13">
                            <option value="">All sources</option>
                            @foreach($leadSourceCategories as $sourceCategory)
                                <option value="{{ $sourceCategory->name }}" @selected(request('source') === $sourceCategory->name)>{{ $sourceCategory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadFilterAdviser" class="form-label fs-12 fw-semibold text-muted mb-1">Advisor</label>
                        <select id="leadFilterAdviser" name="adviser" class="form-select fs-13">
                            <option value="">All advisors</option>
                            @foreach($advisers as $adviserName)
                                <option value="{{ $adviserName }}" @selected(request('adviser') === $adviserName)>{{ $adviserName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadCreatedFrom" class="form-label fs-12 fw-semibold text-muted mb-1">Created From</label>
                        <input id="leadCreatedFrom" name="created_from" type="date" class="form-control fs-13" value="{{ request('created_from') }}">
                    </div>
                    <div class="col-xxl col-xl-2 col-lg-4 col-md-6">
                        <label for="leadCreatedTo" class="form-label fs-12 fw-semibold text-muted mb-1">Created To</label>
                        <input id="leadCreatedTo" name="created_to" type="date" class="form-control fs-13" value="{{ request('created_to') }}">
                    </div>
                     <div class="col-xxl-auto col-xl-2 col-lg-4 col-md-6 d-flex gap-2">
                        <button class="btn btn-primary btn-sm fw-bold px-3" type="submit"><i class="feather-filter me-1"></i> Filter</button>
                        <a href="{{ route('crm.pipeline') }}" class="btn btn-light btn-sm px-3">Reset</a>
                    </div>
                </div>
            </form>

                                    <!-- 1. KANBAN VIEW CONTAINER WITH FULL INTERACTIVE DRAG AND DROP -->
                        <div id="kanbanViewContainer" class="row g-3 d-none" data-can-write-leads="{{ auth()->user()->canWrite('leads') ? '1' : '0' }}">
                @php
                    $stageColors = [
                        'new' => 'primary',
                        'contacted' => 'info',
                        'proposal' => 'warning',
                        'won' => 'success',
                    ];
                    $stages = [];
                    foreach ($pipelineStages as $pStage) {
                        $k = $pStage->stage_key;
                        $stages[$k] = [
                            'label' => $pStage->name,
                            'color' => $stageColors[$k] ?? 'secondary',
                        ];
                    }
                    if (empty($stages)) {
                        $stages = [
                            'new' => ['label' => 'New Leads', 'color' => 'primary'],
                            'contacted' => ['label' => 'Contacted', 'color' => 'info'],
                            'proposal' => ['label' => 'Underwriting', 'color' => 'warning'],
                            'won' => ['label' => 'Policy Issued', 'color' => 'success'],
                        ];
                    }
                    $colWidth = count($stages) > 0 ? (count($stages) <= 4 ? (int)(12 / count($stages)) : 3) : 3;
                @endphp

                @foreach($stages as $status => $stage)
                @php
                    $stageTotal = $stageCounts[$status] ?? (isset($leadsByStatus[$status]) ? count($leadsByStatus[$status]) : 0);
                @endphp
                <div class="col-md-{{ $colWidth }} kanban-column-box" data-status-class="border-{{ $stage['color'] }}">
                    <div class="card-widget-kanban" data-status="{{ $status }}" ondragover="handleKanbanDragOver(event)" ondrop="handleKanbanDrop(event, this)">
                        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                            <h6 class="fw-bold text-dark mb-0"><i class="feather-circle text-{{ $stage['color'] }} me-2"></i> {{ $stage['label'] }} <span class="kanban-count text-{{ $stage['color'] }}">({{ $stageTotal }})</span></h6>
                        </div>
                        <div class="kanban-card-dropzone" 
                             data-status="{{ $status }}"
                             data-stage-label="{{ $stage['label'] }}"
                             data-stage-color="{{ $stage['color'] }}"
                             data-offset="0"
                             data-total="{{ $stageTotal }}"
                             data-has-more="{{ $stageTotal > 0 ? 'true' : 'false' }}"
                             style="height: calc(100vh - 300px); min-height: 520px;">
                            <div class="kanban-scroll-loader text-center py-2 d-none">
                                <span class="spinner-border spinner-border-sm text-{{ $stage['color'] }}" role="status"></span>
                                <span class="fs-11 text-muted ms-1">Loading more...</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- 2. LIST VIEW CONTAINER (Initially Hidden) -->
            <div id="listViewContainer" class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="leadsDataTable" data-server-paginated data-source="{{ route('crm.pipeline.leads-data') }}" data-can-write="{{ auth()->user()->canWrite('leads') ? '1' : '0' }}" data-is-admin="{{ auth()->user()->isSuperAdmin() ? '1' : '0' }}">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold">
                                <th>Lead Name</th>
                                <th>Mobile Number</th>
                                <th>Email ID</th>
                                <th>Source</th>
                                <th>Adviser</th>
                                <th>Stage</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal fade" id="importLeadsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                            <h5 class="modal-title text-white mb-0"><i class="feather-upload me-2"></i> Import Leads</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="importLeadsForm" action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault(); handleBatchImportLeads();">
                            @csrf
                            <input type="hidden" name="destination" value="leads">
                            <input type="hidden" name="file_path" id="leadsImportFilePath" value="">
                            <input type="hidden" name="sheet_index" id="leadsImportSheetIndex" value="0">
                            <div class="modal-body p-4" id="leadsImportStep1">
                                <div class="mb-3">
                                    <label for="leadImportSourceSelect" class="form-label fw-semibold fs-13 text-dark">Lead source for this upload</label>
                                    <select id="leadImportSourceSelect" class="form-select form-select-sm">
                                        <option value="">Use source values from the spreadsheet</option>
                                        @foreach($leadSourceCategories as $sourceCategory)
                                            <option value="{{ $sourceCategory->name }}">{{ $sourceCategory->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Categories are managed in Access Control → Category Master.</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold fs-13 text-dark mb-2">Upload Excel or CSV File <span class="text-danger">*</span></label>
                                    <div class="drag-drop-box" onclick="document.getElementById('importLeadsFileInput').click();"
                                        ondragover="event.preventDefault(); event.stopPropagation(); this.classList.add('drag-over');"
                                        ondragleave="event.preventDefault(); event.stopPropagation(); this.classList.remove('drag-over');"
                                        ondrop="event.preventDefault(); event.stopPropagation(); this.classList.remove('drag-over'); const fileInput = document.getElementById('importLeadsFileInput'); if (event.dataTransfer.files.length) { fileInput.files = event.dataTransfer.files; fileInput.dispatchEvent(new Event('change', { bubbles: true })); }">
                                        <i class="feather-file-text fs-1 text-primary mb-2"></i>
                                        <h6 class="fw-bold text-dark mb-1">Click to browse or drag & drop file here</h6>
                                        <p class="text-muted fs-12 mb-0">Supports .CSV, .XLS, or .XLSX spreadsheets</p>
                                        <input type="file" id="importLeadsFileInput" name="import_file" accept=".csv, .xls, .xlsx" style="display: none;" onchange="handleLeadImportFileSelected(this)">
                                    </div>
                                    <div id="leadsFileNameDisplay" class="fs-12 fw-bold text-primary mt-2"></div>
                                    <div id="leadsImportSheetSelector" class="mt-3 d-none">
                                        <label for="leadsImportSheetSelect" class="form-label fw-semibold fs-13 text-dark">Worksheet</label>
                                        <select id="leadsImportSheetSelect" class="form-select form-select-sm"></select>
                                    </div>
                                </div>

                                <div class="bg-light p-3 rounded-3 mb-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fw-bold fs-13 text-dark"><i class="feather-info me-1 text-info"></i> Column Mapping</span>
                                        <a href="#" onclick="downloadLeadsTemplate(event);" class="fs-12 text-primary text-decoration-none fw-semibold"><i class="feather-download me-1"></i> Download CSV Template</a>
                                    </div>
                                    <p class="fs-12 text-muted mb-0">Upload a spreadsheet with any column names, map the columns to lead fields, and preview rows before importing. First Name and Email or Phone are required.</p>
                                </div>
                                <div id="leadImportError" class="alert alert-danger fs-12 mt-2 d-none" role="alert"></div>
                            </div>

                            <div class="modal-body p-4 d-none" id="leadsImportStep2">
                                <h6 class="fw-bold text-dark mb-3">Map Columns <span id="leadsSelectedSheetName" class="text-muted fs-13 fw-normal"></span></h6>
                                <p class="text-muted fs-13 mb-3">Select the lead field that matches each spreadsheet column. Leave unrelated columns set to Do Not Import.</p>
                                <div class="table-responsive mapping-scroll-region" role="region" aria-label="Lead column mapping" tabindex="0" style="height: min(55vh, 400px); max-width: 100%; overflow: auto; overscroll-behavior: contain;">
                                    <table class="table table-bordered table-sm align-middle fs-13" id="leadsMappingTable">
                                        <thead class="bg-light sticky-top"><tr><th>Your File Column</th><th>Lead Field</th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div id="leadsMappingError" class="alert alert-danger fs-12 d-none" role="alert"></div>
                            </div>

                            <div class="modal-body p-4 d-none" id="leadsImportStep3">
                                <h6 class="fw-bold text-dark mb-3">Data Preview</h6>
                                <p class="text-muted fs-13 mb-3">Review the mapped rows. Rows missing First Name or both Email and Phone will be skipped.</p>
                                <div class="table-responsive mapping-scroll-region" role="region" aria-label="Lead data preview" tabindex="0" style="height: min(55vh, 400px); max-width: 100%; overflow: auto; overscroll-behavior: contain;">
                                    <table class="table table-bordered table-sm align-middle fs-12" id="leadsPreviewTable">
                                        <thead class="bg-light sticky-top"></thead><tbody></tbody>
                                    </table>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span class="fs-12 text-muted" id="leadsPreviewPageSummary"></span>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Lead import preview pages">
                                        <button type="button" class="btn btn-outline-secondary" id="leadsPreviewPreviousButton" onclick="handleLeadImportPreview(Math.max(0, currentLeadPreviewOffset - currentLeadPreviewLimit))" disabled>Previous</button>
                                        <button type="button" class="btn btn-outline-secondary" id="leadsPreviewNextButton" onclick="handleLeadImportPreview(currentLeadPreviewOffset + currentLeadPreviewLimit)" disabled>Next</button>
                                    </div>
                                </div>
                                <div id="leadsProcessError" class="alert alert-danger fs-12 mt-3 d-none" role="alert"></div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="leadsSkipErrorsCheck" checked>
                                    <label class="form-check-label fs-13 text-dark" for="leadsSkipErrorsCheck">Skip invalid rows and import valid leads</label>
                                </div>
                            </div>

                            <div class="modal-footer bg-light d-flex justify-content-between" id="leadsImportFooterStep1">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold" id="importLeadsSubmitBtn" disabled>Continue to Mapping <i class="feather-arrow-right ms-1"></i></button>
                            </div>
                            <div class="modal-footer bg-light d-flex justify-content-between d-none" id="leadsImportFooterStep2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showLeadImportStep(1)"><i class="feather-arrow-left me-1"></i> Back</button>
                                <button type="button" class="btn btn-primary btn-sm fw-bold" id="leadsPreviewButton" onclick="handleLeadImportPreview()">Generate Preview <i class="feather-arrow-right ms-1"></i></button>
                            </div>
                            <div class="modal-footer bg-light d-flex justify-content-between d-none" id="leadsImportFooterStep3">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showLeadImportStep(2)"><i class="feather-arrow-left me-1"></i> Back</button>
                                <button type="button" class="btn btn-success btn-sm fw-bold" id="leadsProcessButton" onclick="handleLeadImportProcess()"><i class="feather-check-circle me-1"></i> Confirm &amp; Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="leadDetailModal" tabindex="-1">
                <div class="modal-dialog modal-fullscreen lead-overview-dialog">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                            <h5 class="modal-title text-white mb-0" id="leadDetailModalTitle">
                                <i class="feather-user me-2"></i> Prospect Lead Overview
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4" id="leadDetailModalBody" style="max-height: 75vh; overflow-y: auto;">
                            {{-- Content is injected by openLeadDetailModal() JS function --}}
                        </div>
                        <div class="modal-footer bg-light d-flex justify-content-between">
                            @if(auth()->user()->canWrite('leads'))
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-primary fw-bold" onclick="editLeadFromDetailModal()">
                                    <i class="feather-edit me-1"></i> Edit Lead
                                </button>
                                <form id="leadConvertForm" method="POST" action="">
                                    @csrf
                                    <button type="button" id="leadConvertBtn" class="btn fw-bold text-white" onclick="openLeadConvertModal()" style="background-color:#00A8B5;border-color:#00A8B5;" onmouseover="this.style.backgroundColor='#008C97'" onmouseout="this.style.backgroundColor='#00A8B5'">
                                        <i class="feather-arrow-right me-1"></i> Convert to Login
                                    </button>
                                </form>
                            </div>
                            @else
                            <span></span>
                            @endif
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="leadConvertConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                            <h5 class="modal-title text-white mb-0"><i class="feather-arrow-right me-2"></i> Convert Lead</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-4">
                            <div class="text-info mb-3" style="font-size: 2.5rem;"><i class="feather-help-circle"></i></div>
                            <h5 class="fw-bold text-dark mb-2">Convert this lead to Login Client?</h5>
                            <p class="text-muted mb-0" id="leadConvertConfirmName"></p>
                        </div>
                        <div class="modal-footer bg-light justify-content-center gap-2">
                            <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="confirmLeadConversion()">Convert</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="createLeadModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                            <h5 class="modal-title text-white mb-0"><i class="feather-user-plus me-2"></i> Add New Login Client Entry</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('crm.store') }}" method="POST">
                            @csrf
                            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- SECTION 1: POLICY & PROVIDER INFO -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Policy No.</label>
                                            <input type="text" class="form-control" name="policy_no" placeholder="e.g. POL-2026-9912" maxlength="30">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                            <select class="form-select" name="company">
                                                <option value="" selected>Select Company</option>
                                        @foreach($insuranceCompanies as $company)
                                            <option value="{{ $company->name }}">{{ $company->name }}</option>
                                        @endforeach
</select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Login Date</label>
                                            <input type="date" class="form-control" name="login_date">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label>
                                            <input type="number" step="0.01" class="form-control" name="anp" placeholder="2500.00">
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 2: CLIENT PERSONAL & CONTACT -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-user text-primary fs-15"></i> 2. Client Contact Information
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">First Name <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control" required placeholder="e.g. Rahul" value="{{ old('first_name') }}" maxlength="50">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control" required placeholder="e.g. Sharma" value="{{ old('last_name') }}" maxlength="50">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                            <input type="date" name="dob" class="form-control">
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Mobile Number <span class="text-danger">*</span></label>
                                            <input type="text" name="phone" class="form-control" placeholder="021 XXX XXXX" value="{{ old('phone') }}" required maxlength="15">
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                                            <input type="email" name="email" class="form-control" placeholder="client@example.com" value="{{ old('email') }}" maxlength="100">
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Lead Source</label>
                                            <select name="lead_source" class="form-select">
                                                <option value="">Select Lead Source</option>
                                                @foreach($leadSourceCategories as $sourceCategory)
                                                    <option value="{{ $sourceCategory->name }}" @selected(old('lead_source') === $sourceCategory->name)>{{ $sourceCategory->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Pipeline Stage</label>
                                            <select name="status" class="form-select">
                                                @foreach($pipelineStages as $stage)
                                                    <option value="{{ $stage->stage_key }}" @selected(old('status', 'new') === $stage->stage_key)>{{ $stage->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 3: RESIDENTIAL ADDRESS -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-map-pin text-primary fs-15"></i> 3. Address Details
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Street Address</label>
                                            <input type="text" class="form-control" name="address" placeholder="e.g. 42 Queen Street" maxlength="150">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">Suburb</label>
                                            <input type="text" class="form-control" name="suburb" placeholder="e.g. Central" maxlength="50">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">City</label>
                                            <input type="text" class="form-control" name="city" placeholder="e.g. Auckland" maxlength="50">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">Post Code</label>
                                            <input type="text" class="form-control" name="post_code" placeholder="1010" maxlength="10">
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 4: COMPLIANCE & STATUS -->
                                <div class="modal-section-card mb-0">
                                    <div class="modal-section-title">
                                        <i class="feather-clipboard text-primary fs-15"></i> 4. Compliance & Processing Status
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Adviser</label>
                                            <select class="form-select" name="adviser">
                                                 <option value="" disabled {{ in_array(auth()->user()->name, $advisers->toArray()) ? '' : 'selected' }}>Select Adviser</option>>
                                                @foreach($advisers as $adviserName)
                                                    <option value="{{ $adviserName }}" {{ auth()->user()->name === $adviserName ? 'selected' : '' }}>{{ $adviserName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Not Counting</label>
                                            <select class="form-select" name="not_counting">
                                                <option value="" disabled selected>Select</option>
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Compliance by</label>
                                            <input type="text" class="form-control" name="compliance_by" placeholder="Officer Name" maxlength="50">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label>
                                            <input type="date" class="form-control" name="roa_due_date">
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label>
                                            <select class="form-select" name="status_compliance">
                                                <option value="" disabled selected>Select Status</option>
                                                <option value="Sent to Compliance">Sent to Compliance</option>
                                                <option value="In Review">In Review</option>
                                                <option value="Approved">Approved</option>
                                              
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label>
                                            <select class="form-select" name="sent_to_client">
                                                <option value="" disabled selected>Select Status</option>
                                                <option value="Pending">Pending</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label>
                                            <input type="text" class="form-control" name="outcome" placeholder="e.g. Pending Medical Test" maxlength="255">
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm fw-bold">Create Lead</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal: Edit Lead -->
            <div class="modal fade" id="editLeadModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                            <h5 class="modal-title text-white mb-0"><i class="feather-edit me-2"></i> Edit Lead Profile - <span id="editLeadNameHeader"></span></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editLeadForm" method="POST" action="" onsubmit="handleEditLeadSubmit(event)">
                            @csrf
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="lead_id" id="editLeadId" value="">
                            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                                <div id="editLeadAlertError" class="alert alert-danger d-none mb-3"></div>

                                <!-- SECTION 1: POLICY & PROVIDER INFO -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-shield text-primary fs-15"></i> 1. Policy & Insurance Provider
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Policy No.</label>
                                            <input type="text" class="form-control" name="policy_no" id="editLeadPolicyNo" placeholder="e.g. POL-2026-9912" maxlength="30">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Insurance Company</label>
                                            <select class="form-select" name="company" id="editLeadCompany">
                                                <option value="">Select Company</option>
                                                @foreach($insuranceCompanies as $company)
                                                    <option value="{{ $company->name }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Login Date</label>
                                            <input type="date" class="form-control" name="login_date" id="editLeadLoginDate">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">ANP ($)</label>
                                            <input type="number" step="0.01" class="form-control" name="anp" id="editLeadAnp" placeholder="2500.00">
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 2: CLIENT PERSONAL & CONTACT -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-user text-primary fs-15"></i> 2. Client Contact Information
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">First Name <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" id="editLeadFirstName" class="form-control" required placeholder="e.g. Rahul" maxlength="50">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" id="editLeadLastName" class="form-control" required placeholder="e.g. Sharma" maxlength="50">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Date of Birth</label>
                                            <input type="date" name="dob" id="editLeadDob" class="form-control">
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Mobile Number <span class="text-danger">*</span></label>
                                            <input type="text" name="phone" id="editLeadPhone" class="form-control" placeholder="021 XXX XXXX" required maxlength="15">
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Email Address</label>
                                            <input type="email" name="email" id="editLeadEmail" class="form-control" placeholder="client@example.com" maxlength="100">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Lead Source</label>
                                            <select name="lead_source" id="editLeadSource" class="form-select">
                                                <option value="">Select Lead Source</option>
                                                @foreach($leadSourceCategories as $sourceCategory)
                                                    <option value="{{ $sourceCategory->name }}">{{ $sourceCategory->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Target Cover Premium ($)</label>
                                            <input type="number" class="form-control" name="estimated_cover" id="editLeadEstimatedCover" placeholder="5000">
                                        </div>
                                        <div class="col-md-4 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Pipeline Stage</label>
                                            <select name="status" id="editLeadStatus" class="form-select">
                                                @foreach($pipelineStages as $stage)
                                                    <option value="{{ $stage->stage_key }}">{{ $stage->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 3: RESIDENTIAL ADDRESS -->
                                <div class="modal-section-card">
                                    <div class="modal-section-title">
                                        <i class="feather-map-pin text-primary fs-15"></i> 3. Address Details
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Street Address</label>
                                            <input type="text" class="form-control" name="address" id="editLeadAddress" placeholder="e.g. 42 Queen Street" maxlength="150">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">Suburb</label>
                                            <input type="text" class="form-control" name="suburb" id="editLeadSuburb" placeholder="e.g. Central" maxlength="50">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">City</label>
                                            <input type="text" class="form-control" name="city" id="editLeadCity" placeholder="e.g. Auckland" maxlength="50">
                                        </div>
                                        <div class="col-md-2 col-sm-4">
                                            <label class="form-label fw-semibold fs-13 text-dark">Post Code</label>
                                            <input type="text" class="form-control" name="post_code" id="editLeadPostCode" placeholder="1010" maxlength="10">
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 4: COMPLIANCE & STATUS -->
                                <div class="modal-section-card mb-0">
                                    <div class="modal-section-title">
                                        <i class="feather-clipboard text-primary fs-15"></i> 4. Compliance & Processing Status
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Adviser</label>
                                            <select class="form-select" name="adviser" id="editLeadAdviser">
                                                <option value="">Select Adviser</option>
                                                @foreach($advisers as $adviserName)
                                                    <option value="{{ $adviserName }}">{{ $adviserName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Not Counting</label>
                                            <select class="form-select" name="not_counting" id="editLeadNotCounting">
                                                <option value="">Select</option>
                                                <option value="No">No</option>
                                                <option value="Yes">Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Compliance by</label>
                                            <input type="text" class="form-control" name="compliance_by" id="editLeadComplianceBy" placeholder="Officer Name" maxlength="50">
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">RoA Due on</label>
                                            <input type="date" class="form-control" name="roa_due_date" id="editLeadRoaDueDate">
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Status - Sent to Compliance</label>
                                            <select class="form-select" name="status_compliance" id="editLeadStatusCompliance">
                                                <option value="">Select Status</option>
                                                <option value="Sent to Compliance">Sent to Compliance</option>
                                                <option value="In Review">In Review</option>
                                                <option value="Approved">Approved</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label class="form-label fw-semibold fs-13 text-dark">Sent to Client</label>
                                            <select class="form-select" name="sent_to_client" id="editLeadSentToClient">
                                                <option value="">Select Status</option>
                                                <option value="Pending">Pending</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-sm-12">
                                            <label class="form-label fw-semibold fs-13 text-dark">Outcome / Pending Requirements</label>
                                            <input type="text" class="form-control" name="outcome" id="editLeadOutcome" placeholder="e.g. Pending Medical Test" maxlength="255">
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" id="editLeadSubmitBtn"><i class="feather-check me-1"></i> Update Lead Record</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js?v=9.0') }}"></script>
    <script src="{{ asset('assets/js/pages/crm-leads-pipeline.js?v=' . filemtime(public_path('assets/js/pages/crm-leads-pipeline.js'))) }}"></script>
@endpush

@push('styles')
   <style>
        .drag-drop-box {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 22px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .drag-drop-box.drag-over {
            border-color: #00a8b5;
            background-color: rgba(0, 168, 181, 0.08);
        }

        /* Kanban Column & Card Spacing Enhancements */
        .kanban-card-dropzone {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 4px 6px 12px 2px !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .kanban-card-dropzone::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-card-dropzone::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-card-dropzone::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 6px;
        }

        .kanban-card-dropzone::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        .lead-kanban-card {
            margin-bottom: 12px !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.07), 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            background-color: #ffffff !important;
            border-radius: 8px !important;
            display: block !important;
            min-height: 80px !important;
            height: auto !important;
            overflow: visible !important;
            padding-bottom: 14px !important;
        }

        .lead-kanban-card:last-child {
            margin-bottom: 0 !important;
        }

        .lead-kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .lead-overview-dialog {
            max-width: 860px;
            transition: all 0.25s ease;
        }

        .lead-overview-dialog.modal-fullscreen {
            width: 100vw !important;
            max-width: 100vw !important;
            height: 100% !important;
            margin: 0 !important;
        }

        .lead-overview-dialog.modal-fullscreen .modal-content {
            height: 100vh !important;
            border-radius: 0 !important;
        }

        .lead-overview-dialog:not(.modal-fullscreen) {
            max-width: 1200px;
            width: 95vw;
            margin: 1rem auto;
            transition: max-width 0.25s ease, width 0.25s ease;
            margin: 1.5rem auto;
        }

        .lead-overview-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .lead-overview-content .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 150px);
            flex: 1 1 auto;
        }

        .lead-notes-scroll-container {
            min-height: 220px;
            max-height: 380px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #64748b #f1f5f9;
        }

        .lead-overview-dialog.modal-fullscreen .lead-notes-scroll-container {
            min-height: 260px;
            max-height: calc(100vh - 390px);
        }

        .lead-notes-scroll-container::-webkit-scrollbar {
            width: 9px;
        }

        .lead-notes-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
            margin: 3px 0;
            border: 1px solid #e2e8f0;
        }

        .lead-notes-scroll-container::-webkit-scrollbar-thumb {
            background: #64748b;
            border-radius: 8px;
            border: 2px solid #f1f5f9;
        }

        .lead-notes-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #334155;
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

@endsection

