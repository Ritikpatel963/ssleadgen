<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientServiceRequest;
use App\Models\Insurer;
use App\Models\User;
use App\Notifications\NewLeadAssigned;
use App\Notifications\StageChangeNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ClientController extends Controller
{
    public function clientRequests()
    {
        $requests = collect([]);
        $advisers = User::whereHas('role', function ($query) {
            $query->where('slug', 'advisor')
                ->orWhere('slug', 'insurance-advisor')
                ->orWhere('name', 'like', '%advisor%')
                ->orWhere('name', 'like', '%adviser%');
        })->orderBy('name')->pluck('name');

        return view('pages.clients.requests', compact('requests', 'advisers'));
    }

    public function clientRequestsData(Request $request): JsonResponse
    {
        $query = ClientServiceRequest::with('client');

        $clientSearch = trim((string) $request->input('client_search', ''));
        if ($clientSearch !== '') {
            $query->where(function ($requests) use ($clientSearch) {
                $requests->where('client_name', 'like', "%{$clientSearch}%")
                    ->orWhereHas('client', fn (Builder $client) => $this->applyClientNameSearch($client, $clientSearch));
            });
        }

        $phoneSearch = trim((string) $request->input('phone_search', ''));
        if ($phoneSearch !== '') {
            $query->whereHas('client', fn (Builder $client) => $client->where('phone', 'like', "%{$phoneSearch}%"));
        }

        $addressSearch = trim((string) $request->input('address_search', ''));
        if ($addressSearch !== '') {
            $query->whereHas('client', fn (Builder $client) => $this->applyClientAddressSearch($client, $addressSearch));
        }

        $dobSearch = trim((string) $request->input('dob_search', ''));
        if ($dobSearch !== '') {
            $query->whereHas('client', fn (Builder $client) => $client->whereDate('dob', $dobSearch));
        }

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('client_name', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('request_type', 'like', "%{$searchValue}%")
                    ->orWhere('process_status', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereHas('client', function (Builder $client) use ($searchValue) {
                        $client->where('phone', 'like', "%{$searchValue}%")
                            ->orWhere('first_name', 'like', "%{$searchValue}%")
                            ->orWhere('last_name', 'like', "%{$searchValue}%")
                            ->orWhere('address', 'like', "%{$searchValue}%")
                            ->orWhere('suburb', 'like', "%{$searchValue}%")
                            ->orWhere('city', 'like', "%{$searchValue}%")
                            ->orWhere('post_code', 'like', "%{$searchValue}%");
                    });
            });
        }

        $totalRecords = ClientServiceRequest::count();
        $filteredRecords = $query->count();

        $columns = [
            'request_date',
            'client_name',
            'company',
            'request_type',
            'process_status',
            'status',
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest('request_date');
        }

        $requests = $query->skip($start)->take($length)->get();

        $data = $requests->map(function ($serviceRequest) {
            $client = $serviceRequest->client;
            $fullAddress = collect([$client?->address, $client?->suburb, $client?->city, $client?->post_code])
                ->filter()
                ->join(' ');

            return [
                'id' => $serviceRequest->id,
                'client_id' => $serviceRequest->client_id,
                'request_date' => $serviceRequest->request_date?->format('d/m/Y') ?? '-',
                'request_date_raw' => $serviceRequest->request_date?->format('Y-m-d') ?? '',
                'client_name' => $serviceRequest->client_name ?? '-',
                'company' => $serviceRequest->company ?: '-',
                'request_type' => $serviceRequest->request_type ?? '-',
                'process_status' => $serviceRequest->process_status ?: '-',
                'process_by' => $serviceRequest->process_by ?? '',
                'outcome' => $serviceRequest->outcome ?? '',
                'comments' => $serviceRequest->comments ?? '',
                'finished_date' => $serviceRequest->finished_date?->format('d/m/Y') ?? '-',
                'finished_date_raw' => $serviceRequest->finished_date?->format('Y-m-d') ?? '',
                'status' => $serviceRequest->status ?? 'Pending',
                'phone' => $client?->phone ?? '-',
                'address' => $fullAddress ?: '-',
                'dob' => $client?->dob?->format('d/m/Y') ?? '-',
                'dob_raw' => $client?->dob?->format('Y-m-d') ?? '',
                'client_status' => $client?->status ?? '',
                'update_url' => route('clients.requests.update', $serviceRequest),
                'resolve_url' => route('clients.requests.resolve', $serviceRequest),
                'move_url' => route('clients.requests.move-to-inforce', $serviceRequest),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function storeLoginClientRequest(Request $request, Client $client)
    {
        $data = $request->validate($this->clientServiceRequestRules());
        $serviceRequest = ClientServiceRequest::create([
            ...$this->mapClientServiceRequestData($data),
            'client_id' => $client->id,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client request saved as Pending.',
            'request_id' => $serviceRequest->id,
        ]);
    }

    public function updateClientRequest(Request $request, ClientServiceRequest $serviceRequest)
    {
        $serviceRequest->update($this->mapClientServiceRequestData($request->validate($this->clientServiceRequestRules())));

        return response()->json(['success' => true, 'message' => 'Client request updated.']);
    }

    public function resolveClientRequest(ClientServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['status' => 'Resolved']);

        return response()->json(['success' => true, 'message' => 'Client request marked Resolved.']);
    }

    public function moveClientRequestToInforce(Request $request, ClientServiceRequest $serviceRequest)
    {
        $requestData = $this->mapClientServiceRequestData($request->validate($this->clientServiceRequestRules()));

        [$client, $oldStage] = DB::transaction(function () use ($serviceRequest, $requestData) {
            $serviceRequest->update($requestData);
            $serviceRequest->refresh();
            $client = $serviceRequest->client()->lockForUpdate()->firstOrFail();
            abort_unless($client->status === 'Login Client', 409, 'This client is no longer a Login Client.');

            $oldStage = $client->status;
            $clientName = preg_split('/\s+/', trim($serviceRequest->client_name), 2);
            $client->update([
                'status' => 'Inforce',
                'first_name' => $clientName[0],
                'last_name' => $clientName[1] ?? null,
                'company' => $serviceRequest->company ?: $client->company,
                'inforce_date' => $serviceRequest->request_date,
                'inforce_request_type' => $serviceRequest->request_type,
                'inforce_process_status' => $serviceRequest->process_status,
                'inforce_process_by' => $serviceRequest->process_by,
                'inforce_outcome' => $serviceRequest->outcome,
                'inforce_comments' => $serviceRequest->comments,
                'inforce_finished_date' => $serviceRequest->finished_date,
            ]);

            return [$client, $oldStage];
        });

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        return response()->json(['success' => true, 'message' => 'Client moved to Inforce.']);
    }

    private function clientServiceRequestRules(): array
    {
        return [
            'inforce_date' => 'required|date',
            'client_name' => 'required|string|max:200',
            'company' => 'nullable|string|max:100',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ];
    }

    private function mapClientServiceRequestData(array $data): array
    {
        return [
            'request_date' => $data['inforce_date'],
            'client_name' => $data['client_name'],
            'company' => $data['company'] ?? null,
            'request_type' => $data['inforce_request_type'],
            'process_status' => $data['inforce_process_status'] ?? null,
            'process_by' => $data['inforce_process_by'] ?? null,
            'outcome' => $data['inforce_outcome'] ?? null,
            'comments' => $data['inforce_comments'] ?? null,
            'finished_date' => $data['inforce_finished_date'] ?? null,
        ];
    }

    public function index(Request $request, $status = null)
    {
        $query = Client::with(['leadSource', 'policies']);

        if ($status) {
            $statusMap = [
                'inforce' => 'Inforce',
                'inactive' => 'Inactive',
                'cancellation' => 'Cancellation',
                'npw-deferred' => 'NPW Deferred',
                'claims' => 'Claims',
            ];

            if (array_key_exists($status, $statusMap)) {
                $query->where('status', $statusMap[$status]);
            }
        }

        $this->applyClientSearchFilters($query, $request);

        // Return empty collection for AJAX-loaded tables
        $clients = collect([]);

        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->where('status', 'Active')->orderBy('name')->pluck('name');
        
            $advisers = User::whereHas('role', function ($query) {
                $query->where('slug', 'advisor')
                    ->orWhere('slug', 'insurance-advisor')
                    ->orWhere('name', 'like', '%advisor%')
                    ->orWhere('name', 'like', '%adviser%');
            })->orderBy('name')->pluck('name');
        

        // Get insurance companies for modals
        $insuranceCompanies = \App\Models\InsuranceCompany::orderBy('name')->get();
        $leadSources = \App\Models\LeadSource::where('is_active', true)->orderBy('name')->get();

        $viewName = $status ? "pages.clients.{$status}" : 'pages.clients.index';

        if (view()->exists($viewName)) {
           return view($viewName, compact('clients', 'status', 'admins', 'advisers', 'insuranceCompanies', 'leadSources'));
        }

        return view('pages.clients.index', compact('clients', 'status', 'admins', 'advisers', 'insuranceCompanies', 'leadSources'));
    }

    private function applyClientSearchFilters(Builder $query, Request $request): void
    {
        $clientSearch = trim((string) ($request->input('client_search') ?? $request->query('client_search', '')));
        if ($clientSearch !== '') {
            $this->applyClientNameSearch($query, $clientSearch);
        }

        $phoneSearch = trim((string) ($request->input('phone_search') ?? $request->query('phone_search', '')));
        if ($phoneSearch !== '') {
            $query->where('phone', 'like', "%{$phoneSearch}%");
        }

        $addressSearch = trim((string) ($request->input('address_search') ?? $request->query('address_search', '')));
        if ($addressSearch !== '') {
            $this->applyClientAddressSearch($query, $addressSearch);
        }

        $dobSearch = trim((string) ($request->input('dob_search') ?? $request->query('dob_search', '')));
        if ($dobSearch !== '') {
            try {
                $parsedDob = \Carbon\Carbon::parse($dobSearch)->format('Y-m-d');
                $query->whereDate('dob', $parsedDob);
            } catch (\Exception $e) {
                $query->whereDate('dob', $dobSearch);
            }
        }
    }

    private function applyClientNameSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $clients) use ($search) {
            $clients->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"]);
        });
    }

    private function applyClientAddressSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $clients) use ($search) {
            $clients->where('address', 'like', "%{$search}%")
                ->orWhere('suburb', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('post_code', 'like', "%{$search}%");
        });
    }
    
    private function deduplicatedClientQuery(?string $status = null): Builder
    {
        $base = Client::query();
        if ($status === 'login') {
            $base->loginClients();
        } elseif ($status !== null) {
            $base->where('status', $status);
        }

        $subquery = (clone $base)->selectRaw('MAX(id)')
            ->groupByRaw("CONCAT_WS('__', LOWER(TRIM(COALESCE(first_name, ''))), LOWER(TRIM(COALESCE(last_name, ''))), COALESCE(NULLIF(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), ''), NULLIF(DATE_FORMAT(dob, '%Y-%m-%d'), ''), NULLIF(TRIM(email), ''), id))");

        return Client::whereIn('clients.id', $subquery)->with(['leadSource', 'policies']);
    }


    public function profile(Client $client): JsonResponse
    {
        $client->load(['policies', 'leadSource', 'user']);
        $data = $client->toArray();
        $data['lead_source_name'] = $client->leadSource?->name;
        $data['user_name'] = $client->user?->name;

        // If the current record is missing key profile information, fallback to other records for the same person
        $fallbackClient = Client::where('first_name', $client->first_name)
            ->where('last_name', $client->last_name)
            ->where('id', '!=', $client->id)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        foreach ($fallbackClient as $fallback) {
            foreach (['phone', 'email', 'address', 'suburb', 'city', 'post_code', 'dob', 'login_date', 'anp'] as $field) {
                if (empty($data[$field]) && !empty($fallback->$field)) {
                    $data[$field] = $fallback->$field;
                    // Ensure the model property is updated for the formatting checks below
                    $client->$field = $fallback->$field;
                }
            }
        }

        $data['policy_no'] = $client->policy_no ?: ($client->policies->pluck('policy_number')->filter()->join(', ') ?: '-');
        $data['policy_numbers'] = $client->policies->pluck('policy_number')->filter()->values();
        
        // Format dates consistently for frontend
        $data['dob_raw'] = $client->dob?->format('Y-m-d') ?: ($data['dob'] ? (is_string($data['dob']) ? \Carbon\Carbon::parse($data['dob'])->format('Y-m-d') : null) : null);
        $data['login_date_raw'] = $client->login_date?->format('Y-m-d') ?: ($data['login_date'] ? (is_string($data['login_date']) ? \Carbon\Carbon::parse($data['login_date'])->format('Y-m-d') : null) : null);
        $data['roa_due_date_raw'] = $client->roa_due_date?->format('Y-m-d') ?: ($data['roa_due_date'] ? (is_string($data['roa_due_date']) ? \Carbon\Carbon::parse($data['roa_due_date'])->format('Y-m-d') : null) : null);
        $data['dob'] = $client->dob?->format('d/m/Y') ?: (is_string($data['dob']) ? \Carbon\Carbon::parse($data['dob'])->format('d/m/Y') : null);
        $data['login_date'] = $client->login_date?->format('d/m/Y') ?: (is_string($data['login_date']) ? \Carbon\Carbon::parse($data['login_date'])->format('d/m/Y') : null);
        $data['roa_due_date'] = $client->roa_due_date?->format('d/m/Y');
        $data['not_counting'] = $client->not_counting ? 'Yes' : 'No';

        // Additional date formatting if needed
        $data['inforce_date'] = $client->inforce_date?->format('Y-m-d');
        $data['inforce_finished_date'] = $client->inforce_finished_date?->format('Y-m-d');
        $data['claim_processed_date'] = $client->claim_processed_date?->format('Y-m-d');
        $data['claim_approved_date'] = $client->claim_approved_date?->format('Y-m-d');
        $data['canc_date_sent'] = $client->canc_date_sent?->format('Y-m-d');
        $data['canc_completed_date'] = $client->canc_completed_date?->format('Y-m-d');
        $data['npw_issue_date'] = $client->npw_issue_date?->format('Y-m-d');

        return response()->json($data);
    }

    public function loginClients()
    {
        $loginClients = collect([]);
        
            $advisers = User::whereHas('role', function ($query) {
                $query->where('slug', 'advisor')
                    ->orWhere('slug', 'insurance-advisor')
                    ->orWhere('name', 'like', '%advisor%')
                    ->orWhere('name', 'like', '%adviser%');
            })->orderBy('name')->pluck('name');
       
        $admins = User::whereHas('role', function ($q) {
            $q->where('name', 'admin');
        })->where('status', 'Active')->orderBy('name')->pluck('name');

        return view('pages.auth.clients-login', compact('loginClients', 'advisers', 'admins'));
    }

    public function loginClientsData(Request $request): JsonResponse
    {
        try {
             $query = $this->deduplicatedClientQuery('login');
            $totalRecords = (clone $query)->count();

            // Apply search filters
            $this->applyClientSearchFilters($query, $request);

            // DataTables server-side parameters
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $searchValue = $request->input('search.value', '');
            $orderColumnIndex = (int) $request->input('order.0.column', 0);
            $orderDirection = $request->input('order.0.dir', 'desc');

            // Apply DataTables search
            if ($searchValue) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('policy_no', 'like', "%{$searchValue}%")
                        ->orWhere('company', 'like', "%{$searchValue}%")
                        ->orWhere('first_name', 'like', "%{$searchValue}%")
                        ->orWhere('last_name', 'like', "%{$searchValue}%")
                        ->orWhere('phone', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
                });
            }

            // Filtered records count
            $filteredRecords = $query->count();

            // Apply ordering
            $columns = ['policy_no', 'company', 'first_name', 'phone', 'login_date', 'anp', 'status_compliance', 'sent_to_client'];
            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDirection);
            } else {
                $query->latest();
            }

            // Get paginated results
            $clients = $query->skip($start)->take($length)->get();

            $formatDate = function ($date, string $format = 'd/m/Y') {
                if (!$date) return '-';
                if ($date instanceof \DateTimeInterface) return $date->format($format);
                try {
                    return \Carbon\Carbon::parse($date)->format($format);
                } catch (\Throwable $e) {
                    return '-';
                }
            };

            $formatRawDate = function ($date) {
                if (!$date) return '';
                if ($date instanceof \DateTimeInterface) return $date->format('Y-m-d');
                try {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return '';
                }
            };

            $data = $clients->map(function ($client) use ($formatDate, $formatRawDate) {
                return [
                    'id' => $client->id,
                    'policy_no' => $client->policy_no ?? '-',
                    'company' => $client->company ?? '-',
                    'first_name' => $client->first_name ?? '',
                    'last_name' => $client->last_name ?? '',
                    'full_name' => trim($client->first_name . ' ' . $client->last_name),
                    'phone' => $client->phone ?? '-',
                    'email' => $client->email ?? '-',
                    'address' => $client->address ?? '',
                    'suburb' => $client->suburb ?? '',
                    'city' => $client->city ?? '',
                    'post_code' => $client->post_code ?? '',
                    'dob' => $formatDate($client->dob, 'd/m/Y'),
                    'dob_raw' => $formatRawDate($client->dob),
                    'login_date' => $formatDate($client->login_date, 'd/m/Y'),
                    'login_date_raw' => $formatRawDate($client->login_date),
                    'anp' => ($client->anp !== null && $client->anp !== '') ? '$' . number_format((float) $client->anp, 2) : '-',
                    'anp_raw' => $client->anp ?? 0,
                    'outcome' => $client->outcome ?? '-',
                    'adviser' => $client->adviser ?? '-',
                    'not_counting' => $client->not_counting ? 'Yes' : 'No',
                    'compliance_by' => $client->compliance_by ?? '-',
                    'roa_due_date' => $formatDate($client->roa_due_date, 'd/m/Y'),
                    'roa_due_date_raw' => $formatRawDate($client->roa_due_date),
                    'status_compliance' => $client->status_compliance ?? 'Sent to Compliance',
                    'sent_to_client' => $client->sent_to_client ?? 'Pending',
                    'claim_admin' => $client->claim_admin ?: ($client->adviser ?? ''),
                    'npw_admin' => $client->npw_admin ?: ($client->adviser ?? ''),
                    'canc_admin' => $client->canc_admin ?: ($client->adviser ?? ''),
                    'move_to_inforce_url' => route('clients.login.move-to-inforce', $client->id),
                    'destroy_url' => route('clients.login.destroy', $client->id),
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('loginClientsData error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function inforceClientsData(Request $request): JsonResponse
    {
        $query = $this->deduplicatedClientQuery('Inforce');
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }
        $filteredRecords = $query->count();

        $columns = ['first_name', 'phone', 'dob', 'city', 'status', 'lead_source_id', 'policy_no', 'login_date', 'id'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'login_date' => $client->login_date ? $client->login_date->format('d/m/Y') : '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'req_type' => $client->req_type ?? '-',
                'req_processed_by' => $client->req_processed_by ?? '-',
                'req_outcome' => $client->req_outcome ?? '-',
                'req_date' => $client->req_date ? $client->req_date->format('d/m/Y') : '-',
                'req_date_raw' => $client->req_date ? $client->req_date->format('Y-m-d') : '',
                'req_finished_date' => $client->req_finished_date ? $client->req_finished_date->format('d/m/Y') : '-',
                'req_finished_date_raw' => $client->req_finished_date ? $client->req_finished_date->format('Y-m-d') : '',
                'adviser' => $client->adviser ?? '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function claimsClientsData(Request $request): JsonResponse
    {
        $query = $this->deduplicatedClientQuery('Claims');
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('claim_type', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }

        
        $filteredRecords = $query->count();

        $columns = ['policy_no', 'company', 'first_name', 'phone', 'claim_type', 'claim_admin', 'claim_processed_date', 'claim_update_status'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'claim_type' => $client->claim_type ?? '-',
                'claim_admin' => $client->claim_admin ?? '-',
                'claim_processed_date' => $client->claim_processed_date ? $client->claim_processed_date->format('d/m/Y') : '-',
                'claim_processed_date_raw' => $client->claim_processed_date ? $client->claim_processed_date->format('Y-m-d') : '',
                'claim_update_status' => $client->claim_update_status ?? '-',
                'claim_approved_date' => $client->claim_approved_date ? $client->claim_approved_date->format('d/m/Y') : '-',
                'claim_approved_date_raw' => $client->claim_approved_date ? $client->claim_approved_date->format('Y-m-d') : '',
                'claim_result' => $client->claim_result ?? '-',
                'adviser' => $client->adviser ?? '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function cancellationClientsData(Request $request): JsonResponse
    {
         $query = $this->deduplicatedClientQuery('Cancellation');
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }

       
        $filteredRecords = $query->count();

        $columns = ['policy_no', 'company', 'first_name', 'phone', 'canc_date_sent', 'canc_completed_date', 'canc_outcome', 'canc_admin'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'canc_date_sent' => $client->canc_date_sent ? $client->canc_date_sent->format('d/m/Y') : '-',
                'canc_date_sent_raw' => $client->canc_date_sent ? $client->canc_date_sent->format('Y-m-d') : '',
                'canc_completed_date' => $client->canc_completed_date ? $client->canc_completed_date->format('d/m/Y') : '-',
                'canc_completed_date_raw' => $client->canc_completed_date ? $client->canc_completed_date->format('Y-m-d') : '',
                'canc_outcome' => $client->canc_outcome ?? '-',
                'canc_admin' => $client->canc_admin ?? '-',
                'canc_comments' => $client->canc_comments ?? '',
                'adviser' => $client->adviser ?? '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function npwDeferredClientsData(Request $request): JsonResponse
    {
        $query = $this->deduplicatedClientQuery('NPW Deferred');
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }

        
        $filteredRecords = $query->count();

        $columns = ['policy_no', 'company', 'first_name', 'phone', 'npw_admin', 'npw_issue_date', 'npw_outcome', 'npw_comments', 'npw_date_deferred'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'npw_admin' => $client->npw_admin ?? '-',
                'npw_issue_date' => $client->npw_issue_date ? $client->npw_issue_date->format('d/m/Y') : '-',
                'npw_issue_date_raw' => $client->npw_issue_date ? $client->npw_issue_date->format('Y-m-d') : '',
                'npw_outcome' => $client->npw_outcome ?? '-',
                'npw_comments' => $client->npw_comments ?? 'No comments.',
                'npw_date_deferred' => $client->npw_date_deferred ? $client->npw_date_deferred->format('d/m/Y') : '-',
                'npw_date_deferred_raw' => $client->npw_date_deferred ? $client->npw_date_deferred->format('Y-m-d') : '',
                'npw_premium' => $client->npw_premium ?? $client->anp ?? 0,
                'npw_premium_mode' => $client->npw_premium_mode ?? 'Monthly',
                'anp_raw' => $client->anp ?? 0,
                'adviser' => $client->adviser ?? '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function inactiveClientsData(Request $request): JsonResponse
    {
         $query = $this->deduplicatedClientQuery('Inactive');
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }


        $columns = ['policy_no', 'company', 'first_name', 'phone', 'inactive_admin', 'inactive_date', 'inactive_outcome', 'inactive_comments', 'inactive_new_company'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'inactive_admin' => $client->inactive_admin ?? '-',
                'inactive_date' => $client->inactive_date ? $client->inactive_date->format('d/m/Y') : '-',
                'inactive_date_raw' => $client->inactive_date ? $client->inactive_date->format('Y-m-d') : '',
                'inactive_outcome' => $client->inactive_outcome ?? '-',
                'inactive_comments' => $client->inactive_comments ?? '',
                'inactive_new_company' => $client->inactive_new_company ?? '-',
                'adviser' => $client->adviser ?? '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function allClientsData(Request $request): JsonResponse
    {
       $query = $this->deduplicatedClientQuery(null);
        $totalRecords = (clone $query)->count();
        $this->applyClientSearchFilters($query, $request);

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('policy_no', 'like', "%{$searchValue}%")
                    ->orWhere('company', 'like', "%{$searchValue}%")
                    ->orWhere('first_name', 'like', "%{$searchValue}%")
                    ->orWhere('last_name', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$searchValue}%"]);
            });
        }

       
        $filteredRecords = $query->count();

        $columns = ['policy_no', 'company', 'first_name', 'phone', 'status', 'adviser', 'created_at', 'updated_at'];
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->latest();
        }

        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'policy_no' => $client->policy_no ?? '-',
                'company' => $client->company ?? '-',
                'first_name' => $client->first_name ?? '',
                'last_name' => $client->last_name ?? '',
                'full_name' => trim($client->first_name . ' ' . $client->last_name),
                'phone' => $client->phone ?? '-',
                'email' => $client->email ?? '-',
                'address' => $client->address ?? '',
                'suburb' => $client->suburb ?? '',
                'city' => $client->city ?? '',
                'post_code' => $client->post_code ?? '',
                'dob' => $client->dob ? $client->dob->format('d/m/Y') : '-',
                'dob_raw' => $client->dob ? $client->dob->format('Y-m-d') : '',
                'status' => $client->status ?? '-',
                'adviser' => $client->adviser ?? '-',
                'created_at' => $client->created_at ? $client->created_at->format('d/m/Y') : '-',
                'updated_at' => $client->updated_at ? $client->updated_at->format('d/m/Y') : '-',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function storeLoginClient(Request $request)
    {
        $data = $request->validate([
            'policy_no' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:100',
            'login_date' => 'nullable|date',
            'anp' => 'nullable|numeric',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:200',
            'suburb' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'post_code' => 'nullable|string|max:20',
            'adviser' => 'nullable|string|max:100',
            'not_counting' => 'nullable|boolean',
            'compliance_by' => 'nullable|string|max:100',
            'roa_due_date' => 'nullable|date',
            'status_compliance' => 'nullable|string|max:50',
            'sent_to_client' => 'nullable|string|max:20',
            'outcome' => 'nullable|string|max:300',
        ]);

        $data['status'] = 'Login Client';
        $data['not_counting'] = $request->boolean('not_counting');

        $client = Client::create($data);

        // Notify the assigned adviser
        if (! empty($data['adviser'])) {
            $adviser = User::where('name', $data['adviser'])->first();
            if ($adviser) {
                $adviser->notify(new NewLeadAssigned($client));
            }
        }
        // Also notify all super-admins
        $superAdmins = User::whereHas('role', fn ($q) => $q->where('is_super_admin', true))->get();
        Notification::send($superAdmins, new NewLeadAssigned($client));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login client added successfully.',
                'client' => $client->fresh(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Login client added successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Login client added successfully.');
    }
    
    public function update(Request $request, Client $client)
    {
        $rules = $this->loginClientRules();
        $rules['status'] = 'nullable|string|max:50';
        $rules['lead_source_id'] = 'nullable|integer|exists:lead_sources,id';

        $data = $request->validate($rules);

        if ($request->has('not_counting')) {
            $data['not_counting'] = $request->boolean('not_counting');
        }

        $client->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'client' => $client->fresh(),
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }


    public function updateLoginClient(Request $request, Client $client)
    {
        $data = $request->validate($this->loginClientRules());

        $data['not_counting'] = $request->boolean('not_counting');
        $client->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'client' => $client->fresh(),
            ]);
        }

        return redirect()->route('clients.login')->with('success', 'Client updated successfully.');
    }

    public function moveLoginClientToInforce(Request $request, Client $client)
    {
        abort_unless($client->status === 'Login Client', 409, 'Only Login Clients can be moved to Inforce.');

        $data = $request->validate($this->loginClientRules());
        $data['not_counting'] = $request->boolean('not_counting');
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $data['inforce_date'] = now()->toDateString();
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        return response()->json([
            'success' => true,
            'message' => 'Client moved to Inforce successfully.',
            'client' => $client->fresh(),
        ]);
    }

    private function loginClientRules(): array
    {
        return [
            'policy_no' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:100',
            'login_date' => 'nullable|date',
            'anp' => 'nullable|numeric',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:200',
            'suburb' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'post_code' => 'nullable|string|max:20',
            'adviser' => 'nullable|string|max:100',
            'not_counting' => 'nullable|boolean',
            'compliance_by' => 'nullable|string|max:100',
            'roa_due_date' => 'nullable|date',
            'status_compliance' => 'nullable|string|max:50',
            'sent_to_client' => 'nullable|string|max:20',
            'outcome' => 'nullable|string|max:300',
        ];
    }

    public function markAsClaim(Request $request, Client $client)
    {
        $data = $request->validate([
            'claim_type' => 'required|string|max:200',
            'claim_admin' => 'nullable|string|max:100',
            'claim_processed_date' => 'nullable|date',
            'claim_update_status' => 'nullable|string|max:50',
            'claim_approved_date' => 'nullable|date',
            'claim_result' => 'nullable|string|max:300',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Claims';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Claims');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Claims successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Claims successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client moved to Claims successfully.');
    }

    public function markNpwAsClaim(Request $request, Client $client)
    {
        $data = $request->validate([
            'claim_type' => 'required|string|max:200',
            'claim_admin' => 'nullable|string|max:100',
            'claim_processed_date' => 'nullable|date',
            'claim_update_status' => 'nullable|string|max:50',
            'claim_approved_date' => 'nullable|date',
            'claim_result' => 'nullable|string|max:300',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Claims';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Claims');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Claim record saved. Client moved to Claims.']);
        }

        return redirect()->route('clients.npw-deferred')
            ->with('success', 'Claim record saved. Client moved to Claims.');
    }

    public function markNpwAsInforce(Request $request, Client $client)
    {
        $data = $request->validate([
            'inforce_date' => 'required|date',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        return redirect()->route('clients.inforce')
            ->with('success', 'Client moved to Inforce successfully.');
    }

    public function markNpwAsCancellation(Request $request, Client $client)
    {
        $data = $request->validate([
            'canc_date_sent' => 'nullable|date',
            'canc_completed_date' => 'nullable|date',
            'canc_outcome' => 'nullable|string|max:255',
            'canc_comments' => 'nullable|string',
            'canc_admin' => 'nullable|string|max:100',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Cancellation';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Cancellation');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cancellation update saved. Client moved to Cancellation.']);
        }

        return redirect()->route('clients.cancellation')
            ->with('success', 'Cancellation update saved. Client moved to Cancellation.');
    }

    public function markClientStageAsClaim(Request $request, Client $client)
    {
        $stage = $request->route('stage') ?? $request->segment(2);
        $data = $request->validate([
            'claim_type' => 'required|string|max:200',
            'claim_admin' => 'nullable|string|max:100',
            'claim_processed_date' => 'nullable|date',
            'claim_update_status' => 'nullable|string|max:50',
            'claim_approved_date' => 'nullable|date',
            'claim_result' => 'nullable|string|max:300',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Claims';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Claims');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Claim record saved. Client moved to Claims.',
                'client' => $client->fresh(),
            ]);
        }

        return redirect()->route($stage === 'all' ? 'clients.index' : 'clients.'.$stage)
            ->with('success', 'Claim record saved. Client moved to Claims.');
    }

    public function markAsCancellation(Request $request, Client $client)
    {
        $data = $request->validate([
            'canc_date_sent' => 'nullable|date',
            'canc_completed_date' => 'nullable|date',
            'canc_outcome' => 'nullable|string|max:255',
            'canc_comments' => 'nullable|string',
            'canc_admin' => 'nullable|string|max:100',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Cancellation';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Cancellation');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Cancellation successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Cancellation successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client moved to Cancellation successfully.');
    }

    public function markInforceAsCancellation(Request $request, Client $client)
    {
        $data = $request->validate([
            'canc_date_sent' => 'nullable|date',
            'canc_completed_date' => 'nullable|date',
            'canc_outcome' => 'nullable|string|max:255',
            'canc_comments' => 'nullable|string',
            'canc_admin' => 'nullable|string|max:100',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Cancellation';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Cancellation');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cancellation update saved. Client moved to Cancellation.']);
        }

        return redirect()->route('clients.cancellation')
            ->with('success', 'Cancellation update saved. Client moved to Cancellation.');
    }

    public function markInactiveAsInforce(Request $request, Client $client)
    {
        $data = $request->validate([
            'inforce_date' => 'required|date',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        return redirect()->route('clients.inforce')
            ->with('success', 'Client moved to Inforce successfully.');
    }

    public function markClaimsAsInforce(Request $request, Client $client)
    {
        $data = $request->validate([
            'inforce_date' => 'required|date',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        return redirect()->route('clients.inforce')
            ->with('success', 'Client moved to Inforce successfully.');
    }

    public function markClaimsAsCancellation(Request $request, Client $client)
    {
        $data = $request->validate([
            'canc_date_sent' => 'nullable|date',
            'canc_completed_date' => 'nullable|date',
            'canc_outcome' => 'nullable|string|max:255',
            'canc_comments' => 'nullable|string',
            'canc_admin' => 'nullable|string|max:100',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Cancellation';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Cancellation');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cancellation update saved. Client moved to Cancellation.']);
        }

        return redirect()->route('clients.cancellation')
            ->with('success', 'Cancellation update saved. Client moved to Cancellation.');
    }

    public function markCancellationAsInforce(Request $request, Client $client)
    {
        $data = $request->validate([
            'inforce_date' => 'required|date',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        return redirect()->route('clients.inforce')
            ->with('success', 'Client moved to Inforce successfully.');
    }

    public function markCancellationAsInactive(Request $request, Client $client)
    {
        $oldStage = $client->status;
        $client->update(['status' => 'Inactive']);

        $this->notifyStageChange($client, $oldStage, 'Inactive');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client marked as Inactive successfully.']);
        }

        return redirect()->route('clients.inactive')
            ->with('success', 'Client marked as Inactive successfully.');
    }

    public function markAsNpwDeferred(Request $request, Client $client)
    {
        $data = $request->validate([
            'npw_issue_date' => 'nullable|date',
            'npw_premium' => 'required|numeric|min:0',
            'npw_premium_mode' => 'nullable|string|max:100',
            'npw_admin' => 'nullable|string|max:100',
            'npw_pending' => 'nullable|string|max:50',
            'npw_comments' => 'nullable|string',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'NPW Deferred';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'NPW Deferred');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to NPW Deferred successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to NPW Deferred successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client moved to NPW Deferred successfully.');
    }

    public function markAsInactive(Request $request, Client $client)
    {
        $oldStage = $client->status;
        $client->update(['status' => 'Inactive']);

        $this->notifyStageChange($client, $oldStage, 'Inactive');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client marked as Inactive successfully.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client marked as Inactive successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client marked as Inactive successfully.');
    }

    public function markAsInforce(Request $request, Client $client)
    {
        $data = $request->validate([
            'inforce_date' => 'required|date',
            'inforce_request_type' => 'required|string|max:100',
            'inforce_process_status' => 'nullable|string|max:50',
            'inforce_process_by' => 'nullable|string|max:100',
            'inforce_outcome' => 'nullable|string|max:500',
            'inforce_comments' => 'nullable|string|max:1000',
            'inforce_finished_date' => 'nullable|date',
        ]);

        $data = $this->applyEditedClientName($data, $request);
        $oldStage = $client->status;
        $data['status'] = 'Inforce';
        $client->update($data);

        $this->notifyStageChange($client, $oldStage, 'Inforce');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client moved to Inforce successfully.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client moved to Inforce successfully.');
    }

    public function destroyLoginClient(Request $request, Client $client)
    {
        if (! $request->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only administrators can delete client records.'], 403);
            }
            return redirect()->back()->withErrors(['error' => 'Only administrators can delete client records.']);
        }

        $client->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client deleted.']);
        }

        return redirect()->route('clients.login')->with('success', 'Client deleted.');
    }

    /**
     * Notify the assigned adviser and all super-admins of a stage change.
     */
    private function applyEditedClientName(array $data, Request $request): array
    {
        $name = trim((string) $request->input('client_name', ''));

        validator(['client_name' => $name], [
            'client_name' => 'required|string|max:255',
        ])->validate();

        $parts = preg_split('/\s+/', $name, 2);
        $data['first_name'] = $parts[0];
        $data['last_name'] = $parts[1] ?? null;

        $company = trim((string) $request->input('company', ''));
        if ($company !== '') {
            $matchedCompany = Insurer::where('is_active', true)
                ->where('name', $company)
                ->value('name');

            if ($matchedCompany === null) {
                validator(['company' => $company], [
                    'company' => 'in:'.Insurer::where('is_active', true)->pluck('name')->implode(','),
                ])->validate();
            }

            $data['company'] = $matchedCompany;
        }

        return $data;
    }

    private function notifyStageChange(Client $client, string $oldStage, string $newStage): void
    {
        $recipients = collect();

        // Add the assigned adviser if found
        if (! empty($client->adviser)) {
            $adviser = User::where('name', $client->adviser)->first();
            if ($adviser) {
                $recipients->push($adviser);
            }
        }

        // Add all super-admins
        $superAdmins = User::whereHas('role', fn ($q) => $q->where('is_super_admin', true))->get();
        $recipients = $recipients->merge($superAdmins)->unique('id');

        Notification::send($recipients, new StageChangeNotification($client, $oldStage, $newStage));
    }

    // ─── Delete (Admin Only) ──────────────────────────────────────────────────

    public function destroyInforceClient(Request $request, Client $client): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only administrators can delete client records.'], 403);
            }
            return redirect()->back()->withErrors(['error' => 'Only administrators can delete client records.']);
        }

        $client->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Client record deleted successfully.']);
        }

        return redirect()->route('clients.inforce')->with('success', 'Client record deleted successfully.');
    }

}

