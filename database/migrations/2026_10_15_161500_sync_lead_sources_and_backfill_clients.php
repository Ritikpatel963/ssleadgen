<?php

use App\Models\Client;
use App\Models\LeadSource;
use App\Models\LookupValue;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sync all active LookupValue lead sources into lead_sources table
        $lookupSources = LookupValue::where('group', 'lead_source')
            ->where('is_active', true)
            ->get();

        foreach ($lookupSources as $lookup) {
            LeadSource::firstOrCreate(
                ['name' => trim($lookup->name)],
                ['is_active' => true]
            );
        }

        // 2. Resolve default/fallback lead source
        $defaultSource = LeadSource::where('name', 'Lincon Leads')->first()
            ?: LeadSource::where('name', 'Others')->first()
            ?: LeadSource::first();

        // 3. Backfill any clients that currently have a null lead_source_id
        if ($defaultSource) {
            Client::whereNull('lead_source_id')->update([
                'lead_source_id' => $defaultSource->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
