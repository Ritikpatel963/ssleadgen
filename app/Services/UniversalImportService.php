<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Insurer;
use App\Models\Lead;
use App\Models\LookupValue;
use App\Models\LeadSource;
use App\Models\User;
use App\Imports\SelectedSheetImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class UniversalImportService
{
    protected array $usersCache = [];
    protected array $insurersCache = [];
    protected array $leadSourcesCache = [];
    protected array $seenEmails = [];
    protected array $seenPhones = [];
    protected bool $leadLookupCachesLoaded = false;
    
    protected array $dbPolicies = [];
    protected array $dbEmails = [];
    protected array $dbPhones = [];
    protected array $dbLeadPhones = [];
    protected array $seenLeadPhones = [];
    protected bool $lookupCachesLoaded = false;

    public function __construct()
    {
    }

    private function loadLookupCaches(): void
    {
        if ($this->lookupCachesLoaded) {
            return;
        }

        $this->usersCache = User::pluck('id', 'name')->toArray();
        $insurerNames = LookupValue::where('group', 'insurance_company')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name');
        if ($insurerNames->isEmpty()) {
            $insurerNames = Insurer::where('is_active', true)->pluck('name');
        }
        $this->insurersCache = $insurerNames
            ->filter()
            ->mapWithKeys(fn ($name) => [$this->normalizeCompanyName($name) => $name])
            ->all();
        $this->leadSourcesCache = LeadSource::where('is_active', true)
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
            ->all();
        $this->lookupCachesLoaded = true;
    }

    /** Load existing records that can conflict with the current spreadsheet chunk. */
    public function preloadExistingClients(array $rows, array $mapping): void
    {
        $this->dbPolicies = [];
        $this->dbEmails = [];
        $this->dbPhones = [];
        $this->seenEmails = [];
        $this->seenPhones = [];

        $policies = [];
        $emails = [];
        $phones = [];

        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = $this->mapRawData($row, $mapping);
            if (!empty($data['policy_no'])) {
                $policies[] = (string) $data['policy_no'];
            }
            if (!empty($data['email'])) {
                $emails[] = strtolower(trim((string) $data['email']));
            }
            if (!empty($data['phone'])) {
                $phones[] = preg_replace('/\D+/', '', (string) $data['phone']);
            }
        }

        $policies = array_values(array_unique(array_filter($policies)));
        $emails = array_values(array_unique(array_filter($emails)));
        $phones = array_values(array_unique(array_filter($phones)));

        if (!$policies && !$emails && !$phones) {
            return;
        }

        $phoneExpression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
        $clients = Client::query()
            ->select(['id', 'policy_no', 'email', 'phone'])
            ->where(function ($query) use ($policies, $emails, $phones, $phoneExpression) {
                if ($policies) {
                    $query->orWhereIn('policy_no', $policies);
                }
                if ($emails) {
                    $query->orWhereIn(DB::raw('LOWER(TRIM(email))'), $emails);
                }
                if ($phones) {
                    $query->orWhereIn(DB::raw($phoneExpression), $phones);
                }
            })
            ->get();

        foreach ($clients as $client) {
            $this->rememberClient($client->toArray());
        }
    }

    /** Load existing leads and earlier file phones for this import chunk. */
    public function preloadExistingLeads(array $rows, array $mapping, array $seenPhones = []): void
    {
        $this->dbLeadPhones = [];
        $this->seenLeadPhones = [];

        foreach ($seenPhones as $phone) {
            $normalizedPhone = preg_replace('/\D+/', '', (string) $phone);
            if ($normalizedPhone !== '') {
                $this->seenLeadPhones[$normalizedPhone] = true;
            }
        }

        $phones = [];
        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = $this->mapRawData($row, $mapping);
            $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
            if ($phone !== '') {
                $phones[] = $phone;
            }
        }

        $phones = array_values(array_unique($phones));
        if (!$phones) {
            return;
        }

        $phoneExpression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
        $leads = Lead::query()
            ->select(['id', 'phone'])
            ->whereNotNull('phone')
            ->whereIn(DB::raw($phoneExpression), $phones)
            ->get();

        foreach ($leads as $lead) {
            $phone = preg_replace('/\D+/', '', (string) $lead->phone);
            if ($phone !== '') {
                $this->dbLeadPhones[$phone] = true;
            }
        }
    }

    public function getSeenLeadPhones(): array
    {
        return array_keys($this->seenLeadPhones);
    }

    public function rememberClient(array $client): void
    {
        $id = $client['id'] ?? null;
        if (!$id) {
            return;
        }
        if (!empty($client['policy_no'])) {
            $this->dbPolicies[$client['policy_no']] = $id;
        }
        if (!empty($client['email'])) {
            $this->dbEmails[strtolower(trim($client['email']))] = $id;
        }
        if (!empty($client['phone'])) {
            $phone = preg_replace('/\D+/', '', $client['phone']);
            if ($phone !== '') {
                $this->dbPhones[$phone] = $id;
            }
        }
    }

    public function forgetClient(int $clientId): void
    {
        $this->dbPolicies = array_filter($this->dbPolicies, fn ($id) => (int) $id !== $clientId);
        $this->dbEmails = array_filter($this->dbEmails, fn ($id) => (int) $id !== $clientId);
        $this->dbPhones = array_filter($this->dbPhones, fn ($id) => (int) $id !== $clientId);
    }

    public function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            return false;
        }

        return true;
    }


    /**
     * Read the headers of the uploaded file.
     */
    public function getSheets(string $filePath): array
    {
        try {
            $absolutePath = Storage::path($filePath);
            $reader = IOFactory::createReaderForFile($absolutePath);
            $names = $reader->listWorksheetNames($absolutePath);

            return array_map(
                fn (string $name, int $index): array => ['index' => $index, 'name' => $name],
                $names,
                array_keys($names)
            );
        } catch (\Throwable $e) {
            Log::error('Error reading spreadsheet sheets: ' . $e->getMessage());
            return [];
        }
    }

    public function getNonEmptyRowNumbers(string $filePath, int $sheetIndex): array
    {
        $cacheKey = 'import-preview-row-index:' . hash(
            'sha256',
            $filePath . '|' . Storage::lastModified($filePath) . '|' . $sheetIndex
        );

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filePath, $sheetIndex): array {
            $spreadsheet = null;
            try {
                $absolutePath = Storage::path($filePath);
                $reader = IOFactory::createReaderForFile($absolutePath);
                $sheetNames = $reader->listWorksheetNames($absolutePath);
                $sheetName = $sheetNames[$sheetIndex] ?? null;
                if ($sheetName === null) {
                    return [];
                }

                $reader->setReadDataOnly(true);
                $reader->setLoadSheetsOnly([$sheetName]);
                $spreadsheet = $reader->load($absolutePath);
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                if ($worksheet === null) {
                    return [];
                }

                $nonEmptyRows = [];
                foreach ($worksheet->getCellCollection()->getCoordinates() as $coordinate) {
                    $value = $worksheet->getCell($coordinate)->getValue();
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        continue;
                    }

                    $rowNumber = (int) preg_replace('/\D+/', '', $coordinate);
                    if ($rowNumber > 1) {
                        $nonEmptyRows[$rowNumber] = true;
                    }
                }

                $rowNumbers = array_map('intval', array_keys($nonEmptyRows));
                sort($rowNumbers, SORT_NUMERIC);

                return $rowNumbers;
            } catch (\Throwable $e) {
                Log::warning('Error indexing non-empty spreadsheet rows: ' . $e->getMessage());
                return [];
            } finally {
                $spreadsheet?->disconnectWorksheets();
            }
        });
    }

    public function getSheetRowCount(string $filePath, int $sheetIndex): int
    {
        try {
            return count($this->getNonEmptyRowNumbers($filePath, $sheetIndex));
        } catch (\Throwable $e) {
            Log::warning('Error counting non-empty spreadsheet rows: ' . $e->getMessage());
            return 0;
        }
    }

    public function getHeaders(string $filePath, int $sheetIndex = 0): array
    {
        try {
            $headings = Excel::toArray(
                SelectedSheetImport::forFile($filePath, new HeadingRowImport(), $sheetIndex),
                Storage::path($filePath)
            );
            $selectedSheet = array_values($headings)[0] ?? [];
            if (empty($selectedSheet) || empty($selectedSheet[0])) {
                return [];
            }

            return array_values(array_filter($selectedSheet[0], fn ($header) => $header !== null && $header !== ''));
        } catch (\Throwable $e) {
            Log::error('Error reading excel headers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate auto-mapping suggestions based on file headers and destination config.
     */
    public function suggestMapping(array $headers, array $config): array
    {
        $mapping = [];
        $dbFields = $config['fields'];
        $aliases = $config['aliases'] ?? [];

        foreach ($headers as $index => $header) {
            $headerText = (string) $header;
            $normalizedHeader = $this->normalizeString($headerText);
            
            $mappedField = null;

            // Check exact or normalized match on keys or labels
            foreach ($dbFields as $fieldKey => $fieldConfig) {
                if ($this->normalizeString($fieldKey) === $normalizedHeader || 
                    $this->normalizeString($fieldConfig['label']) === $normalizedHeader) {
                    $mappedField = $fieldKey;
                    break;
                }
            }

            // Check aliases
            if (!$mappedField) {
                foreach ($aliases as $fieldKey => $fieldAliases) {
                    foreach ($fieldAliases as $alias) {
                        if ($this->normalizeString($alias) === $normalizedHeader) {
                            $mappedField = $fieldKey;
                            break 2;
                        }
                    }
                }
            }

            $mapping[$index] = [
                'original_header' => $headerText,
                'suggested_field' => $mappedField,
            ];
        }

        return $mapping;
    }

    /**
     * Process mapped data array to prepare for DB insertion, apply validations.
     */
    public function processRow(array $row, array $config, array $mapping, bool $overwrite = false, ?string $defaultLeadSource = null): array
    {
        $processed = [];
        $errors = [];
        
        $rawData = $this->mapRawData($row, $mapping);
        if (filled($defaultLeadSource) && (empty($rawData['lead_source']) || $config['name'] === 'Lead')) {
            $rawData['lead_source'] = $defaultLeadSource;
            $rawData['__lead_source_from_dropdown'] = true;
        }

        if ($config['name'] === 'Lead') {
            if (empty($rawData['email']) && empty($rawData['phone'])) {
                return ['success' => false, 'errors' => ['Email or Phone is required.'], 'raw' => $rawData];
            }

            $phone = preg_replace('/\D+/', '', (string) ($rawData['phone'] ?? ''));
            if ($phone !== '') {
                if (isset($this->dbLeadPhones[$phone]) || isset($this->seenLeadPhones[$phone])) {
                    return ['success' => false, 'errors' => ['Duplicate mobile number: lead already exists'], 'raw' => $rawData];
                }
                $this->seenLeadPhones[$phone] = true;
            }
        }

        // Validate required fields
        foreach ($config['fields'] as $fieldKey => $fieldConfig) {
            if ($fieldConfig['required'] && empty($rawData[$fieldKey])) {
                $errors[] = "Missing required field: " . $fieldConfig['label'];
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'raw' => $rawData];
        }

        if ($config['name'] === 'Lead') {
            $processed = $this->processLead($rawData, $config);
            if (isset($processed['error'])) {
                return ['success' => false, 'errors' => [$processed['error']], 'raw' => $rawData];
            }
        // Specific processing for Login Clients (Reusing old logic)
        } elseif ($config['name'] === 'Login Client') {
            $processed = $this->processLoginClient($rawData);
            if (isset($processed['error'])) {
                 return ['success' => false, 'errors' => [$processed['error']], 'raw' => $rawData];
            }
        } else {
            // Generic fallback processing (can be expanded)
            $processed = $rawData;
            $processed['status'] = $config['default_status'] ?? 'Active';
        }

        return ['success' => true, 'data' => $processed, 'raw' => $rawData];
    }

    private function processLead(array $rawData, array $config): array
    {
                $this->loadLookupCaches();
        $rawCompany = $rawData['company'] ?? null;
        $company = $this->resolveCompany($rawCompany);
        if ($rawCompany && trim((string) $rawCompany) !== '' && !$company) {
            return ['error' => 'Insurance Company "' . trim((string) $rawCompany) . '" does not match an active insurer.'];
        }
        if (!$this->leadLookupCachesLoaded) {
            $this->usersCache = User::pluck('id', 'name')->toArray();
            $this->leadSourcesCache = LeadSource::where('is_active', true)
                ->pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [strtolower(trim($name)) => $id])
                ->all();
            $this->leadLookupCachesLoaded = true;
        }

        $sourceName = strtolower(trim((string) ($rawData['lead_source'] ?? '')));
        $sourceId = $this->leadSourcesCache[$sourceName] ?? null;
        if (!$sourceId && $sourceName !== '') {
            $source = LeadSource::firstOrCreate(['name' => trim((string) $rawData['lead_source'])], ['is_active' => true]);
            $sourceId = $this->leadSourcesCache[$sourceName] = $source->id;
        }
        $adviserName = trim((string) ($rawData['adviser'] ?? ''));
        $cover = trim((string) ($rawData['estimated_cover'] ?? ''));
        $cover = $cover === '' ? null : preg_replace('/[^\d.\-]/', '', $cover);
        $status = strtolower(trim((string) ($rawData['status'] ?? $config['default_status'])));
        $status = [
            'new lead' => 'new',
            'new' => 'new',
            'contacted' => 'contacted',
            'underwriting' => 'proposal',
            'proposal' => 'proposal',
            'policy issued' => 'won',
            'won' => 'won',
        ][$status] ?? $config['default_status'];
        $anp = trim((string) ($rawData['anp'] ?? ''));

        return [
            'first_name' => trim((string) $rawData['first_name']),
            'last_name' => trim((string) ($rawData['last_name'] ?? '')),
            'email' => filled($rawData['email'] ?? null) ? strtolower(trim((string) $rawData['email'])) : null,
            'phone' => filled($rawData['phone'] ?? null) ? trim((string) $rawData['phone']) : null,
            'estimated_cover' => is_numeric($cover) ? $cover : null,
            'notes' => $rawData['notes'] ?? null,
            'policy_no' => $rawData['policy_no'] ?? null,
            'company' => $company ?: null,
            'login_date' => $this->parseDate($rawData['login_date'] ?? null),
            'anp' => $anp === '' ? null : preg_replace('/[^\d.\-]/', '', $anp),
            'dob' => $this->parseDate($rawData['dob'] ?? null),
            'address' => $rawData['address'] ?? null,
            'suburb' => $rawData['suburb'] ?? null,
            'city' => $rawData['city'] ?? null,
            'post_code' => $rawData['post_code'] ?? null,
            'not_counting' => $rawData['not_counting'] ?? null,
            'compliance_by' => $rawData['compliance_by'] ?? null,
            'roa_due_date' => $this->parseDate($rawData['roa_due_date'] ?? null),
            'status_compliance' => $rawData['status_compliance'] ?? null,
            'sent_to_client' => $rawData['sent_to_client'] ?? null,
            'outcome' => $rawData['outcome'] ?? null,
            'lead_source_id' => $sourceId,
            'status' => $status,
            'adviser' => filled($rawData['adviser'] ?? null) ? trim((string) $rawData['adviser']) : null,
            'user_id' => $this->usersCache[$adviserName] ?? null,
        ];
    }

    private function mapRawData(array $row, array $mapping): array
    {
        $rawData = [];
        foreach ($mapping as $mapInfo) {
            if (!is_array($mapInfo)) {
                continue;
            }
            $field = $mapInfo['db_field'] ?? null;
            $header = $mapInfo['original_header'] ?? null;
            if ($field && $field !== '--ignore--' && is_string($header)) {
                $rawData[$field] = $row[$header] ?? null;
            }
        }

        return $rawData;
    }

    private function processLoginClient(array $rawData): array
    {
        $this->loadLookupCaches();
        $rawCompany = $rawData['company'] ?? null;
        $company = $this->resolveCompany($rawCompany);

        if ($rawCompany && trim((string)$rawCompany) !== '' && !$company) {
            return ['error' => 'Insurance Company "' . trim((string)$rawCompany) . '" does not match an active insurer.'];
        }

        $email = strtolower(trim((string)($rawData['email'] ?? '')));
        $phone = preg_replace('/\D+/', '', (string)($rawData['phone'] ?? ''));

        $existingClientId = null;
        if (!empty($rawData['policy_no'])) {
            $existingClientId = $this->dbPolicies[$rawData['policy_no']] ?? null;
        }

        $existingPhoneId = $phone !== '' ? ($this->dbPhones[$phone] ?? null) : null;
        $seenPhoneIdentity = $phone !== '' ? ($this->seenPhones[$phone] ?? null) : null;
        $duplicatePhoneInFile = $seenPhoneIdentity !== null;

        if ($duplicatePhoneInFile && !$existingPhoneId) {
            if (str_starts_with((string) $seenPhoneIdentity, 'client:')) {
                $existingClientId = (int) substr((string) $seenPhoneIdentity, strlen('client:'));
            } elseif (str_starts_with((string) $seenPhoneIdentity, 'file:')) {
                $existingClientId = null;
            }
        }

        if ($existingPhoneId) {
            $existingClientId = $existingPhoneId;
        }

        $recordIdentity = $existingClientId
            ? 'client:' . $existingClientId
            : ($phone !== '' ? 'file:' . $phone : null);

        if ($email !== '') {
            if (isset($this->seenEmails[$email]) && (!$recordIdentity || $this->seenEmails[$email] !== $recordIdentity)) {
                return ['error' => 'Duplicate email address in import file'];
            }
            
            $existingEmailId = $this->dbEmails[$email] ?? null;
            if ($existingEmailId && (!$existingClientId || $existingEmailId !== $existingClientId)) {
                return ['error' => 'Duplicate email address already exists'];
            }
            $this->seenEmails[$email] = $recordIdentity ?? 'email:' . $email;
        }

        if ($phone !== '') {
            $this->seenPhones[$phone] = $recordIdentity ?? 'file:' . $phone;
        }

        $userId = null;
        if (!empty($rawData['adviser'])) {
            if (isset($this->usersCache[$rawData['adviser']])) {
                $userId = $this->usersCache[$rawData['adviser']];
            } else {
                $adviserSearch = strtolower((string) $rawData['adviser']);
                foreach ($this->usersCache as $name => $id) {
                    if (str_contains(strtolower($name), $adviserSearch)) {
                        $userId = $id;
                        $this->usersCache[$rawData['adviser']] = $id;
                        break;
                    }
                }
            }
        }

        $sourceName = strtolower(trim((string) ($rawData['lead_source'] ?? '')));
        $sourceId = $this->leadSourcesCache[$sourceName] ?? null;
        if (!$sourceId && $sourceName !== '') {
            $source = LeadSource::firstOrCreate(['name' => trim((string) $rawData['lead_source'])], ['is_active' => true]);
            $sourceId = $this->leadSourcesCache[$sourceName] = $source->id;
        }

        return [
            '__duplicate_phone_in_file' => $duplicatePhoneInFile,
            'existing_client_id' => $existingClientId,
            'lead_source_id' => $sourceId,
            'policy_no' => $rawData['policy_no'] ?? null,
            'company' => $company,
            'first_name' => $rawData['first_name'] ?? null,
            'last_name' => $rawData['last_name'] ?? null,
            'dob' => $this->parseDate($rawData['dob'] ?? null),
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'address' => $rawData['address'] ?? null,
            'suburb' => $rawData['suburb'] ?? null,
            'city' => $rawData['city'] ?? null,
            'post_code' => $rawData['post_code'] ?? null,
            'login_date' => $this->parseDate($rawData['login_date'] ?? null),
            'anp' => isset($rawData['anp']) ? floatval(preg_replace('/[^\d.]/', '', $rawData['anp'])) : 0,
            'outcome' => $rawData['outcome'] ?? null,
            'notes' => $rawData['notes'] ?? null,
            'adviser' => $rawData['adviser'] ?? null,
            'user_id' => $userId,
            'not_counting' => strtolower($rawData['not_counting'] ?? '') === 'yes' || ($rawData['not_counting'] ?? 0) == 1,
            'compliance_by' => $rawData['compliance_by'] ?? null,
            'roa_due_date' => $this->parseDate($rawData['roa_due_date'] ?? null),
            'status_compliance' => $rawData['status_compliance'] ?? null,
            'status' => $this->normalizeStatus($rawData['status'] ?? null),
            'sent_to_client' => !empty($rawData['sent_to_client']) ? $rawData['sent_to_client'] : 'Pending',
            'inforce_date' => $this->parseDate($rawData['inforce_date'] ?? null),
            'inforce_request_type' => $rawData['inforce_request_type'] ?? null,
            'inforce_process_status' => $rawData['inforce_process_status'] ?? null,
            'inforce_process_by' => $rawData['inforce_process_by'] ?? null,
            'inforce_outcome' => $rawData['inforce_outcome'] ?? null,
            'inforce_comments' => $rawData['inforce_comments'] ?? null,
            'inforce_finished_date' => $this->parseDate($rawData['inforce_finished_date'] ?? null),
            'claim_type' => $rawData['claim_type'] ?? null,
            'claim_admin' => $rawData['claim_admin'] ?? null,
            'claim_processed_date' => $this->parseDate($rawData['claim_processed_date'] ?? null),
            'claim_update_status' => $rawData['claim_update_status'] ?? null,
            'claim_approved_date' => $this->parseDate($rawData['claim_approved_date'] ?? null),
            'claim_result' => $rawData['claim_result'] ?? null,
            'canc_date_sent' => $this->parseDate($rawData['canc_date_sent'] ?? null),
            'canc_completed_date' => $this->parseDate($rawData['canc_completed_date'] ?? null),
            'canc_outcome' => $rawData['canc_outcome'] ?? null,
            'canc_comments' => $rawData['canc_comments'] ?? null,
            'canc_admin' => $rawData['canc_admin'] ?? null,
            'npw_issue_date' => $this->parseDate($rawData['npw_issue_date'] ?? null),
            'npw_premium' => $rawData['npw_premium'] ?? null,
            'npw_premium_mode' => $rawData['npw_premium_mode'] ?? null,
            'npw_admin' => $rawData['npw_admin'] ?? null,
            'npw_pending' => $rawData['npw_pending'] ?? null,
            'npw_comments' => $rawData['npw_comments'] ?? null,
        ];
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeString(string $string): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '', $string)));
    }

    private function normalizeCompanyName(mixed $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(trim((string) $value)))) ?? '';
    }

    private function normalizeStatus(?string $status): string
    {
        $statusMap = [
            'inforce' => 'Inforce',
            'inactive' => 'Inactive',
            'cancellation' => 'Cancellation',
            'npw deferred' => 'NPW Deferred',
            'npw-deferred' => 'NPW Deferred',
            'login client' => 'Login Client',
            'login' => 'Login Client',
            'claims' => 'Claims',
            'claim' => 'Claims',
            'active' => 'Login Client',
            'pending' => 'Login Client',
        ];

        return $statusMap[strtolower(trim((string) $status))] ?? 'Login Client';
    }

    private function resolveCompany(mixed $value): ?string
    {
        $normalized = $this->normalizeCompanyName($value);
        if ($normalized === '') return null;
        if (isset($this->insurersCache[$normalized])) return $this->insurersCache[$normalized];

        $prefix = substr($normalized, 0, min(3, strlen($normalized)));
        if (strlen($prefix) < 3) return null;

        $matches = [];
        foreach ($this->insurersCache as $insurerNormalized => $insurerName) {
            if (!str_starts_with($insurerNormalized, $prefix)) continue;
            similar_text($normalized, $insurerNormalized, $similarity);
            $distance = levenshtein($normalized, $insurerNormalized);
            $allowedDistance = max(1, (int) floor(strlen($insurerNormalized) * 0.25));

            if ($similarity >= 70 || $distance <= $allowedDistance) {
                $matches[] = ['name' => $insurerName, 'similarity' => $similarity, 'distance' => $distance];
            }
        }

        usort($matches, function (array $left, array $right): int {
            if ($left['similarity'] === $right['similarity']) return $left['distance'] <=> $right['distance'];
            return $right['similarity'] <=> $left['similarity'];
        });

        if (count($matches) === 1) return $matches[0]['name'];
        if (count($matches) > 1) {
            $best = $matches[0];
            $second = $matches[1];
            if ($best['similarity'] >= 80 && ($best['similarity'] - $second['similarity']) >= 8) {
                return $best['name'];
            }
        }
        return null;
    }
}
