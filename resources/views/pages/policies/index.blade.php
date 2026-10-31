@extends('layouts.app')
@section('title', 'Policy Management')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Active Policy Portfolio (2,134 Policies)</h4>
                    <p class="text-muted fs-13 mb-0">Track policy terms, insured amounts, renewal dates, and underwriter details.</p>
                </div>
                @if(auth()->user()->canWrite('policies'))
                    <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#newPolicyModal"><i class="feather-plus me-1"></i> Issue New Policy</button>
                @endif
            </div>

            <!-- Policy Table -->
            <div class="card-widget">
                <div class="table-responsive">
                    <table class="table table-hover align-middle w-100" id="policyDirectoryTable" data-server-paginated>
                        <thead>
                            <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                                <th>Policy #</th>
                                <th>Policy Holder</th>
                                <th>Insurer</th>
                                <th>Cover Type</th>
                                <th>Sum Assured</th>
                                <th>Annual Premium</th>
                                <th>Renewal Date</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($policies as $policy)
                            <tr data-insurer="{{ $policy->insurer->name ?? 'Unknown' }}" data-type="{{ $policy->cover_type }}">
                                <td class="fw-bold text-dark fs-13">{{ $policy->policy_number }}</td>
                                <td class="fs-13">{{ $policy->client->first_name ?? '' }} {{ $policy->client->last_name ?? '' }}</td>
                                <td class="fs-13">{{ $policy->insurer->name ?? 'Unknown' }}</td>
                                <td class="fs-13"><span class="badge bg-soft-primary text-primary">{{ $policy->cover_type ?? 'N/A' }}</span></td>
                                <td class="fs-13 fw-semibold">${{ number_format($policy->sum_assured, 0) }}</td>
                                <td class="fs-13 fw-semibold text-success">${{ number_format($policy->annual_premium, 0) }}</td>
                                <td class="fs-13 text-muted">{{ $policy->renewal_date ? \Carbon\Carbon::parse($policy->renewal_date)->format('d M Y') : 'N/A' }}</td>
                                <td>
                                    @if($policy->status == 'Active')
                                        <span class="status-pill-inforce">Active</span>
                                    @elseif($policy->status == 'Inactive')
                                        <span class="status-pill-inactive">Inactive</span>
                                    @else
                                        <span class="status-pill-cancellation">Cancelled</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(auth()->user()->canWrite('policies'))
                                        <button class="btn btn-sm btn-light py-1 px-2">Manage</button>
                                    @else
                                        <span class="text-muted fs-12">View Only</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No policies found.</td>
                            </tr>
                            @endforelse
</tbody>
                    </table>
                    {{ $policies->links('pagination::bootstrap-5') }}
                </div>
            

</div>
    </div>

    <!-- Modal: Issue New Policy -->
    <div class="modal fade" id="newPolicyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-file-text me-2"></i> Issue New Policy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="newPolicyForm" action="{{ route('policies.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Policy Holder Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="policyHolderInput" name="client_id" required>
                                    <option value="" selected disabled>Select a client...</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</option>
                                    @endforeach
                                </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Policy Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="policyNumberInput" name="policy_number" placeholder="POL-2026-XXXX" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Underwriter Insurer</label>
                                <select class="form-select" id="policyInsurerInput" name="insurer_id">
                                    <option value="AIA Health">AIA Health</option>
                                    <option value="AIA Living">AIA Living</option>
                                    <option value="Partners Life">Partners Life</option>
                                    <option value="Chubb Life">Chubb Life</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Sum Assured ($)</label>
                                <input type="number" class="form-control" id="policySumInput" name="sum_assured" placeholder="e.g. 500000">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Annual Premium ($)</label>
                                <input type="number" class="form-control" id="policyPremiumInput" name="annual_premium" placeholder="e.g. 2400">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Issue Policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/dashboard-redesign.js') }}"></script>
    <script src="{{ asset('assets/js/pages/policies.js') }}?v={{ filemtime(public_path('assets/js/pages/policies.js')) }}"></script>
@endpush

@endsection
