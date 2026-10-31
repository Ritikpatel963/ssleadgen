<?php

namespace Database\Seeders;

use App\Models\LookupValue;
use Illuminate\Database\Seeder;

class LookupValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            'insurance_company' => [
                'AIA Health',
                'AIA Living',
                'AIA Life',
                'AIA New Zealand',
                'Asteron Life',
                'Chubb Life',
                'Fidelity Life',
                'NIB',
                'Partners Life',
            ],
            'lead_category' => [
                'Website',
                'Referral',
                'Existing Client',
                'Social Media',
                'Walk-in',
            ],
            'lead_source' => ['Lincon Leads', 'Door to Door'],
             'pipeline_stage' => [
                'New',
                'Contacted',
                'Proposal',
                'Won / Converted',
            ],
            'not_counting' => ['No', 'Yes'],
            'sent_to_client' => ['Pending', 'Yes', 'No'],
            'compliance_status' => ['Sent to Compliance', 'In Review', 'Approved'],
            'request_type' => [
                'LOA',
                'MOT',
                'Address update',
                'Put the premium on hold',
                'LOA / Change of Adviser',
                'Correctify Name',
                'Name Correction',
                'Update Payment Details - DD',
                'Other',
            ],
            'process_status' => ['Login', 'In Processing', 'Pending Information', 'Completed'],
            'claim_update_status' => ['Under Assessment', 'Medical Review', 'Document Verification', 'Approved', 'Declined'],
            'premium_mode' => ['Weekly', 'Fortnightly', 'Monthly', 'Annually'],
            'npw_pending' => ['Yes', 'No'],
        ];

        foreach ($groups as $group => $values) {
            foreach ($values as $index => $value) {
                LookupValue::query()->updateOrCreate(
                    ['group' => $group, 'name' => $value],
                    ['sort_order' => $index + 1, 'is_active' => true]
                );
            }
        }
    }
}
