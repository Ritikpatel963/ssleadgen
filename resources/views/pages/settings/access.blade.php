@extends('layouts.app')
@section('title', 'Access Control & Role Permissions')

@section('content')

{{-- Page Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h4 class="fw-bold text-dark mb-1">Access Control & Role Permissions</h4>
        <p class="text-muted fs-13 mb-0">Manage active advisor accounts, FSPR numbers, and assigned module permissions.</p>
    </div>
</div>

{{-- Flash Messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show fs-13 d-flex align-items-center gap-2" role="alert">
        <i class="feather-check-circle fs-5"></i>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error') || $errors->any())
    <div class="alert alert-danger alert-dismissible fade show fs-13 d-flex align-items-center gap-2" role="alert">
        <i class="feather-alert-circle fs-5"></i>
        <span>{{ session('error') ?? $errors->first() }}</span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Tab Navigation --}}
<ul class="nav nav-tabs task-filter-tabs mb-3" id="accessTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ request('tab') === 'dropdowns' ? '' : 'active' }} fw-bold" id="users-tab" data-bs-toggle="tab"
            data-bs-target="#usersTabContent" type="button" role="tab">
            <i class="feather-users me-1"></i> Users Management
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="roles-tab" data-bs-toggle="tab"
            data-bs-target="#rolesTabContent" type="button" role="tab">
            <i class="feather-shield me-1"></i> Roles & Permissions
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ request('tab') === 'dropdowns' ? 'active' : '' }} fw-bold" id="dropdowns-tab" data-bs-toggle="tab"
            data-bs-target="#dropdownsTabContent" type="button" role="tab">
            <i class="feather-list me-1"></i> Category Master
        </button>
    </li>
</ul>

<div class="tab-content" id="accessTabContent">

    {{-- ─── Tab 1: Users Management ───────────────────────────────────────── --}}
    <div class="tab-pane fade {{ request('tab') === 'dropdowns' ? '' : 'show active' }}" id="usersTabContent" role="tabpanel">
        <div class="card-widget">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h6 class="widget-title mb-0">Advisor Directory</h6>
                @if(auth()->user()->canWrite('access'))
                    <button class="btn btn-primary btn-sm px-3 fw-bold"
                        data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="feather-user-plus me-1"></i> Add New User
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="usersTable">
                    <thead>
                        <tr class="fs-12 text-muted text-uppercase fw-semibold">
                            <th>User Name</th>
                            <th>Email Address</th>
                            <th>Role Assigned</th>
                            <th>FSPR Number</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            @if(auth()->user()->canWrite('access'))
                                <th class="text-center">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="fw-bold text-dark fs-13">{{ $user->name }}</td>
                                <td class="fs-13 text-muted">{{ $user->email }}</td>
                                <td>
                                    @if($user->role)
                                        <span class="badge {{ $user->role->is_super_admin ? 'bg-soft-primary text-primary' : 'bg-soft-info text-info' }} fs-11 fw-bold">
                                            {{ $user->role->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-soft-secondary text-secondary fs-11">No Role</span>
                                    @endif
                                </td>
                                <td class="fs-13 fw-semibold">{{ $user->fspr_number ?? '—' }}</td>
                                <td>
                                    @if($user->status === 'active')
                                        <span class="status-pill-inforce">Active</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger fs-11 fw-semibold">Inactive</span>
                                    @endif
                                </td>
                                <td class="fs-13 text-muted">
                                    {{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}
                                </td>
                                @if(auth()->user()->canWrite('access'))
                                <td class="text-center">
                                    <div class="action-kebab-wrapper">
                                        <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                        <div class="action-kebab-dropdown">
                                            <a href="javascript:void(0);" class="action-kebab-item"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $user->id }}">
                                                <i class="feather-edit text-primary me-1"></i> Edit User
                                            </a>
                                            @if($user->id !== auth()->id())
                                                @if($user->status === 'active' && auth()->user()->isSuperAdmin())
                                                    <a href="javascript:void(0);" class="action-kebab-item text-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deactivateUserModal-{{ $user->id }}">
                                                        <i class="feather-user-x text-danger me-1"></i> Deactivate
                                                    </a>
                                                @elseif($user->status !== 'active')
                                                    <form method="POST" action="{{ route('users.update', $user) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="active">
                                                        <button type="submit" class="action-kebab-item text-success w-100 text-start border-0 bg-transparent">
                                                            <i class="feather-user-check text-success me-1"></i> Activate
                                                        </button>
                                                    </form>
                                                @endif
                                                @if(auth()->user()->isSuperAdmin())
                                                    <a href="javascript:void(0);" class="action-kebab-item text-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal-{{ $user->id }}">
                                                        <i class="feather-trash-2 text-danger me-1"></i> Delete User
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->canWrite('access') ? 7 : 6 }}" class="text-center text-muted py-4 fs-13">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade {{ request('tab') === 'dropdowns' ? 'show active' : '' }}" id="dropdownsTabContent" role="tabpanel">
        @include('pages.settings.dropdown-values')
    </div>

    {{-- ─── Tab 2: Roles & Permissions Matrix ──────────────────────────────── --}}
    <div class="tab-pane fade" id="rolesTabContent" role="tabpanel">
        <div class="card-widget">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <h6 class="widget-title mb-0">
                    Role Permissions Matrix
                    <span class="badge bg-soft-primary text-primary ms-1 fs-11">{{ $roles->count() }}</span>
                </h6>
                @if(auth()->user()->isSuperAdmin())
                    <button class="btn btn-primary btn-sm px-3 fw-bold"
                        data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="feather-plus me-1"></i> Add New Role
                    </button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="rolesTable">
                    <thead>
                        <tr class="fs-12 text-muted text-uppercase fw-semibold">
                            <th>Role</th>
                            <th>Description</th>
                            <th>Clients</th>
                            <th>Leads</th>
                            <th>Policies</th>
                            <th>Reports</th>
                            <th>Documents</th>
                            <th>Access Ctrl</th>
                            @if(auth()->user()->isSuperAdmin())
                                <th class="text-center">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark fs-13">{{ $role->name }}</span>
                                    @if($role->is_super_admin)
                                        <span class="badge bg-soft-warning text-warning fs-10 ms-1">Super</span>
                                    @endif
                                </td>
                                <td class="fs-12 text-muted" style="max-width:200px;">{{ $role->description }}</td>
                                @foreach(['clients','leads','policies','reports','documents','access'] as $module)
                                    @php $level = $role->permissions[$module] ?? 'none'; @endphp
                                    <td>
                                        @if($level === 'write')
                                            <span class="badge bg-success fs-11">Read/Write</span>
                                        @elseif($level === 'read')
                                            <span class="badge bg-info fs-11">Read</span>
                                        @else
                                            <span class="badge bg-secondary fs-11 opacity-50">None</span>
                                        @endif
                                    </td>
                                @endforeach
                                @if(auth()->user()->isSuperAdmin())
                                    <td class="text-center">
                                        <div class="action-kebab-wrapper">
                                            <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                            <div class="action-kebab-dropdown">
                                                <a href="javascript:void(0);" class="action-kebab-item"
                                                    data-bs-toggle="modal" data-bs-target="#editRoleModal-{{ $role->id }}">
                                                    <i class="feather-edit text-primary me-1"></i> Edit Role
                                                </a>
                                                @if(!$role->is_super_admin)
                                                <a href="javascript:void(0);" class="action-kebab-item text-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteRoleModal-{{ $role->id }}">
                                                    <i class="feather-trash-2 text-danger me-1"></i> Delete Role
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- ALL MODALS (Placed safely outside of tables and card-widgets)          --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="accessModals">
@if(auth()->user()->canWrite('access'))

    {{-- ─── Modals: Edit User (One per user) ─────────────────────────────── --}}
    @foreach($users as $user)
    <div class="modal fade" id="editUserModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background:var(--color-navy-dark,#1e3a5f);">
                    <h5 class="modal-title text-white mb-0 fs-14">
                        <i class="feather-edit me-2"></i> Edit: {{ $user->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PATCH')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13">User Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13">Email</label>
                            <input type="text" class="form-control" value="{{ $user->email }}" disabled>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13">Role Assigned</label>
                                <select class="form-select" name="role_id">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13">FSPR Number</label>
                                <input type="text" class="form-control" name="fspr_number"
                                    value="{{ $user->fspr_number }}" placeholder="FSP-XXXXXX">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    {{-- ─── Modals: Edit Role (One per role) ─────────────────────────────── --}}
    @php
    $moduleLabels = [
        'dashboard' => ['feather-grid',        'Dashboard Overview', false],
        'clients'   => ['feather-users',       'Clients Directory', false],
        'clients_login' => ['feather-corner-down-right', 'Login Client', true],
        'clients_inforce' => ['feather-corner-down-right', 'Inforce Clients', true],
        'clients_inactive' => ['feather-corner-down-right', 'Inactive Clients', true],
        'clients_claims' => ['feather-corner-down-right', 'Claim update', true],
        'clients_cancellation' => ['feather-corner-down-right', 'Cancellation update', true],
        'clients_npw_deferred' => ['feather-corner-down-right', 'NPW Deferred', true],
        'leads'     => ['feather-target',      'Leads Pipeline', false],
        'tasks'     => ['feather-check-square', 'Tasks & Follow-ups', false],
        'calendar'  => ['feather-calendar',    'Calendar', false],
        'policies'  => ['feather-file-text',   'Policies', false],
        'reports'   => ['feather-bar-chart-2', 'Reports & Analytics', false],
        'documents' => ['feather-folder',      'Document Vault', false],
        'access'    => ['feather-lock',        'Access Control', false],
    ];
    @endphp

    @if(auth()->user()->isSuperAdmin())
    @foreach($roles as $role)
    <div class="modal fade" id="editRoleModal-{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark, #0B192C);">
                    <h5 class="modal-title text-white mb-0">
                        <i class="feather-shield me-2"></i> Edit Role: {{ $role->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body px-4 py-3">
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13 text-dark">
                                    Role Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" class="form-control"
                                    value="{{ $role->name }}" required {{ $role->is_super_admin ? 'readonly' : '' }}>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13 text-dark">Role Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of role capabilities">{{ $role->description }}</textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark fs-13 mb-2">
                            Module Permission Levels (All Sidebar Menus)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle fs-13 mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold text-uppercase fs-11 text-dark">Sidebar Navigation Menu</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:100px;">Read Only</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:100px;">Read / Write</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:90px;">None</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\Role::modules() as $module)
                                        @php
                                            $info = $moduleLabels[$module] ?? ['feather-circle', $module, false];
                                            $icon = $info[0];
                                            $label = $info[1];
                                            $isSub = $info[2] ?? false;
                                            $curLevel = $role->permissions[$module] ?? 'none';
                                        @endphp
                                        <tr class="{{ $isSub ? 'bg-light' : '' }}">
                                            <td class="{{ $isSub ? 'ps-4 text-muted' : '' }}">
                                                <i class="{{ $icon }} me-2 {{ $isSub ? 'text-secondary' : 'text-primary' }} fs-13"></i>
                                                {{ $label }}
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="read" class="form-check-input"
                                                    {{ $curLevel === 'read' ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="write" class="form-check-input"
                                                    {{ $curLevel === 'write' ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="none" class="form-check-input"
                                                    {{ $curLevel === 'none' ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3 border-top bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="feather-check me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @endif

    {{-- ─── Modal: Add New System User ─────────────────────────────────────── --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark, #0B192C);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-user-plus me-2"></i> Add New System User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">User Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g. Sushant Yadav" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="user@ssadvisory.co.nz" required maxlength="100">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Role Assigned</label>
                                <select class="form-select @error('role_id') is-invalid @enderror" name="role_id" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">FSPR Number</label>
                                <input type="text" class="form-control @error('fspr_number') is-invalid @enderror" name="fspr_number" value="{{ old('fspr_number') }}" placeholder="FSP-XXXXXX">
                                @error('fspr_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Create User Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─── Modal: Add New Role ─────────────────────────────────────────────── --}}
    @if(auth()->user()->isSuperAdmin())
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark, #0B192C);">
                    <h5 class="modal-title text-white mb-0">
                        <i class="feather-shield me-2"></i> Add New Access Role & Permissions
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf
                    <div class="modal-body px-4 py-3">
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13 text-dark" for="role-name-input">
                                    Role Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="role-name-input" name="name"
                                    class="form-control"
                                    placeholder="e.g. Underwriting Specialist" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold fs-13 text-dark" for="role-desc-input">
                                    Role Description
                                </label>
                                <textarea id="role-desc-input" name="description" class="form-control" rows="2" placeholder="Brief description of role capabilities"></textarea>
                            </div>
                        </div>

                        <h6 class="fw-bold text-dark fs-13 mb-2">
                            Module Permission Levels (All Sidebar Menus)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle fs-13 mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold text-uppercase fs-11 text-dark">Sidebar Navigation Menu</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:100px;">Read Only</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:100px;">Read / Write</th>
                                        <th class="text-center fw-bold text-uppercase fs-11 text-dark" style="width:90px;">None</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\Role::modules() as $module)
                                        @php 
                                            $info = $moduleLabels[$module] ?? ['feather-circle', $module, false];
                                            $icon = $info[0];
                                            $label = $info[1];
                                            $isSub = $info[2] ?? false;
                                        @endphp
                                        <tr class="{{ $isSub ? 'bg-light' : '' }}">
                                            <td class="{{ $isSub ? 'ps-4 text-muted' : '' }}">
                                                <i class="{{ $icon }} me-2 {{ $isSub ? 'text-secondary' : 'text-primary' }} fs-13"></i>
                                                {{ $label }}
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="read" class="form-check-input">
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="write" class="form-check-input">
                                            </td>
                                            <td class="text-center">
                                                <input type="radio" name="permissions[{{ $module }}]"
                                                    value="none" class="form-check-input" checked>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-3 border-top bg-light">
                        <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-save-role"
                            class="btn btn-primary btn-sm px-4 fw-bold">
                            <i class="feather-shield me-1"></i> Save Role & Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endif

    {{-- ─── Modals: Deactivate User (One per user) ───────────────────────── --}}
    @foreach($users as $user)
    @if(auth()->user()->isSuperAdmin() && $user->status === 'active' && $user->id !== auth()->id())
    <div class="modal fade" id="deactivateUserModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white bg-danger">
                    <h5 class="modal-title text-white mb-0 fs-14">
                        <i class="feather-alert-triangle me-2"></i> Deactivate User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="feather-alert-octagon text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Are you sure?</h5>
                    <p class="text-muted fs-13 mb-0">
                        Deactivating <strong>{{ $user->name }}</strong> will immediately terminate their session and prevent future logins.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('users.deactivate', $user) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Yes, Deactivate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach

    {{-- ─── Modals: Delete Role (One per role) ─────────────────────────────── --}}
    @if(auth()->user()->isSuperAdmin())
    @foreach($roles as $role)
    @if(!$role->is_super_admin)
    <div class="modal fade" id="deleteRoleModal-{{ $role->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white bg-danger">
                    <h5 class="modal-title text-white mb-0 fs-14">
                        <i class="feather-alert-triangle me-2"></i> Delete Role
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="feather-trash-2 text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Are you sure?</h5>
                    <p class="text-muted fs-13 mb-0">
                        Delete role <strong>{{ $role->name }}</strong>? This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('roles.destroy', $role) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach
    @endif

    {{-- ─── Modals: Delete User (One per user) ─────────────────────────────── --}}
    @foreach($users as $user)
    @if(auth()->user()->isSuperAdmin() && $user->id !== auth()->id() && !$user->isSuperAdmin())
    <div class="modal fade" id="deleteUserModal-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white bg-danger">
                    <h5 class="modal-title text-white mb-0 fs-14">
                        <i class="feather-alert-triangle me-2"></i> Delete User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="feather-trash-2 text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">Are you sure?</h5>
                    <p class="text-muted fs-13 mb-0">
                        Delete user <strong>{{ $user->name }}</strong>? This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('users.destroy', $user) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endforeach

    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/pages/access.js') }}?v={{ filemtime(public_path('assets/js/pages/access.js')) }}"></script>
        <script>
            document.addEventListener('show.bs.modal', function (event) {
                if (event.target.id !== 'dropdownValueModal') return;

                const button = event.relatedTarget;
                const form = document.getElementById('dropdownValueForm');
                const method = document.getElementById('dropdownValueMethod');
                const group = document.getElementById('dropdownGroupSelect');
                const name = document.getElementById('dropdownValueName');
                const status = document.getElementById('dropdownValueStatus');
                const submit = document.getElementById('dropdownValueSubmitBtn');
                const title = document.getElementById('dropdownValueModalTitle');
                const id = button?.getAttribute('data-id');

                if (!id) {
                    form.action = @json(route('settings.dropdowns.store'));
                    method.value = 'POST';
                    title.innerHTML = '<i class="feather-plus me-2"></i> Add Category';
                    const selOpt = document.getElementById('dropdownGroupFilter')?.selectedOptions[0];
                    group.value = selOpt?.dataset?.group || '';
                    name.value = '';
                    status.checked = true;
                    return;
                }

                form.action = @json(url('settings/dropdowns')) + '/' + id;
                method.value = 'PATCH';
                title.innerHTML = '<i class="feather-edit-2 me-2"></i> Edit Category';
                submit.textContent = 'Update';
                group.value = button.getAttribute('data-group');
                name.value = button.getAttribute('data-name');
                status.checked = button.getAttribute('data-status') === '1';
            });
        </script>
    @endpush

@endsection
