<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\LookupValue;
use App\Models\Policy;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // 1. Top KPI Cards Metrics (Overall totals)
        $totalClients = Client::count();
        $inforceClients = Client::where('status', 'Inforce')->count();
        $inactiveClients = Client::where('status', 'Inactive')->count();

        $activePolicies = Policy::where('status', 'Active')->count();
        $totalAnnualPremium = Client::sum('anp') ?? 0;
        $claimUpdates = Client::where('status', 'Claims')->count();
        $cancellationUpdates = Client::where('status', 'Cancellation')->count();

        // Top KPI Cards Growth Calculation (comparing this month vs last month)
        // Helper function for growth
        $calculateGrowth = function ($thisMonthCount, $lastMonthCount) {
            if ($lastMonthCount == 0) {
                return $thisMonthCount > 0 ? 100 : 0;
            }

            return (($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100;
        };

        $totalClientsGrowth = $calculateGrowth(
            Client::where('created_at', '>=', $startOfMonth)->count(),
            Client::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );
        $inforceClientsGrowth = $calculateGrowth(
            Client::where('status', 'Inforce')->where('updated_at', '>=', $startOfMonth)->count(),
            Client::where('status', 'Inforce')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );
        $inactiveClientsGrowth = $calculateGrowth(
            Client::where('status', 'Inactive')->where('updated_at', '>=', $startOfMonth)->count(),
            Client::where('status', 'Inactive')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );
        $activePoliciesGrowth = $calculateGrowth(
            Policy::where('status', 'Active')->where('created_at', '>=', $startOfMonth)->count(),
            Policy::where('status', 'Active')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );
        $annualPremiumGrowth = $calculateGrowth(
            Client::where('created_at', '>=', $startOfMonth)->sum('anp'),
            Client::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('anp')
        );
        $claimUpdatesGrowth = $calculateGrowth(
            Client::where('status', 'Claims')->where('updated_at', '>=', $startOfMonth)->count(),
            Client::where('status', 'Claims')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );
        $cancellationUpdatesGrowth = $calculateGrowth(
            Client::where('status', 'Cancellation')->where('updated_at', '>=', $startOfMonth)->count(),
            Client::where('status', 'Cancellation')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])->count()
        );

        // Donut Chart: Clients & Leads by Source Breakdown (Combined)
        // 1. Fetch active configured sources from Category Master (LookupValue)
        $configuredSources = LookupValue::where('group', 'lead_source')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        // 2. Aggregate counts from both clients and leads through leadSource relation
        $clientSourceCounts = Client::with('leadSource')
            ->selectRaw('lead_source_id, count(*) as count')
            ->groupBy('lead_source_id')
            ->get();

        $leadSourceCounts = Lead::with('leadSource')
            ->selectRaw('lead_source_id, count(*) as count')
            ->groupBy('lead_source_id')
            ->get();

        // Build a lookup map of lower-case configured source names to original configured casing
        $configuredSourceMap = [];
        $hasConfiguredOther = false;
        foreach ($configuredSources as $sourceName) {
            $lower = strtolower(trim($sourceName));
            $configuredSourceMap[$lower] = $sourceName;
            if (in_array($lower, ['other', 'others'], true)) {
                $hasConfiguredOther = true;
            }
        }

        // Initialize all configured sources with 0
        $groupedSources = [];
        foreach ($configuredSources as $sourceName) {
            $groupedSources[$sourceName] = 0;
        }

        $fallbackOtherKey = $hasConfiguredOther
            ? ($configuredSourceMap['others'] ?? $configuredSourceMap['other'])
            : 'Other';

        if (!isset($groupedSources[$fallbackOtherKey])) {
            $groupedSources[$fallbackOtherKey] = 0;
        }

        $allSourceCounts = $clientSourceCounts->concat($leadSourceCounts);
        foreach ($allSourceCounts as $item) {
            $rawName = $item->leadSource ? trim($item->leadSource->name) : '';
            $lowerName = strtolower($rawName);

            if ($rawName === '' || in_array($lowerName, ['other', 'others', 'null', 'none'], true)) {
                $groupedSources[$fallbackOtherKey] += $item->count;
            } elseif (isset($configuredSourceMap[$lowerName])) {
                $targetKey = $configuredSourceMap[$lowerName];
                $groupedSources[$targetKey] += $item->count;
            } else {
                // Dynamically add unlisted active source
                if (!isset($groupedSources[$rawName])) {
                    $groupedSources[$rawName] = 0;
                }
                $groupedSources[$rawName] += $item->count;
            }
        }

        // Remove fallback Other if count is 0 and it was not in configured sources
        if (!$hasConfiguredOther && isset($groupedSources['Other']) && $groupedSources['Other'] === 0) {
            unset($groupedSources['Other']);
        }

        // Activity Feed (Basic Implementation using recent clients and tasks)
        $activities = collect();

        $recentClients = Client::latest()->take(10)->get()->map(function ($client) {
            return [
                'type' => 'client',
                'title' => 'New client added',
                'name' => $client->first_name.' '.$client->last_name,
                'date' => $client->created_at ? $client->created_at->format('d M Y, h:i A') : 'N/A',
                'timestamp' => $client->created_at ? $client->created_at->timestamp : 0,
                'icon' => 'feather-user-plus',
                'bg' => 'bg-soft-primary',
                'text' => 'text-primary',
            ];
        });

        $recentTasks = Task::latest()->take(5)->get()->map(function ($task) {
            return [
                'type' => 'task',
                'title' => 'Task created',
                'name' => $task->client_name ?: 'No Client',
                'date' => $task->created_at ? $task->created_at->format('d M Y, h:i A') : 'N/A',
                'timestamp' => $task->created_at ? $task->created_at->timestamp : 0,
                'icon' => 'feather-calendar',
                'bg' => 'bg-soft-purple',
                'text' => 'text-purple',
            ];
        });

        $recentDocs = Document::latest()->take(5)->get()->map(function ($doc) {
            return [
                'type' => 'document',
                'title' => 'Document uploaded',
                'name' => $doc->title ?? 'Document',
                'date' => $doc->created_at->format('d M Y, h:i A'),
                'timestamp' => $doc->created_at->timestamp,
                'icon' => 'feather-file-text',
                'bg' => 'bg-soft-warning',
                'text' => 'text-warning',
            ];
        });

        $activities = $activities->concat($recentClients)->concat($recentTasks)->concat($recentDocs)
            ->sortByDesc('timestamp')
            ->take(5)
            ->values();

        $dashboardData = [
            'totalClients' => $totalClients,
            'inforceClients' => $inforceClients,
            'inactiveClients' => $inactiveClients,
            'sources' => $groupedSources,
            'activities' => $activities,
        ];

             // Recent Clients Table (balanced mix of 50 clients so all tabs have data)
        $recentInforce = Client::with(['leadSource', 'policies'])->inforce()->latest()->take(15)->get();
        $recentInactive = Client::with(['leadSource', 'policies'])->where('status', 'Inactive')->latest()->take(15)->get();
        $recentLogin = Client::with(['leadSource', 'policies'])->loginClients()->latest()->take(20)->get();
        
        $recentClientsTable = $recentInforce->merge($recentInactive)->merge($recentLogin)->sortByDesc('created_at')->values();

        // Upcoming Follow Ups (Appointments)
        $upcomingFollowUps = Appointment::where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->take(5)
            ->get();

        // Bottom KPI Cards Metrics
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // New Clients
        $newClientsThisMonth = Client::where('created_at', '>=', $startOfMonth)->count();
        $newClientsLastMonth = Client::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $newClientsGrowth = $newClientsLastMonth > 0 ? (($newClientsThisMonth - $newClientsLastMonth) / $newClientsLastMonth) * 100 : ($newClientsThisMonth > 0 ? 100 : 0);

        // Policies Issued
        $policiesThisMonth = Policy::where('created_at', '>=', $startOfMonth)->count();
        $policiesLastMonth = Policy::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $policiesGrowth = $policiesLastMonth > 0 ? (($policiesThisMonth - $policiesLastMonth) / $policiesLastMonth) * 100 : ($policiesThisMonth > 0 ? 100 : 0);

        // Claims Lodged
        $claimsThisMonth = Client::where('status', 'Claim')->where('updated_at', '>=', $startOfMonth)->count();
        $claimsLastMonth = Client::where('status', 'Claim')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $claimsGrowth = $claimsLastMonth > 0 ? (($claimsThisMonth - $claimsLastMonth) / $claimsLastMonth) * 100 : ($claimsThisMonth > 0 ? 100 : 0);

        // Pending Tasks
        $pendingTasksCount = Task::where('status', 'Pending')->count();

        return view('pages.dashboard.index', compact(
            'totalClients',
            'inforceClients',
            'inactiveClients',
            'activePolicies',
            'totalAnnualPremium',
            'claimUpdates',
            'cancellationUpdates',
            'totalClientsGrowth',
            'inforceClientsGrowth',
            'inactiveClientsGrowth',
            'activePoliciesGrowth',
            'annualPremiumGrowth',
            'claimUpdatesGrowth',
            'cancellationUpdatesGrowth',
            'dashboardData',
            'activities',
            'recentClientsTable',
            'upcomingFollowUps',
            'newClientsThisMonth',
            'newClientsGrowth',
            'policiesThisMonth',
            'policiesGrowth',
            'claimsThisMonth',
            'claimsGrowth',
            'pendingTasksCount'
        ));
    }
}
