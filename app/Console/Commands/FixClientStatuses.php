<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class FixClientStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:fix-statuses {--dry-run : Test without making changes} {--smart : Use smart assignment based on data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix NULL status values in clients table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nullClients = Client::whereNull('status')->get();
        
        $this->info("Found {$nullClients->count()} clients with NULL status");
        
        if ($nullClients->isEmpty()) {
            $this->info('✅ No clients need fixing. All status values are set.');
            return 0;
        }
        
        // Show preview
        $this->warn("\nPreview of clients with NULL status (first 10):");
        $this->table(
            ['ID', 'Name', 'Policy No', 'Login Date', 'Inforce Date', 'Claim Type'],
            $nullClients->take(10)->map(fn($c) => [
                $c->id,
                trim($c->first_name . ' ' . $c->last_name),
                $c->policy_no ?? 'N/A',
                $c->login_date?->format('Y-m-d') ?? 'N/A',
                $c->inforce_date?->format('Y-m-d') ?? 'N/A',
                $c->claim_type ?? 'N/A'
            ])
        );
        
        if ($this->option('dry-run')) {
            if ($this->option('smart')) {
                $this->showSmartDistribution($nullClients);
            } else {
                $this->info("\n📊 All {$nullClients->count()} clients would be set to: Login Client");
            }
            $this->warn('🔍 This is a dry run. No changes were made.');
            return 0;
        }
        
        if ($this->option('smart')) {
            return $this->smartAssignment($nullClients);
        } else {
            return $this->simpleAssignment($nullClients);
        }
    }
    
    /**
     * Simple assignment: Mark all as Login Client
     */
    private function simpleAssignment($clients)
    {
        $this->warn("\n⚠️  This will set ALL {$clients->count()} clients to 'Login Client' status");
        
        if (!$this->confirm('Continue?', true)) {
            $this->info('❌ Aborted by user.');
            return 1;
        }
        
        $updated = Client::whereNull('status')->update([
            'status' => 'Login Client',
            'updated_at' => now()
        ]);
        
        $this->info("✅ Successfully updated {$updated} clients to 'Login Client' status");
        
        $this->showStatusDistribution();
        
        return 0;
    }
    
    /**
     * Smart assignment: Based on data
     */
    private function smartAssignment($clients)
    {
        $this->warn("\n🧠 Smart assignment will assign status based on existing data:");
        $this->showSmartDistribution($clients);
        
        if (!$this->confirm('Continue with smart assignment?', true)) {
            $this->info('❌ Aborted by user.');
            return 1;
        }
        
        $counts = [
            'Claims' => 0,
            'Cancellation' => 0,
            'NPW Deferred' => 0,
            'Inforce' => 0,
            'Login Client' => 0,
        ];
        
        // 1. Clients with claim data → Claims
        $count = Client::whereNull('status')
            ->whereNotNull('claim_type')
            ->update(['status' => 'Claims', 'updated_at' => now()]);
        $counts['Claims'] = $count;
        
        // 2. Clients with cancellation data → Cancellation
        $count = Client::whereNull('status')
            ->whereNotNull('canc_date_sent')
            ->update(['status' => 'Cancellation', 'updated_at' => now()]);
        $counts['Cancellation'] = $count;
        
        // 3. Clients with NPW data → NPW Deferred
        $count = Client::whereNull('status')
            ->whereNotNull('npw_issue_date')
            ->update(['status' => 'NPW Deferred', 'updated_at' => now()]);
        $counts['NPW Deferred'] = $count;
        
        // 4. Clients with inforce data → Inforce
        $count = Client::whereNull('status')
            ->whereNotNull('inforce_date')
            ->update(['status' => 'Inforce', 'updated_at' => now()]);
        $counts['Inforce'] = $count;
        
        // 5. All remaining → Login Client
        $count = Client::whereNull('status')
            ->update(['status' => 'Login Client', 'updated_at' => now()]);
        $counts['Login Client'] = $count;
        
        $this->info("\n✅ Smart assignment complete:");
        $this->table(
            ['Status', 'Count'],
            collect($counts)->filter(fn($c) => $c > 0)->map(fn($count, $status) => [$status, $count])
        );
        
        $this->showStatusDistribution();
        
        return 0;
    }
    
    /**
     * Show what smart distribution would look like
     */
    private function showSmartDistribution($clients)
    {
        $distribution = [
            'Claims' => $clients->whereNotNull('claim_type')->count(),
            'Cancellation' => $clients->whereNull('claim_type')->whereNotNull('canc_date_sent')->count(),
            'NPW Deferred' => $clients->whereNull('claim_type')->whereNull('canc_date_sent')->whereNotNull('npw_issue_date')->count(),
            'Inforce' => $clients->whereNull('claim_type')->whereNull('canc_date_sent')->whereNull('npw_issue_date')->whereNotNull('inforce_date')->count(),
            'Login Client' => $clients->whereNull('claim_type')->whereNull('canc_date_sent')->whereNull('npw_issue_date')->whereNull('inforce_date')->count(),
        ];
        
        $this->info("\n📊 Distribution Preview:");
        $this->table(
            ['Status', 'Count', 'Logic'],
            [
                ['Claims', $distribution['Claims'], 'Has claim_type'],
                ['Cancellation', $distribution['Cancellation'], 'Has canc_date_sent'],
                ['NPW Deferred', $distribution['NPW Deferred'], 'Has npw_issue_date'],
                ['Inforce', $distribution['Inforce'], 'Has inforce_date'],
                ['Login Client', $distribution['Login Client'], 'Default (no stage data)'],
            ]
        );
    }
    
    /**
     * Show current status distribution
     */
    private function showStatusDistribution()
    {
        $distribution = Client::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
        
        $this->info("\n📊 Current Status Distribution:");
        $this->table(
            ['Status', 'Count'],
            $distribution->map(fn($d) => [$d->status ?? 'NULL', $d->count])
        );
        
        $nullCount = Client::whereNull('status')->count();
        if ($nullCount > 0) {
            $this->warn("⚠️  {$nullCount} clients still have NULL status");
        } else {
            $this->info('✅ All clients have a status value');
        }
    }
}
