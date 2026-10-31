<!-- Navigation Sidebar (Consistent across all 15 pages) -->
    <nav class="nxl-navigation" id="mainSidebar">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="{{ url('/') }}" class="b-brand d-flex align-items-center gap-3 text-decoration-none"
                    title="SS Advisory Lead Engine Home">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="SS Advisory Logo" class="brand-logo-img" />
                    <div class="nxl-mtext d-flex flex-column">
                        <span class="fs-14 fw-bolder text-white tracking-wide"
                            style="letter-spacing: 0.05em; line-height: 1.1;">SS <span
                                style="color: var(--color-cyan);">ADVISORY</span></span>
                        <span class="fs-10 text-muted fw-semibold text-uppercase mt-1"
                            style="letter-spacing: 0.12em; color: #94A3B8 !important;">LEAD ENGINE</span>
                    </div>
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    {{-- 1. Dashboard Overview --}}
                    @if(auth()->user()?->hasPermission('dashboard'))
                    <li class="nxl-item {{ request()->is('/') || request()->is('dashboard*') || request()->is('analytics*') ? 'active' : '' }}">
                        <a href="{{ url('/') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-grid"></i></span>
                            <span class="nxl-mtext">Dashboard</span>
                        </a>
                    </li>
                    @endif

                    {{-- 2. Clients Directory --}}
                    @if(auth()->user()?->hasPermission('clients') || auth()->user()?->hasPermission('clients_login') || auth()->user()?->hasPermission('clients_inforce') || auth()->user()?->hasPermission('clients_inactive') || auth()->user()?->hasPermission('clients_claims') || auth()->user()?->hasPermission('clients_cancellation') || auth()->user()?->hasPermission('clients_npw_deferred'))
                    <li class="nxl-item nxl-custom-hasmenu clients-menu-expanded {{ request()->is('clients*') || request()->is('auth/clients-login') ? 'active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Clients</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if(auth()->user()?->hasPermission('clients_login'))
                            <li class="nxl-item {{ request()->is('auth/clients-login') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('auth/clients-login') }}">Login Client</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_inforce'))
                            <li class="nxl-item {{ request()->is('clients/inforce') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients/inforce') }}">Inforce Clients</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_claims'))
                            <li class="nxl-item {{ request()->is('clients/requests') ? 'active' : '' }}"><a class="nxl-link" href="{{ route('clients.requests') }}">Client Request</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_inactive'))
                            <li class="nxl-item {{ request()->is('clients/inactive') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients/inactive') }}">Inactive Clients</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_claims'))
                            <li class="nxl-item {{ request()->is('clients/claims') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients/claims') }}">Claim update</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_cancellation'))
                            <li class="nxl-item {{ request()->is('clients/cancellation') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients/cancellation') }}">Cancellation update</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients_npw_deferred'))
                            <li class="nxl-item {{ request()->is('clients/npw-deferred') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients/npw-deferred') }}">NPW Deferred</a></li>
                            @endif
                            @if(auth()->user()?->hasPermission('clients'))
                            <li class="nxl-item {{ request()->is('clients') ? 'active' : '' }}"><a class="nxl-link" href="{{ url('clients') }}">All Clients</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif


                    {{-- 4. Leads Pipeline --}}
                    @if(auth()->user()?->hasPermission('leads'))
                    <li class="nxl-item {{ request()->is('crm/pipeline*') || request()->is('crm/create*') ? 'active' : '' }}">
                        <a href="{{ url('crm/pipeline') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-target"></i></span>
                            <span class="nxl-mtext">Leads</span>
                        </a>
                    </li>
                    @endif

                    {{-- 5. Tasks & Follow-ups --}}
                    @if(auth()->user()?->hasPermission('tasks'))
                    <li class="nxl-item {{ request()->is('utilities/tasks*') ? 'active' : '' }}">
                        <a href="{{ url('utilities/tasks') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-check-square"></i></span>
                            <span class="nxl-mtext">Tasks & Follow ups</span>
                        </a>
                    </li>
                    @endif

                    {{-- 6. Calendar & Consultations --}}
                    @if(auth()->user()?->hasPermission('calendar'))
                    <li class="nxl-item {{ request()->is('utilities/calendar*') ? 'active' : '' }}">
                        <a href="{{ url('utilities/calendar') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-calendar"></i></span>
                            <span class="nxl-mtext">Calendar</span>
                        </a>
                    </li>
                    @endif

                    {{-- 7. Reports & Analytics --}}
                    @if(auth()->user()?->hasPermission('reports'))
                    <li class="nxl-item {{ request()->is('crm/reports*') ? 'active' : '' }}">
                        <a href="{{ url('crm/reports') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
                            <span class="nxl-mtext">Reports</span>
                        </a>
                    </li>
                    @endif

                    {{-- 8. Document Vault --}}
                    @if(auth()->user()?->hasPermission('documents'))
                    <li class="nxl-item {{ request()->is('utilities/documents*') ? 'active' : '' }}">
                        <a href="{{ url('utilities/documents') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-folder"></i></span>
                            <span class="nxl-mtext">Documents</span>
                        </a>
                    </li>
                    @endif



                    {{-- 11. Access Control --}}
                    @if(auth()->user()?->hasPermission('access'))
                    <li class="nxl-item {{ request()->is('settings/access*') || request()->is('settings/dropdowns*') ? 'active' : '' }}">
                        <a href="{{ url('settings/access') }}" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-lock"></i></span>
                            <span class="nxl-mtext">Access Control</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
