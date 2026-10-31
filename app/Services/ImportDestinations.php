<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImportDestinations
{
    /**
     * Get the configuration for a specific destination.
     *
     * @param string $destination
     * @return array|null
     */
    public static function getConfig(string $destination): ?array
    {
        $configs = self::allConfigs();
        return $configs[$destination] ?? null;
    }

    /**
     * Get all available import configurations.
     *
     * @return array
     */
    public static function allConfigs(): array
    {
        $configs = [
            'leads' => [
                'name' => 'Lead',
                'permission' => 'leads',
                'model' => \App\Models\Lead::class,
                'fields' => [
                    'first_name' => ['label' => 'First Name', 'type' => 'string', 'required' => true],
                    'last_name' => ['label' => 'Last Name', 'type' => 'string', 'required' => false],
                    'email' => ['label' => 'Email', 'type' => 'string', 'required' => false],
                    'phone' => ['label' => 'Phone', 'type' => 'string', 'required' => false],
                    'lead_source' => ['label' => 'Lead Source', 'type' => 'string', 'required' => false],
                    'estimated_cover' => ['label' => 'Estimated Cover', 'type' => 'numeric', 'required' => false],
                    'status' => ['label' => 'Status', 'type' => 'string', 'required' => false],
                    'adviser' => ['label' => 'Adviser', 'type' => 'string', 'required' => false],
                    'notes' => ['label' => 'Notes', 'type' => 'string', 'required' => false],
                    'policy_no' => ['label' => 'Policy No', 'type' => 'string', 'required' => false],
                    'company' => ['label' => 'Insurance Company', 'type' => 'string', 'required' => false],
                    'login_date' => ['label' => 'Login Date', 'type' => 'date', 'required' => false],
                    'anp' => ['label' => 'ANP', 'type' => 'numeric', 'required' => false],
                    'dob' => ['label' => 'DOB', 'type' => 'date', 'required' => false],
                    'address' => ['label' => 'Address', 'type' => 'string', 'required' => false],
                    'suburb' => ['label' => 'Suburb', 'type' => 'string', 'required' => false],
                    'city' => ['label' => 'City', 'type' => 'string', 'required' => false],
                    'post_code' => ['label' => 'Post Code', 'type' => 'string', 'required' => false],
                    'not_counting' => ['label' => 'Not Counting', 'type' => 'boolean', 'required' => false],
                    'compliance_by' => ['label' => 'Compliance By', 'type' => 'string', 'required' => false],
                    'roa_due_date' => ['label' => 'RoA Due Date', 'type' => 'date', 'required' => false],
                    'status_compliance' => ['label' => 'Status Compliance', 'type' => 'string', 'required' => false],
                    'sent_to_client' => ['label' => 'Sent to Client', 'type' => 'string', 'required' => false],
                    'outcome' => ['label' => 'Outcome', 'type' => 'string', 'required' => false],
                ],
                'aliases' => [
                    'first_name' => ['firstname', 'given name'],
                    'last_name' => ['lastname', 'surname', 'family name'],
                    'email' => ['email address', 'email id'],
                    'phone' => ['mobile', 'mobile number', 'mobile no', 'phone number', 'cell'],
                    'lead_source' => ['source', 'source name', 'lead source name'],
                    'estimated_cover' => ['cover', 'cover target', 'estimated cover amount', 'cover amount'],
                    'adviser' => ['advisor', 'assigned adviser', 'assigned advisor'],
                    'policy_no' => ['policy number', 'policy #', 'policy no'],
                    'company' => ['insurer', 'insurance provider'],
                    'dob' => ['date of birth', 'birth date'],
                    'post_code' => ['postcode', 'postal code', 'zip'],
                    'roa_due_date' => ['roa due on', 'roa due'],
                ],
                'default_status' => 'new',
            ],
            'login_client' => [
                'name' => 'Login Client',
                'permission' => 'clients_login',
                'model' => \App\Models\Client::class,
                'fields' => [
                    'policy_no' => ['label' => 'Policy No', 'type' => 'string', 'required' => false],
                    'company' => ['label' => 'Insurance Company', 'type' => 'string', 'required' => false],
                    'first_name' => ['label' => 'First Name', 'type' => 'string', 'required' => true],
                    'last_name' => ['label' => 'Last Name', 'type' => 'string', 'required' => true],
                    'dob' => ['label' => 'DOB', 'type' => 'date', 'required' => false],
                    'phone' => ['label' => 'Mobile', 'type' => 'string', 'required' => true],
                    'email' => ['label' => 'Email', 'type' => 'string', 'required' => false],
                    'lead_source' => ['label' => 'Lead Source', 'type' => 'string', 'required' => false],
                    'address' => ['label' => 'Address', 'type' => 'string', 'required' => false],
                    'suburb' => ['label' => 'Suburb', 'type' => 'string', 'required' => false],
                    'city' => ['label' => 'City', 'type' => 'string', 'required' => false],
                    'post_code' => ['label' => 'Post Code', 'type' => 'string', 'required' => false],
                    'login_date' => ['label' => 'Login Date', 'type' => 'date', 'required' => false],
                    'anp' => ['label' => 'ANP', 'type' => 'numeric', 'required' => false],
                    'outcome' => ['label' => 'Outcome', 'type' => 'string', 'required' => false],
                    'notes' => ['label' => 'Notes', 'type' => 'string', 'required' => false],
                    'adviser' => ['label' => 'Adviser', 'type' => 'string', 'required' => false],
                    'not_counting' => ['label' => 'Not Counting', 'type' => 'boolean', 'required' => false],
                    'compliance_by' => ['label' => 'Compliance by', 'type' => 'string', 'required' => false],
                    'roa_due_date' => ['label' => 'RoA Due on', 'type' => 'date', 'required' => false],
                    'status' => ['label' => 'Status', 'type' => 'string', 'required' => false],
                    'sent_to_client' => ['label' => 'Sent to Client', 'type' => 'string', 'required' => false],
                    'status_compliance' => ['label' => 'Status Compliance', 'type' => 'string', 'required' => false],
                    'inforce_date' => ['label' => 'Inforce Date', 'type' => 'date', 'required' => false],
                    'inforce_request_type' => ['label' => 'Inforce Request Type', 'type' => 'string', 'required' => false],
                    'inforce_process_status' => ['label' => 'Inforce Process Status', 'type' => 'string', 'required' => false],
                    'inforce_process_by' => ['label' => 'Inforce Process By', 'type' => 'string', 'required' => false],
                    'inforce_outcome' => ['label' => 'Inforce Outcome', 'type' => 'string', 'required' => false],
                    'inforce_comments' => ['label' => 'Inforce Comments', 'type' => 'string', 'required' => false],
                    'inforce_finished_date' => ['label' => 'Inforce Finished Date', 'type' => 'date', 'required' => false],
                    'claim_type' => ['label' => 'Claim Type', 'type' => 'string', 'required' => false],
                    'claim_admin' => ['label' => 'Claim Admin', 'type' => 'string', 'required' => false],
                    'claim_processed_date' => ['label' => 'Claim Processed Date', 'type' => 'date', 'required' => false],
                    'claim_update_status' => ['label' => 'Claim Update Status', 'type' => 'string', 'required' => false],
                    'claim_approved_date' => ['label' => 'Claim Approved Date', 'type' => 'date', 'required' => false],
                    'claim_result' => ['label' => 'Claim Result', 'type' => 'string', 'required' => false],
                    'canc_date_sent' => ['label' => 'Canc Date Sent', 'type' => 'date', 'required' => false],
                    'canc_completed_date' => ['label' => 'Canc Completed Date', 'type' => 'date', 'required' => false],
                    'canc_outcome' => ['label' => 'Canc Outcome', 'type' => 'string', 'required' => false],
                    'canc_comments' => ['label' => 'Canc Comments', 'type' => 'string', 'required' => false],
                    'canc_admin' => ['label' => 'Canc Admin', 'type' => 'string', 'required' => false],
                    'npw_issue_date' => ['label' => 'Npw Issue Date', 'type' => 'date', 'required' => false],
                    'npw_premium' => ['label' => 'Npw Premium', 'type' => 'string', 'required' => false],
                    'npw_premium_mode' => ['label' => 'Npw Premium Mode', 'type' => 'string', 'required' => false],
                    'npw_admin' => ['label' => 'Npw Admin', 'type' => 'string', 'required' => false],
                    'npw_pending' => ['label' => 'Npw Pending', 'type' => 'string', 'required' => false],
                    'npw_comments' => ['label' => 'Npw Comments', 'type' => 'string', 'required' => false],
                ],
                'aliases' => [
                    'policy_no' => ['policy number', 'policy #', 'policy no.', 'policyno'],
                    'company' => ['insurer', 'insurance', 'provider'],
                    'first_name' => ['firstname', 'given name'],
                    'last_name' => ['lastname', 'surname', 'family name'],
                    'dob' => ['date of birth', 'birth date', 'birthdate'],
                    'phone' => ['mobile no', 'mobile number', 'phone number', 'cell'],
                    'email' => ['email address', 'email id'],
                    'lead_source' => ['source', 'source name', 'lead source name'],
                    'post_code' => ['postcode', 'postal code', 'zip'],
                    'login_date' => ['logindate', 'date of login'],
                    'roa_due_date' => ['roa due on', 'roa due'],
                ],
                'default_status' => 'Login Client'
            ],
            // More destinations can be added here
        ];

        foreach ($configs as $destination => $config) {
            $model = new $config['model']();
            $configuredFields = array_fill_keys(array_keys($config['fields']), true);
            $fieldAliases = [
                'lead_source_id' => 'lead_source',
                'user_id' => 'adviser',
            ];
            $missingFields = [];

            foreach ($model->getFillable() as $fillable) {
                $configuredField = $fieldAliases[$fillable] ?? $fillable;
                if (!isset($configuredFields[$configuredField])) {
                    $missingFields[] = $fillable;
                }
            }

            if ($missingFields) {
                Log::warning('Import destination is missing model fillable fields.', [
                    'destination' => $destination,
                    'model' => $config['model'],
                    'missing_fields' => $missingFields,
                ]);
            }
        }

        return $configs;
    }
}
