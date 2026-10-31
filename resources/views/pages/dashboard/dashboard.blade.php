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
                                <input type="text" id="filterClientSearch" class="form-control dash-filter-input" placeholder="Search by client name" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Mobile Number</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterNumberSearch" class="form-control dash-filter-input" placeholder="Search by mobile number" />
                            </div>
                        </div>
                        <div style="min-width: 170px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Address (Street Address, Pin code, City, Suburb)</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-search"></i>
                                <input type="text" id="filterAddressSearch" class="form-control dash-filter-input" placeholder="Search by address" />
                            </div>
                        </div>
                        <div style="min-width: 150px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Date of Birth Search</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-calendar"></i>
                                <input type="date" id="filterDobSearch" class="form-control dash-filter-input" />
                            </div>
                        </div>
                        <div style="min-width: 210px;" class="flex-fill">
                            <label class="fs-11 fw-bold text-muted mb-1 d-block">Date Range Filter</label>
                            <div class="dash-filter-input-group">
                                <i class="feather-calendar"></i>
                                <input type="text" id="filterDateRange" class="form-control dash-filter-input" placeholder="12 Aug 2026 – 18 Aug 2026" />
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <button id="btnExecuteSearch" class="btn btn-navy px-3 py-2 fw-bold"><i class="feather-search me-1"></i> Search</button>
                        <button id="btnClearSearch" class="btn btn-light px-3 py-2 fw-semibold">Clear</button>
                        <a href="javascript:void(0);" class="fs-12 text-muted fw-semibold text-nowrap ms-1 text-decoration-none" title="More Filters"><i class="feather-filter me-1"></i> More Filters</a>
                    </div>
                </div>
            </section>

            <!-- 2. TOP KPI STAT CARDS ROW (5 SOLID CIRCLE AVATAR CARDS) -->
            <section class="row g-3">
                <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-blue"><i class="feather-users"></i></div>
                        <div>
                            <div class="kpi-label">Total Clients</div>
                            <div class="kpi-val">1,245</div>
                            <div class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 8.5% <span class="text-muted fw-normal ms-1">vs last month</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-green"><i class="feather-shield"></i></div>
                        <div>
                            <div class="kpi-label">Inforce Clients</div>
                            <div class="kpi-val">892</div>
                            <div class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 6.7% <span class="text-muted fw-normal ms-1">vs last month</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-yellow"><i class="feather-user-x"></i></div>
                        <div>
                            <div class="kpi-label">Inactive Clients</div>
                            <div class="kpi-val">353</div>
                            <div class="kpi-badge text-danger"><i class="feather-arrow-down-right"></i> ↓ 2.1% <span class="text-muted fw-normal ms-1">vs last month</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-purple"><i class="feather-file-text"></i></div>
                        <div>
                            <div class="kpi-label">Active Policies</div>
                            <div class="kpi-val">2,134</div>
                            <div class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 7.4% <span class="text-muted fw-normal ms-1">vs last month</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-cyan"><i class="feather-dollar-sign"></i></div>
                        <div>
                            <div class="kpi-label">Total Annual Premium</div>
                            <div class="kpi-val">$4.28M</div>
                            <div class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 9.2% <span class="text-muted fw-normal ms-1">vs last month</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. MIDDLE CHARTS & ACTIVITY FEED ROW -->
            <section class="row g-4">
                <div class="col-xl-4 col-lg-6">
                    <div class="card-widget h-100">
                        <h6 class="widget-title mb-3">Client Status Overview</h6>
                        <div id="clientStatusChart" class="chart-container-wrapper">
                            <div class="text-center py-2 w-100">
                                <svg viewBox="0 0 100 100" style="width: 155px; height: 155px; transform: rotate(-90deg);">
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#F59E0B" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#10B981" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="67.5"></circle>
                                </svg>
                                <div class="mt-2 fs-13 fw-extrabold text-dark">Total Clients: 1,245</div>
                                <div class="d-flex justify-content-center gap-3 fs-11 mt-1 fw-bold">
                                    <span class="text-success"><i class="fa fa-square me-1"></i> Inforce: 892 (71.6%)</span>
                                    <span class="text-warning"><i class="fa fa-square me-1"></i> Inactive: 353 (28.4%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6">
                    <div class="card-widget h-100">
                        <h6 class="widget-title mb-3">Clients by Source</h6>
                        <div id="clientsBySourceChart" class="chart-container-wrapper">
                            <div class="text-center py-2 w-100">
                                <svg viewBox="0 0 100 100" style="width: 155px; height: 155px; transform: rotate(-90deg);">
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#2563EB" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="0"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#10B981" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="93"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#F59E0B" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="154"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#8B5CF6" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="194"></circle>
                                    <circle cx="50" cy="50" r="38" fill="transparent" stroke="#94A3B8" stroke-width="13" stroke-dasharray="238.76" stroke-dashoffset="221"></circle>
                                </svg>
                                <div class="d-flex flex-wrap justify-content-center gap-2 fs-11 mt-2 fw-bold">
                                    <span style="color: #2563EB;"><i class="fa fa-circle me-1"></i> Referral 485 (39.0%)</span>
                                    <span style="color: #10B981;"><i class="fa fa-circle me-1"></i> Existing 320 (25.7%)</span>
                                    <span style="color: #F59E0B;"><i class="fa fa-circle me-1"></i> Online 210 (16.9%)</span>
                                    <span style="color: #8B5CF6;"><i class="fa fa-circle me-1"></i> Partner 140 (11.2%)</span>
                                    <span style="color: #94A3B8;"><i class="fa fa-circle me-1"></i> Other 90 (7.2%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-12">
                    <div class="card-widget h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="widget-title mb-0">Activity Feed</h6>
                            <a href="tasks.html" class="fs-12 text-primary fw-bold text-decoration-none">View All</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text bg-soft-primary text-primary rounded-circle flex-shrink-0"><i class="feather-user-plus"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">New client added</div>
                                    <div class="text-muted fs-12">Rahul Sharma</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">18 Aug 2026, 10:45 AM</div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text bg-soft-success text-success rounded-circle flex-shrink-0"><i class="feather-shield-check"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">Policy issued</div>
                                    <div class="text-muted fs-12">AIA Health - $1,000,000</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">18 Aug 2026, 09:30 AM</div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text bg-soft-purple text-purple rounded-circle flex-shrink-0"><i class="feather-calendar"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">Follow up scheduled</div>
                                    <div class="text-muted fs-12">Priya Patel</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">17 Aug 2026, 04:15 PM</div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text bg-soft-warning text-warning rounded-circle flex-shrink-0"><i class="feather-file-text"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">Document uploaded</div>
                                    <div class="text-muted fs-12">Kishore Kumar</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">17 Aug 2026, 11:20 AM</div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-text bg-soft-info text-info rounded-circle flex-shrink-0"><i class="feather-edit"></i></div>
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark fs-13">Client updated</div>
                                    <div class="text-muted fs-12">Suman Pappula</div>
                                </div>
                                <div class="text-muted fs-11 flex-shrink-0">16 Aug 2026, 02:35 PM</div>
                            </div>
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
                                        <button class="nav-link active px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab" data-filter="all">All Clients</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab" data-filter="Inforce">Inforce Clients</button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link px-3 py-1 fs-13 fw-semibold" data-bs-toggle="tab" data-filter="Inactive">Inactive Clients</button>
                                    </li>
                                </ul>
                            </div>
                            <a href="clients.html" class="fs-12 text-primary fw-bold text-decoration-none">View All Clients</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle w-100" id="recentClientsTable">
                                <thead>
                                    <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                        <th>Client Name</th>
                                        <th>Phone Number</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th class="text-center">Policies</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr data-status="Inforce">
                                        <td class="fw-bold text-dark fs-13">Kishore Kumar</td>
                                        <td class="fs-13 text-muted">021 123 4567</td>
                                        <td><span class="status-pill-inforce">Inforce</span></td>
                                        <td class="fs-13 text-muted">Referral</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">3</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper">
                                                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="clients.html" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-status="Inforce">
                                        <td class="fw-bold text-dark fs-13">Rahul Sharma</td>
                                        <td class="fs-13 text-muted">021 555 6677</td>
                                        <td><span class="status-pill-inforce">Inforce</span></td>
                                        <td class="fs-13 text-muted">Broker / Partner</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">4</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper">
                                                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="clients.html" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-status="Inforce">
                                        <td class="fw-bold text-dark fs-13">Suman Pappula</td>
                                        <td class="fs-13 text-muted">021 987 6543</td>
                                        <td><span class="status-pill-inforce">Inforce</span></td>
                                        <td class="fs-13 text-muted">Existing Client</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">2</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper">
                                                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="clients.html" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                                                    
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-status="Inactive">
                                        <td class="fw-bold text-dark fs-13">Vandana Singh</td>
                                        <td class="fs-13 text-muted">022 345 6789</td>
                                        <td><span class="status-pill-inactive">Inactive</span></td>
                                        <td class="fs-13 text-muted">Online / Website</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">1</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper">
                                                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="clients.html" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                                                   
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr data-status="Inactive">
                                        <td class="fw-bold text-dark fs-13">Neha Gupta</td>
                                        <td class="fs-13 text-muted">027 654 3210</td>
                                        <td><span class="status-pill-inactive">Inactive</span></td>
                                        <td class="fs-13 text-muted">Referral</td>
                                        <td class="fs-13 fw-semibold text-dark text-center">1</td>
                                        <td class="text-center">
                                            <div class="action-kebab-wrapper">
                                                <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                                <div class="action-kebab-dropdown">
                                                    <a href="clients.html" class="action-kebab-item"><i class="feather-eye text-primary me-1"></i> View Client Profile</a>
                                                    <a href="policies.html" class="action-kebab-item"><i class="feather-file-text me-1"></i> View Policies</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-12">
                    <div class="card-widget h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="widget-title mb-0">Upcoming Follow Ups</h6>
                            <a href="calendar.html" class="fs-12 text-primary fw-bold text-decoration-none">View Calendar</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">Kishore Kumar</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">18 Aug 2026</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">Vandana Singh</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">19 Aug 2026</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">Ravi Mehta</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">20 Aug 2026</span>
                                    <i class="feather-mail text-muted fs-13"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">Neha Gupta</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">21 Aug 2026</span>
                                    <i class="feather-mail text-muted fs-13"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="feather-user text-muted fs-14"></i>
                                    <span class="fw-bold text-dark fs-13">Arjun Verma</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted fw-semibold">22 Aug 2026</span>
                                    <i class="feather-mail text-muted fs-13"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. BOTTOM METRIC CARDS ROW (STANDARD BOOTSTRAP GRID - NO OVERLAP) -->
            <section class="row g-3">
                <div class="col-xl-3 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-purple"><i class="feather-user-plus"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">New Clients (This Month)</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="kpi-val">42</span>
                                <span class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 12.3%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-green"><i class="feather-file-text"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Policies Issued (This Month)</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="kpi-val">68</span>
                                <span class="kpi-badge text-success"><i class="feather-arrow-up-right"></i> ↑ 15.6%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle bg-danger text-white"><i class="feather-shield-off"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Claims Lodged (This Month)</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="kpi-val">11</span>
                                <span class="kpi-badge text-danger"><i class="feather-arrow-down-right"></i> ↓ 8.3%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 col-sm-12">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-yellow"><i class="feather-check-square"></i></div>
                        <div class="flex-fill">
                            <div class="kpi-label">Pending Tasks</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="kpi-val">27</span>
                                <a href="tasks.html" class="fs-12 text-primary fw-bold text-decoration-none">View all</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        

</div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}?v={{ filemtime(public_path('assets/js/dashboard-redesign.js')) }}"></script>
@endpush

@endsection
