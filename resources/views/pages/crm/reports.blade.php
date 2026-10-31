@extends('layouts.app')
@section('title', 'Lead & Sales Reports')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Lead Conversion & Performance Reports</h4>
                    <p class="text-muted fs-13 mb-0">Export performance metrics by advisor, channel, and quote
                        conversion rates.</p>
                </div>
            </div>

            <!-- Report Filters Card -->
            <div class="dash-filter-card">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fs-12 fw-semibold text-muted mb-1">Date Range</label>
                        <div class="dash-filter-input-group">
                            <i class="feather-calendar"></i>
                            <input type="text" class="form-control dash-filter-input fs-13" id="reportDateRange"
                                placeholder="Select Date Range" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-12 fw-semibold text-muted mb-1">Lead Source</label>
                        <select class="form-select fs-13" id="reportSourceSelect">
                            <option value="">All Sources</option>
                            @foreach($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-12 fw-semibold text-muted mb-1">Advisor-wise Filter</label>
                        <select class="form-select fs-13" id="reportAdvisorSelect">
                            <option value="">All Advisors</option>
                            @foreach($advisors as $advisor)
                            <option value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end" style="height: 62px;">
                        <button class="btn btn-primary w-100 fw-bold" id="btnApplyReportFilter"><i
                                class="feather-filter me-1"></i> Apply Filters</button>
                    </div>
                </div>
            </div>

            <!-- New Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card-widget p-4 h-100">
                        <h6 class="widget-title mb-3">Client Status Distribution</h6>
                        <div id="reportStatusDonutChart" class="d-flex justify-content-center" style="min-height: 260px;"></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-widget p-4 h-100">
                        <h6 class="widget-title mb-3">Pipeline Snapshot</h6>
                        <div id="reportPipelineBarChart" style="min-height: 260px;"></div>
                    </div>
                </div>
            </div>

            <!-- Report Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card-widget p-4 h-100">
                        <h6 class="widget-title mb-3">Clients Generated Over Time</h6>
                        <div id="reportSourceBarChart" style="min-height: 260px;"></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-widget p-4 h-100">
                        <h6 class="widget-title mb-3">Advisor Conversion Funnel</h6>
                        <div id="reportFunnelAreaChart" style="min-height: 260px;"></div>
                    </div>
                </div>
            </div>

            <!-- Report DataTables -->
            <div class="card-widget">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="widget-title mb-0">Advisor-wise Performance Breakdown</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="repPerformanceTable">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Advisor Name</th>
                                <th>Inforce Clients</th>
                                <th>Conversion Rate</th>
                                <th>Annual Premium Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            

</div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/pages/reports-leads.js?v=' . filemtime(public_path('assets/js/pages/reports-leads.js'))) }}"></script>
@endpush

@endsection
