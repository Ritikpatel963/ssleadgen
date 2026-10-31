<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Imports\LeadsImport;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LookupValue;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:120'],
            'adviser' => ['nullable', 'string', 'max:120'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
        ]);

        \App\Http\Controllers\Settings\DropdownValueController::ensureDefaultPipelineStages();

        $baseQuery = $this->buildFilteredLeadQuery($filters);

        // Calculate accurate total counts for each kanban stage
        $countsQuery = clone $baseQuery;
        $stageCounts = $countsQuery->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $pipelineStages = LookupValue::where('group', 'pipeline_stage')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $statuses = $pipelineStages->pluck('stage_key')->unique()->values()->all();
        if (empty($statuses)) {
            $statuses = ['new', 'contacted', 'proposal', 'won'];
        }
        $leadsByStatus = array_fill_keys($statuses, collect());
        $leads = collect();

        $userAdvisers = User::whereHas('role', function ($query) {
           $query->where('slug', 'advisor')
                ->orWhere('slug', 'insurance-advisor')
                ->orWhere('name', 'like', '%advisor%')
                ->orWhere('name', 'like', '%adviser%');
        })->pluck('name');

        $leadAdvisers = Lead::whereNotNull('adviser')
            ->where('adviser', '!=', '')
            ->distinct()
            ->pluck('adviser');

        $advisers = $userAdvisers->concat($leadAdvisers)->filter()->unique()->sort()->values();
        $leadSourceCategories = LookupValue::where('group', 'lead_source')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('pages.crm.pipeline', compact('leads', 'leadsByStatus', 'stageCounts', 'advisers', 'leadSourceCategories', 'pipelineStages'));
    }

    public function listLeads(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filter_search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:120'],
            'adviser' => ['nullable', 'string', 'max:120'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:120'],
            'order.0.column' => ['nullable', 'integer', 'min:0', 'max:5'],
            'order.0.dir' => ['nullable', 'in:asc,desc'],
        ]);

        $baseQuery = $this->buildFilteredLeadQuery([
            'search' => $filters['filter_search'] ?? '',
            'status' => $filters['status'] ?? null,
            'source' => $filters['source'] ?? null,
            'adviser' => $filters['adviser'] ?? null,
            'created_from' => $filters['created_from'] ?? null,
            'created_to' => $filters['created_to'] ?? null,
        ]);
        $recordsTotal = Lead::count();

        $tableSearch = trim((string) data_get($filters, 'search.value', ''));
        if ($tableSearch !== '') {
            $baseQuery->where(function ($leads) use ($tableSearch) {
                $leads->where('first_name', 'like', "%{$tableSearch}%")
                    ->orWhere('last_name', 'like', "%{$tableSearch}%")
                    ->orWhere('email', 'like', "%{$tableSearch}%")
                    ->orWhere('phone', 'like', "%{$tableSearch}%")
                    ->orWhereHas('leadSource', fn ($source) => $source->where('name', 'like', "%{$tableSearch}%"));
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();
        $sortableColumns = [0 => 'updated_at', 1 => 'phone', 2 => 'email', 3 => 'lead_source_id', 4 => 'status'];
        $orderColumn = (int) data_get($filters, 'order.0.column', 0);
        $orderDirection = data_get($filters, 'order.0.dir', 'desc');
        $sortColumn = $sortableColumns[$orderColumn] ?? 'updated_at';

        $leads = $baseQuery->with('leadSource')
            ->orderBy($sortColumn, $orderDirection)
            ->orderByDesc('id')
            ->skip((int) ($filters['start'] ?? 0))
            ->take((int) ($filters['length'] ?? 10))
            ->get();

        return response()->json([
            'draw' => (int) ($filters['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $leads->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'full_name' => trim($lead->first_name . ' ' . $lead->last_name),
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->leadSource->name ?? 'Unknown',
                'adviser' => $lead->adviser ?? '-',
                'estimated_cover' => $lead->estimated_cover,
                'notes' => $lead->notes,
                'status' => $lead->status,
                'updated_at' => $lead->updated_at?->timestamp ?? $lead->id,
            ]),
        ]);
    }

    public function stageLeads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:80'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'adviser' => ['nullable', 'string', 'max:120'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
        ]);

        $offset = (int) ($validated['offset'] ?? 0);
        $limit = (int) ($validated['limit'] ?? 20);

        $query = $this->buildFilteredLeadQuery($validated)
            ->where('status', $validated['status']);

        $total = (clone $query)->count();
        $leads = $query->orderByDesc('updated_at')->orderByDesc('id')->skip($offset)->take($limit)->get();

        $items = $leads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'full_name' => trim($lead->first_name . ' ' . $lead->last_name),
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->leadSource->name ?? 'Unknown',
                'adviser' => $lead->adviser ?? '-',
                'estimated_cover' => $lead->estimated_cover,
                'notes' => $lead->notes,
                'status' => $lead->status,
                'created_at_human' => $lead->created_at ? $lead->created_at->diffForHumans() : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'status' => $validated['status'],
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'has_more' => ($offset + $leads->count()) < $total,
            'leads' => $items,
        ]);
    }

    private function buildFilteredLeadQuery(array $filters)
    {
        $query = Lead::with('leadSource');
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($leads) use ($search) {
                $leads->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['source'])) $query->whereHas('leadSource', fn ($source) => $source->where('name', $filters['source']));
         if (!empty($filters['adviser'])) $query->where('adviser', $filters['adviser']);
        if (!empty($filters['created_from'])) $query->where('created_at', '>=', Carbon::parse($filters['created_from'])->startOfDay());
        if (!empty($filters['created_to'])) $query->where('created_at', '<', Carbon::parse($filters['created_to'])->addDay()->startOfDay());

        return $query;
    }

    public function create()
    {
        $leadSources = LeadSource::where('is_active', true)->get();
        \App\Http\Controllers\Settings\DropdownValueController::ensureDefaultPipelineStages();
        $pipelineStages = LookupValue::where('group', 'pipeline_stage')->where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.crm.create', compact('leadSources', 'pipelineStages'));
    }

    public function store(StoreLeadRequest $request)
    {
        $data = $request->validated();
        if (!empty($data['lead_source'])) {
            $source = LeadSource::firstOrCreate(['name' => $data['lead_source']], ['is_active' => true]);
            $data['lead_source_id'] = $source->id;
            unset($data['lead_source']);
        }

        Lead::create($data);

        return redirect()->route('crm.pipeline')->with('success', 'Lead created successfully.');
    }
    
    public function show(Lead $lead): JsonResponse
    {
        $lead->load('leadSource');

        return response()->json([
            'success' => true,
            'lead' => [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'full_name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
                'email' => $lead->email,
                'phone' => $lead->phone,
                'lead_source' => $lead->leadSource?->name ?? '',
                'lead_source_id' => $lead->lead_source_id,
                'estimated_cover' => $lead->estimated_cover,
                'status' => $lead->status,
                'policy_no' => $lead->policy_no,
                'company' => $lead->company,
                'login_date' => $lead->login_date ? Carbon::parse($lead->login_date)->format('Y-m-d') : '',
                'anp' => $lead->anp,
                'dob' => $lead->dob ? Carbon::parse($lead->dob)->format('Y-m-d') : '',
                'address' => $lead->address,
                'suburb' => $lead->suburb,
                'city' => $lead->city,
                'post_code' => $lead->post_code,
                'adviser' => $lead->adviser,
                'not_counting' => $lead->not_counting,
                'compliance_by' => $lead->compliance_by,
                'roa_due_date' => $lead->roa_due_date ? Carbon::parse($lead->roa_due_date)->format('Y-m-d') : '',
                'status_compliance' => $lead->status_compliance,
                'sent_to_client' => $lead->sent_to_client,
                'outcome' => $lead->outcome,
                'notes' => $lead->notes,
            ],
        ]);
    }

    public function update(StoreLeadRequest $request, Lead $lead)
    {
        $data = $request->validated();
        if (!empty($data['lead_source'])) {
            $source = LeadSource::firstOrCreate(['name' => $data['lead_source']], ['is_active' => true]);
            $data['lead_source_id'] = $source->id;
            unset($data['lead_source']);
        } elseif (array_key_exists('lead_source', $data) && empty($data['lead_source'])) {
            $data['lead_source_id'] = null;
            unset($data['lead_source']);
        }

        $lead->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully.',
                'lead' => $lead->fresh('leadSource'),
            ]);
        }

        return redirect()->route('crm.pipeline')->with('success', 'Lead updated successfully.');
    }
    public function destroy(Request $request, Lead $lead)
    {
        if (! $request->user()->isSuperAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only administrators can delete leads.'], 403);
            }
            return redirect()->back()->withErrors(['error' => 'Only administrators can delete leads.']);
        }

        $lead->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Lead deleted successfully.']);
        }

        return redirect()->route('crm.pipeline')->with('success', 'Lead deleted successfully.');
    }


    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
        ]);

        try {
            $import = new LeadsImport;
            Excel::import($import, $request->file('import_file'));

            return response()->json([
                'success' => true,
                'message' => "Leads imported successfully. Imported: {$import->rowsImported}, Skipped: {$import->rowsSkipped}.",
                'skipped_details' => $import->skippedDetails,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing leads: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function convert(Request $request, Lead $lead)
    {
        // Create a new client record
        $client = Client::create([
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'lead_source_id' => $lead->lead_source_id,
            'user_id' => $lead->user_id,
            'notes' => $lead->notes,
            'status' => 'Login Client',
            'policy_no' => $lead->policy_no,
            'company' => $lead->company,
            'login_date' => $lead->login_date,
            'anp' => $lead->anp,
            'dob' => $lead->dob,
            'address' => $lead->address,
            'suburb' => $lead->suburb,
            'city' => $lead->city,
            'post_code' => $lead->post_code,
            'adviser' => $lead->adviser,
            'not_counting' => in_array(strtolower($lead->not_counting), ['yes', 'on', '1', 'true']) ? 1 : 0,
            'compliance_by' => $lead->compliance_by,
            'roa_due_date' => $lead->roa_due_date,
            'status_compliance' => $lead->status_compliance,
            'sent_to_client' => $lead->sent_to_client ?? 'Pending',
            'outcome' => $lead->outcome,
        ]);

        // Delete the lead so it removes from the pipeline
        $lead->delete();

        return redirect()->route('clients.index')->with('success', 'Lead successfully converted to Client!');
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $request->validate(['status' => 'required|string|max:80']);

        $lead->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function storeNote(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $notes = json_decode($lead->notes ?? '[]', true);
        if (! is_array($notes)) {
            $notes = $lead->notes ? [['text' => $lead->notes, 'timestamp' => $lead->updated_at?->format('d M Y, h:i A')]] : [];
        }

        $notes[] = [
            'text' => $validated['note'],
            'timestamp' => Carbon::now()->format('d M Y, h:i A'),
        ];

        $lead->update(['notes' => json_encode($notes)]);

        return response()->json([
            'success' => true,
            'note' => end($notes),
        ]);
    }
}


