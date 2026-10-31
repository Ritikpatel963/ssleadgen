@forelse($clients as $client)
<tr data-source="{{ $client->leadSource->name ?? 'Direct' }}" data-status="{{ $client->status }}">
    <td class="fw-bold text-dark fs-13" data-order="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</td>
    <td class="fs-13 text-muted">{{ $client->phone ?? 'N/A' }}</td>
    <td class="fs-13 text-muted">{{ $client->dob ? \Carbon\Carbon::parse($client->dob)->format('d/m/Y') : 'N/A' }}</td>
    <td class="fs-13 text-muted">{{ $client->address ?? 'N/A' }}</td>
    <td>
        @if($client->status == 'Inforce')
            <span class="status-pill-inforce">Inforce</span>
        @elseif($client->status == 'Inactive')
            <span class="status-pill-inactive">Inactive</span>
        @elseif($client->status == 'Cancellation')
            <span class="status-pill-cancellation">Cancellation</span>
        @elseif($client->status == 'NPW Deferred')
            <span class="status-pill-npw">NPW Deferred</span>
        @elseif($client->status == 'Login Client')
            <span class="badge bg-primary fs-11 px-2 py-1">Login Client</span>
        @else
            <span class="badge bg-secondary fs-11 px-2 py-1">{{ $client->status ?: 'Unknown' }}</span>
        @endif
    </td>
    <td class="fs-13 text-muted">{{ $client->leadSource->name ?? 'Direct' }}</td>

    <td class="fs-13 text-muted">{{ $client->updated_at?->format('d M Y') ?? 'N/A' }}</td>
    <td class="text-center">
        <div class="action-kebab-wrapper"
            data-client-id="{{ $client->id }}"
            data-client-name="{{ $client->first_name }} {{ $client->last_name }}"
            data-company="{{ $client->company }}"
            data-policy-no="{{ $client->policy_no ?: ($client->policies->pluck('policy_number')->filter()->join(', ') ?: '-') }}"
            data-first-name="{{ $client->first_name }}"
            data-last-name="{{ $client->last_name }}"
            data-dob="{{ $client->dob?->format('d/m/Y') }}"
            data-phone="{{ $client->phone }}"
            data-email="{{ $client->email }}"
            data-address="{{ $client->address }}"
            data-suburb="{{ $client->suburb }}"
            data-city="{{ $client->city }}"
            data-post-code="{{ $client->post_code }}"
            data-login-date="{{ $client->login_date?->format('d/m/Y') }}"
            data-anp="{{ $client->anp }}"
            data-adviser="{{ $client->adviser }}"
            data-not-counting="{{ $client->not_counting ? 'Yes' : 'No' }}"
            data-compliance-by="{{ $client->compliance_by }}"
            data-roa-due-date="{{ $client->roa_due_date?->format('d/m/Y') }}"
            data-status-compliance="{{ $client->status_compliance }}"
            data-sent-to-client="{{ $client->sent_to_client }}"
            data-outcome="{{ $client->outcome }}"
            data-claim-type="{{ $client->claim_type }}"
            data-claim-admin="{{ $client->claim_admin ?: $client->adviser }}"
            data-canc-admin="{{ $client->canc_admin ?: $client->adviser }}"
            data-npw-admin="{{ $client->npw_admin ?: $client->adviser }}"
            data-adviser="{{ $client->adviser }}"
            data-claim-processed-date="{{ $client->claim_processed_date?->format('Y-m-d') }}"
            data-claim-update-status="{{ $client->claim_update_status }}"
            data-claim-approved-date="{{ $client->claim_approved_date?->format('Y-m-d') }}"
            data-claim-result="{{ $client->claim_result }}">
            <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
            <div class="action-kebab-dropdown">
                <a href="javascript:void(0);" class="action-kebab-item" onclick="viewClientDetailsFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-eye text-primary me-1"></i> View Profile</a>
                @if(auth()->user()->canWrite('clients'))
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openClientRequestModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', {{ $client->id }})"><i class="feather-git-pull-request text-warning me-1"></i> Inforce Clients</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openClaimUpdateModalFromRow(this.closest('.action-kebab-wrapper'))"><i class="feather-shield text-info me-1"></i> Claim Update</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openInactiveClientModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', '{{ $client->id }}')"><i class="feather-user-x text-secondary me-1"></i> Inactive Clients</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openNpwDeferredModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', '{{ $client->id }}', '{{ addslashes($client->npw_admin ?: ($client->adviser ?? '')) }}')"><i class="feather-clock text-purple me-1"></i> NPW Deferred</a>
                    <a href="javascript:void(0);" class="action-kebab-item" onclick="openCancellationUpdateModal('{{ addslashes($client->first_name . ' ' . $client->last_name) }}', '{{ addslashes($client->company ?? '') }}', '{{ $client->id }}', '{{ addslashes($client->canc_admin ?: ($client->adviser ?? '')) }}')"><i class="feather-file-minus text-danger me-1"></i> Cancellation update</a>
                @endif
            </div>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="9" class="text-center py-4 text-muted" data-order="0">No clients found.</td>
</tr>
@endforelse
