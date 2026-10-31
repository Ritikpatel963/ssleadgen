@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
            <!-- 1. TOP FILTER SEARCH BAR (RESPONSIVE FLEX - NO TRUNCATION) -->
            <section class="dash-filter-card">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap flex-fill">
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Client Search (First, Last Name)</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterClientSearch" class="form-control dash-filter-input"
                                    placeholder="Search by client name" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Mobile Number</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterNumberSearch" class="form-control dash-filter-input"
                                    placeholder="Search by mobile number" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Address (Street Address, Pin code, City, Suburb)</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterAddressSearch" class="form-control dash-filter-input"
                                    placeholder="Search by address" />
                            </div>
                        </div>
                        <div style="min-width: 150px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Date of Birth Search</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-calendar"></i>
                                <input type="date" id="filterDobSearch" class="form-control dash-filter-input" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <button id="btnExecuteSearch" class="btn btn-navy px-3 py-2 fw-bold"><i
                                class="feather-search me-1"></i> Search</button>
                        <button id="btnClearSearch" class="btn btn-light px-3 py-2 fw-semibold">Clear</button>
                    </div>
                </div>
            </section>

            <!-- 2. DASHBOARD SUMMARY CARDS -->
            <section class="row g-3">
                <div class="col-xl-2-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card h-100">
                        <div class="kpi-icon-circle kpi-icon-blue"><i class="feather-users"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Total Clients</div>
                            <div class="kpi-val">{{ number_format($totalClients) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card h-100">
                        <div class="kpi-icon-circle kpi-icon-green"><i class="feather-shield"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Inforce Clients</div>
                            <div class="kpi-val">{{ number_format($inforceClients) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card h-100">
                        <div class="kpi-icon-circle kpi-icon-yellow"><i class="feather-user-x"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Inactive Clients</div>
                            <div class="kpi-val">{{ number_format($inactiveClients) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card h-100">
                        <div class="kpi-icon-circle kpi-icon-purple"><i class="feather-file-text"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Claim Updates</div>
                            <div class="kpi-val">{{ number_format($claimUpdates) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card h-100">
                        <div class="kpi-icon-circle kpi-icon-cyan"><i class="feather-dollar-sign"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Cancellation Updates</div>
                            <div class="kpi-val">{{ number_format($cancellationUpdates) }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. MIDDLE CHARTS & ACTIVITY FEED ROW -->
            <section class="row g-4">
                <div class="col-xl-4 col-lg-6">
                    <div class="card-widget h-100">
                        <h6 class="widget-title mb-3">Client Status Overview</h6>
                        <div id="clientStatusChart" class="chart-container-wrapper">
                            <div class="text-center py-2 w-100">
                                <svg viewBox="0 0 100 100"
                                    style="width: 155px; height: 155px; transform: rotate(-90deg);">
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#F59E0B" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#10B981" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="67.5"></circle>
                                </svg>
                                <div class="mt-2 fs-13 fw-extrabold text-dark">Total Clients: {{ number_format($totalClients) }}</div>
                                <div class="d-flex justify-content-center gap-3 fs-11 mt-1 fw-bold">
                                    <span class="text-success"><i class="fa fa-square me-1"></i> Inforce: {{ number_format($inforceClients) }}
                                        ({{ $totalClients > 0 ? number_format(($inforceClients / $totalClients) * 100, 1) : '0.0' }}%)</span>
                                    <span class="text-warning"><i class="fa fa-square me-1"></i> Inactive: {{ number_format($inactiveClients) }}
                                        ({{ $totalClients > 0 ? number_format(($inactiveClients / $totalClients) * 100, 1) : '0.0' }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6">
                    <div class="card-widget h-100">
                        <h6 class="widget-title mb-3">Clients &amp; Leads by Source</h6>
                        <div id="clientsBySourceChart" class="chart-container-wrapper">
                            <div class="text-center py-2 w-100">
                                <svg viewBox="0 0 100 100"
                                    style="width: 155px; height: 155px; transform: rotate(-90deg);">
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#2563EB" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#10B981" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="93"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#F59E0B" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="154"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#8B5CF6" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="194"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#94A3B8" stroke-width="13"
                                        stroke-dasharray="238.76" stroke-dashoffset="221"></circle>
                                </svg>
                                <div class="d-flex flex-wrap justify-content-center gap-2 fs-11 mt-2 fw-bold">
                                    <span style="color: #2563EB;"><i class="fa fa-circle me-1"></i> Referral 485
                                        (39.0%)</span>
                                    <span style="color: #10B981;"><i class="fa fa-circle me-1"></i> Existing 320
                                        (25.7%)</span>
                                    <span style="color: #F59E0B;"><i class="fa fa-circle me-1"></i> Online 210
                                        (16.9%)</span>
                                    <span style="color: #8B5CF6;"><i class="fa fa-circle me-1"></i> Partner 140
                                        (11.2%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-12">
                    <div class="card-widget h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="widget-title mb-0">Activity Feed</h6>
                            <a href="{{ route('tasks.index') }}" class="fs-12 text-primary fw-bold text-decoration-none">View All</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse($activities as $activity)
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text {{ $activity['bg'] }} {{ $activity['text'] }} rounded-circle flex-shrink-0"><i class="{{ $activity['icon'] }}"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">{{ $activity['title'] }}</div>
                                    <div class="text-muted fs-12">{{ $activity['name'] }}</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">{{ $activity['date'] }}</div>
                            </div>
                            @empty
                            <div class="text-muted fs-12">No recent activity</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. RECENT CLIENTS TABLE & UPCOMING FOLLOW UPS SIDE WIDGET -->
            <section class="row g-4">
                <div class="col-xl-8 col-lg-12">
                    <div class="card-widget">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="widget-title mb-2">Recent Clients</h6>
                                <ul class="nav nav-tabs border-bottom-0" id="recentClientsTabs">
                                    <li class="nav-item">
                                        <button class="nav-link active px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab"
                                            data-filter="all">All Clients</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab"
                                            data-filter="Inforce">Inforce Clients</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab"
                                            data-filter="Inactive">Inactive Clients</button>
                                    </li>
                                </ul>
                            </div>
                            <a href="{{ route('clients.index') }}" class="fs-12 text-primary fw-bold text-decoration-none">View All
                                Clients</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle w-100" id="recentClientsTable">
                                <thead>
                                    <tr class="fs-12 text-muted text-uppercase fw-semibold"
                                        style="background: #F8FAFC;">
                                        <th>Client Name</th>
                                        <th>Phone Number</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th class="text-center">Policies</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentClientsTable as $client)
                                    <tr data-status="{{ $client->status }}"
                                        data-email="{{ $client->email ?? '-' }}"
                                        data-address="{{ implode(', ', array_filter([$client->address, $client->post_code, $client->city, $client->suburb])) }}">
                                        <td class="fw-bold text-dark fs-13">
                                            {{ $client->first_name }} {{ $client->last_name }}
                                            <span class="d-none">{{ $client->email }} {{ $client->address }} {{ $client->post_code }} {{ $client->city }} {{ $client->suburb }} {{ $client->dob?->format('d/m/Y') }}</span>
                                        </td>
                                        <td class="fs-13 text-muted">{{ $client->phone ?? '-' }}</td>
                                        <td>
                                            @if($client->status == 'Inforce')
                                            <span class="status-pill-inforce">Inforce</span>
                                            @elseif($client->status == 'Inactive')
                                            <span class="status-pill-inactive">Inactive</span>
                                            @else
                                            <span class="badge bg-light text-dark">{{ $client->status }}</span>
                                            @endif
                                        </td>
                                        <td class="fs-13 text-muted">{{ $client->leadSource ? $client->leadSource->name : 'Other' }}</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">{{ $client->policies ? $client->policies->count() : 0 }}</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper" data-client-id="{{ $client->id }}">
                                                <button class="action-kebab-btn"><i
                                                        class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="javascript:void(0);" class="action-kebab-item" onclick="viewSharedClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i
                                                            class="feather-eye text-primary me-1"></i> View Client
                                                        Profile</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="text-center fs-13 text-muted py-3">No recent clients found</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-12">
                    <div class="card-widget h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="widget-title mb-0">Upcoming Follow Ups</h6>
                            <a href="{{ route('calendar.index') }}" class="fs-12 text-primary fw-bold text-decoration-none">View
                                Calendar</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            @forelse($upcomingFollowUps as $followup)
                            <div
                                class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">{{ $followup->client_name }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">{{ $followup->appointment_date->format('d M Y') }}</span>
                                </div>
                            </div>
                            @empty
                            <div class="p-2 text-muted fs-13 text-center py-3">No upcoming follow ups</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>



</div>
    </div>

@include('pages.auth.partials.client-details-modal')

@push('scripts')
    <script>
        window.dashboardData = @json($dashboardData);
    </script>
@endpush

@endsection
