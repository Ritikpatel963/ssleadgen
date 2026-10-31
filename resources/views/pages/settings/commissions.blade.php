@extends('layouts.app')
@section('title', 'Commissions & Revenue')
@section('content')
<!-- Standardized Breadcrumb Header matching settings-sources.html -->
            <div class="page-header mb-0">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title">
                        <h5 class="m-b-0 fw-bold text-dark">Brokerage Revenue</h5>
                    </div>
                    <ul class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.html">Home</a></li>
                        <li class="breadcrumb-item active">Commissions</li>
                    </ul>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Brokerage Commission Statements</h4>
                    <p class="text-muted fs-13 mb-0">Initial upfront commissions and recurring renewal trail payments.</p>
                </div>
                <button class="btn btn-outline-primary btn-sm px-3 fw-bold" onclick="alert('Downloading Monthly Brokerage Statement...')"><i class="feather-download me-1"></i> Download Statement</button>
            </div>

            <!-- KPI Row -->
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-green"><i class="feather-dollar-sign"></i></div>
                        <div>
                            <div class="kpi-label">Upfront Commission (YTD)</div>
                            <div class="kpi-val">$328,450</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-purple"><i class="feather-refresh-cw"></i></div>
                        <div>
                            <div class="kpi-label">Renewal Trail (Monthly)</div>
                            <div class="kpi-val">$24,180</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-stat-card">
                        <div class="kpi-icon-circle kpi-icon-blue"><i class="feather-pie-chart"></i></div>
                        <div>
                            <div class="kpi-label">Average Commission Rate</div>
                            <div class="kpi-val">190% Initial</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="commissionsTable">
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Policy #</th>
                                <th>Client Name</th>
                                <th>Insurer</th>
                                <th>Type</th>
                                <th>Annual Premium</th>
                                <th>Commission Type</th>
                                <th>Payout Amount</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2627</td>
                                <td class="fs-13">Kishore Kumar</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$1,996</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$199</td>
                                <td class="fs-13 text-muted">09 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-4109</td>
                                <td class="fs-13">Suman Pappula</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$2,250</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$4,275</td>
                                <td class="fs-13 text-muted">20 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-1349</td>
                                <td class="fs-13">Rahul Sharma</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$3,495</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$6,640</td>
                                <td class="fs-13 text-muted">19 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-4362</td>
                                <td class="fs-13">Priya Patel</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$6,303</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$11,975</td>
                                <td class="fs-13 text-muted">08 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-6383</td>
                                <td class="fs-13">Sarah Connor</td>
                                <td class="fs-13">Fidelity Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$3,771</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$7,164</td>
                                <td class="fs-13 text-muted">05 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-9850</td>
                                <td class="fs-13">Amit Miller</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$2,400</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$4,560</td>
                                <td class="fs-13 text-muted">05 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-1243</td>
                                <td class="fs-13">David Chang</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$6,323</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$632</td>
                                <td class="fs-13 text-muted">01 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-3854</td>
                                <td class="fs-13">Michael Singh</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$2,538</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$253</td>
                                <td class="fs-13 text-muted">17 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2862</td>
                                <td class="fs-13">Aarav Cooper</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$5,172</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$517</td>
                                <td class="fs-13 text-muted">17 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2786</td>
                                <td class="fs-13">Vandana Taylor</td>
                                <td class="fs-13">Chubb Life</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$9,244</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$17,563</td>
                                <td class="fs-13 text-muted">17 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-5941</td>
                                <td class="fs-13">James Walker</td>
                                <td class="fs-13">Chubb Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$1,998</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$199</td>
                                <td class="fs-13 text-muted">13 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-7984</td>
                                <td class="fs-13">Olivia Patel</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$7,335</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$733</td>
                                <td class="fs-13 text-muted">03 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2323</td>
                                <td class="fs-13">Ethan Smith</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$2,038</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$3,872</td>
                                <td class="fs-13 text-muted">09 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-9984</td>
                                <td class="fs-13">Arjun Johnson</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$6,393</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$639</td>
                                <td class="fs-13 text-muted">15 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-9282</td>
                                <td class="fs-13">Neha Williams</td>
                                <td class="fs-13">Asteron Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$2,312</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$4,392</td>
                                <td class="fs-13 text-muted">18 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-4522</td>
                                <td class="fs-13">John Brown</td>
                                <td class="fs-13">Chubb Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$8,775</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$16,672</td>
                                <td class="fs-13 text-muted">14 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-6553</td>
                                <td class="fs-13">Emma Jones</td>
                                <td class="fs-13">Chubb Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$4,907</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$9,323</td>
                                <td class="fs-13 text-muted">11 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-7992</td>
                                <td class="fs-13">Robert Garcia</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$4,566</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$8,675</td>
                                <td class="fs-13 text-muted">16 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2099</td>
                                <td class="fs-13">Sophia Miller</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$2,263</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$226</td>
                                <td class="fs-13 text-muted">04 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-7105</td>
                                <td class="fs-13">William Davis</td>
                                <td class="fs-13">Fidelity Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$6,304</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$630</td>
                                <td class="fs-13 text-muted">04 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-7730</td>
                                <td class="fs-13">Isabella Rodriguez</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$8,611</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$16,360</td>
                                <td class="fs-13 text-muted">10 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-6119</td>
                                <td class="fs-13">Daniel Martinez</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$6,234</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$11,844</td>
                                <td class="fs-13 text-muted">05 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-8900</td>
                                <td class="fs-13">Mia Hernandez</td>
                                <td class="fs-13">Fidelity Life</td>
                                <td><span class="badge bg-soft-primary text-primary fs-11">Life & Cover</span></td>
                                <td class="fs-13 fw-semibold">$4,368</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$436</td>
                                <td class="fs-13 text-muted">04 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-5564</td>
                                <td class="fs-13">Joseph Lopez</td>
                                <td class="fs-13">Asteron Life</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$8,112</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$811</td>
                                <td class="fs-13 text-muted">18 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-1430</td>
                                <td class="fs-13">Charlotte Gonzalez</td>
                                <td class="fs-13">Asteron Life</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$1,737</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$3,300</td>
                                <td class="fs-13 text-muted">09 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-6062</td>
                                <td class="fs-13">Matthew Wilson</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$1,550</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$2,945</td>
                                <td class="fs-13 text-muted">05 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-7566</td>
                                <td class="fs-13">Amelia Anderson</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$7,570</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$14,383</td>
                                <td class="fs-13 text-muted">03 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-9691</td>
                                <td class="fs-13">David Thomas</td>
                                <td class="fs-13">Fidelity Life</td>
                                <td><span class="badge bg-soft-danger text-danger fs-11">Key Person Business</span></td>
                                <td class="fs-13 fw-semibold">$4,939</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$493</td>
                                <td class="fs-13 text-muted">11 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-3578</td>
                                <td class="fs-13">Harper Taylor</td>
                                <td class="fs-13">Partners Life</td>
                                <td><span class="badge bg-soft-warning text-warning fs-11">Income Protection</span></td>
                                <td class="fs-13 fw-semibold">$7,411</td>
                                <td><span class="badge bg-soft-purple text-purple fs-11">Renewal Trail</span></td>
                                <td class="fs-13 fw-bold text-success">$741</td>
                                <td class="fs-13 text-muted">19 Aug 2026</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark fs-13">POL-2026-2391</td>
                                <td class="fs-13">Andrew Moore</td>
                                <td class="fs-13">AIA Health</td>
                                <td><span class="badge bg-soft-info text-info fs-11">Trauma & Medical</span></td>
                                <td class="fs-13 fw-semibold">$2,789</td>
                                <td><span class="badge bg-soft-success text-success fs-11">Initial Upfront</span></td>
                                <td class="fs-13 fw-bold text-success">$5,299</td>
                                <td class="fs-13 text-muted">03 Aug 2026</td>
                            </tr>
</tbody>
                    </table>
                </div>
            



@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}"></script>
    <script src="{{ asset('assets/js/pages/commissions.js') }}?v={{ filemtime(public_path('assets/js/pages/commissions.js')) }}"></script>
@endpush

@endsection
