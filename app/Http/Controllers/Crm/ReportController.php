<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $advisors = $this->advisorUsers();
        $sources = LeadSource::all();

        return view('pages.crm.reports', compact('advisors', 'sources'));
    }

    public function data(Request $request)
    {
        $dateRange = $request->input('date_range');
        $advisorId = $request->input('advisor_id');
        $sourceId = $request->input('source_id');

        // Parse date range
        $startDate = null;
        $endDate = null;
        if ($dateRange && strpos($dateRange, ' to ') !== false) {
            $dates = explode(' to ', $dateRange);
            $startDate = Carbon::parse($dates[0])->startOfDay();
            $endDate = Carbon::parse($dates[1])->endOfDay();
        } elseif ($dateRange) {
            $startDate = Carbon::parse($dateRange)->startOfDay();
            $endDate = Carbon::parse($dateRange)->endOfDay();
        }

        // Base Queries
        $clientsQuery = Client::query();
        $leadsQuery = Lead::query();

        if ($startDate && $endDate) {
            $clientsQuery->whereBetween('created_at', [$startDate, $endDate]);
            $leadsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $advisors = $this->advisorUsers();
        $selectedAdvisor = $advisorId ? $advisors->firstWhere('id', (int) $advisorId) : null;
        $advisorGroups = $advisors->groupBy(fn ($advisor) => mb_strtolower(trim($advisor->name)));
        if ($selectedAdvisor) {
            $selectedNameKey = mb_strtolower(trim($selectedAdvisor->name));
            $advisorGroups = $advisorGroups->only([$selectedNameKey]);
            $this->applyAdvisorAssignment($clientsQuery, $advisorGroups->get($selectedNameKey));
            $this->applyAdvisorAssignment($leadsQuery, $advisorGroups->get($selectedNameKey));
        } elseif ($advisorId) {
            $advisorGroups = collect();
            $clientsQuery->whereRaw('1 = 0');
            $leadsQuery->whereRaw('1 = 0');
        }

        if ($sourceId) {
            $clientsQuery->where('lead_source_id', $sourceId);
            $leadsQuery->where('lead_source_id', $sourceId);
        }

        // 1. Generation Over Time (Clients grouped by Month-Year)
        $timeDataRaw = (clone $clientsQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%b %Y") as month_name, count(*) as count, MIN(created_at) as min_date')
            ->groupBy('month_name')
            ->orderBy('min_date')
            ->get();
            
        $timeCategories = [];
        $timeDataCounts = [];
        foreach($timeDataRaw as $row) {
            $timeCategories[] = $row->month_name;
            $timeDataCounts[] = $row->count;
        }

        // 2. Advisor Conversion Funnel (Area Chart)
        // Grouping Conversion Rates by Month within the date range
        $funnelData = [];
        $funnelCategories = [];

        if ($startDate && $endDate) {
            $currentDate = $startDate->copy()->startOfMonth();
            while ($currentDate <= $endDate) {
                $monthStart = $currentDate->copy();
                $monthEnd = $currentDate->copy()->endOfMonth();

                $cCount = (clone $clientsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->count();
                $lCount = (clone $leadsQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->count();

                $total = $cCount + $lCount;
                $conversionRate = $total > 0 ? round(($cCount / $total) * 100, 1) : 0;

                $funnelCategories[] = $currentDate->format('M Y');
                $funnelData[] = $conversionRate;

                $currentDate->addMonth();
            }
        }

        // 3. Advisor Performance Table
        $advisorPerformance = [];
        foreach ($advisorGroups as $advisorGroup) {
            $advisorName = $advisorGroup->first()->name;
            $cQuery = clone $clientsQuery;
            $lQuery = clone $leadsQuery;
            $this->applyAdvisorAssignment($cQuery, $advisorGroup);
            $this->applyAdvisorAssignment($lQuery, $advisorGroup);

            $inforceClients = (clone $cQuery)->where('status', 'Inforce')->count();
            $claimUpdates = (clone $cQuery)->where('status', 'Claims')->count();
            $anpTotal = (clone $cQuery)->sum('anp');

            $totalClients = $cQuery->count();
            $totalLeads = $lQuery->count();
            $totalGenerated = $totalClients + $totalLeads;

            $conversionRate = $totalGenerated > 0 ? round(($totalClients / $totalGenerated) * 100, 1) : 0;

            $advisorPerformance[] = [
                'name' => $advisorName,
                'inforce' => $inforceClients,
                'claims' => $claimUpdates,
                'conversion_rate' => $conversionRate,
                'anp' => $anpTotal,
            ];
        }

        // 4. Client Status Distribution
        $statusDistribution = [];
        $statuses = ['Login Client', 'Inforce', 'Inactive', 'Claims', 'Cancellation', 'NPW Deferred'];
        foreach ($statuses as $status) {
            $count = (clone $clientsQuery)->where('status', $status)->count();
            if ($count > 0) {
                $statusDistribution[] = [
                    'name' => $status,
                    'count' => $count,
                ];
            }
        }

        // 5. Pipeline Snapshot
        $pipelineSnapshot = [
            ['name' => 'Total Leads', 'count' => (clone $leadsQuery)->count()],
            ['name' => 'Login Clients', 'count' => (clone $clientsQuery)->where('status', 'Login Client')->count()],
            ['name' => 'Inforce Clients', 'count' => (clone $clientsQuery)->where('status', 'Inforce')->count()],
        ];

        return response()->json([
            'generationOverTime' => [
                'categories' => $timeCategories,
                'data' => $timeDataCounts,
            ],
            'funnelChart' => [
                'categories' => $funnelCategories,
                'data' => $funnelData,
            ],
            'statusDistribution' => [
                'categories' => array_column($statusDistribution, 'name'),
                'data' => array_column($statusDistribution, 'count'),
            ],
            'pipelineSnapshot' => [
                'categories' => array_column($pipelineSnapshot, 'name'),
                'data' => array_column($pipelineSnapshot, 'count'),
            ],
            'performanceTable' => $advisorPerformance,
        ]);
    }

    private function advisorUsers()
    {
        return User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['advisor', 'insurance-advisor'])
                ->orWhere('name', 'like', '%advisor%')
                ->orWhere('name', 'like', '%adviser%');
        })->orderBy('name')->get();
    }

    private function applyAdvisorAssignment($query, $advisorGroup): void
    {
        if (!$advisorGroup || $advisorGroup->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $advisorIds = $advisorGroup->pluck('id');
        $advisorNames = $advisorGroup->flatMap(function ($advisor) {
            $name = trim($advisor->name);
            $firstName = preg_split('/\\s+/', $name)[0] ?? $name;

            return [$name, $firstName];
        })->map(fn ($name) => mb_strtolower(trim($name)))->unique()->values()->all();
        $namePlaceholders = implode(', ', array_fill(0, count($advisorNames), '?'));

        $query->where(function ($assignment) use ($advisorIds, $advisorNames, $namePlaceholders) {
            $assignment->whereIn('user_id', $advisorIds)
                ->orWhere(function ($legacy) use ($advisorNames, $namePlaceholders) {
                    $legacy->whereRaw("LOWER(TRIM(adviser)) IN ({$namePlaceholders})", $advisorNames);
                });
        });
    }
}
