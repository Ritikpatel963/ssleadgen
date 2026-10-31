<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Insurer;
use App\Models\LookupValue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ClientsImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow
{
    protected $overwrite;

    protected $skipErrors;

    public $rowsImported = 0;

    public $rowsSkipped = 0;

    public $skippedDetails = []; // Array of ['row' => index/data, 'reason' => string]

    protected $usersCache = [];

    protected array $seenEmails = [];

    protected array $seenPhones = [];

    protected array $insurersCache = [];

    public function __construct($overwrite = true, $skipErrors = true)
    {
        $this->overwrite = filter_var($overwrite, FILTER_VALIDATE_BOOLEAN);
        $this->skipErrors = filter_var($skipErrors, FILTER_VALIDATE_BOOLEAN);

        // Preload users for quick adviser mapping
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
    }

    public function model(array $row): Model|array|null
    {
        // 1. Check for Raw Lead Format (e.g. Lincon Leads: name, phone, address, notes)
        if (isset($row['name']) && ! isset($row['first_name'])) {
            if (empty($row['name'])) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Missing "name" field in Raw Lead format',
                ];

                return null;
            }

            $parts = explode(' ', trim($row['name']), 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? null;
            $phone = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));

            if ($phone !== '') {
                if (isset($this->seenPhones[$phone]) || Client::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?", [$phone])->exists()) {
                    $this->rowsSkipped++;
                    $this->skippedDetails[] = [
                        'row' => json_encode($row),
                        'reason' => 'Duplicate mobile number already exists',
                    ];

                    return null;
                }

                $this->seenPhones[$phone] = true;
            }

            $clientData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone !== '' ? $phone : null,
                'address' => $row['address'] ?? null,
                'notes' => $row['notes'] ?? null,
                'status' => 'Login Client', // Default status for raw imports
                'sent_to_client' => 'Pending',
            ];

            $this->rowsImported++;

            return new Client($clientData);
        }

        // 2. Standard Login Client Format
        // Require a first name; policy numbers are optional for imported login clients.
        if (empty($row['first_name'])) {
            $this->rowsSkipped++;
            $this->skippedDetails[] = [
                'row' => json_encode($row),
                'reason' => 'Missing "first_name" field',
            ];

            return null;
        }

        $rawCompany = $this->firstNonEmpty($row, ['insurance_company', 'company', 'insurer']);
        $company = $this->resolveCompany($rawCompany);

        if ($rawCompany !== null && trim((string) $rawCompany) !== '' && $company === null) {
            $this->rowsSkipped++;
            $this->skippedDetails[] = [
                'row' => json_encode($row),
                'reason' => 'Insurance Company "'.trim((string) $rawCompany).'" does not match an active insurer.',
            ];

            return null;
        }

        $email = strtolower(trim((string) ($row['email'] ?? $row['email_id'] ?? $row['email_address'] ?? '')));
        $phone = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? $row['phone'] ?? ''));
        $existingClient = null;

        if ($this->overwrite && ! empty($row['policy_no'])) {
            $existingClient = Client::where('policy_no', $row['policy_no'])->first();
        }
        if ($this->overwrite && $phone !== '') {
            $existingPhoneClient = Client::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?", [$phone])->first();
            if ($existingClient && $existingPhoneClient && $existingClient->id !== $existingPhoneClient->id) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Mobile number belongs to a different existing client',
                ];

                return null;
            }
            $existingClient ??= $existingPhoneClient;
        }

        if ($email !== '') {
            if (isset($this->seenEmails[$email]) && (! $this->overwrite || ! $existingClient || ($this->seenEmails[$email] !== true && $this->seenEmails[$email] !== $existingClient->id))) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Duplicate email address in import file',
                ];

                return null;
            }

            $existingEmailClient = Client::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

            if ($existingEmailClient && (! $existingClient || $existingEmailClient->id !== $existingClient->id)) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Duplicate email address already exists',
                ];

                return null;
            }
            $this->seenEmails[$email] = $existingClient?->id ?? true;
        }

        if ($phone !== '') {
            if (isset($this->seenPhones[$phone]) && (! $this->overwrite || ! $existingClient || ($this->seenPhones[$phone] !== true && $this->seenPhones[$phone] !== $existingClient->id))) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Duplicate mobile number in import file',
                ];

                return null;
            }

            $existingPhoneClient = $existingClient ?: Client::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?", [$phone])->first();

            if ($existingPhoneClient && (! $existingClient || $existingPhoneClient->id !== $existingClient->id)) {
                $this->rowsSkipped++;
                $this->skippedDetails[] = [
                    'row' => json_encode($row),
                    'reason' => 'Duplicate mobile number already exists',
                ];

                return null;
            }

            $this->seenPhones[$phone] = $existingClient?->id ?? true;
        }

        // Map Adviser string to User ID
        $userId = null;
        if (! empty($row['adviser'])) {
            // Try exact match first
            if (isset($this->usersCache[$row['adviser']])) {
                $userId = $this->usersCache[$row['adviser']];
            } else {
                // Try case-insensitive fuzzy match
                $matchedUser = User::where('name', 'LIKE', '%'.$row['adviser'].'%')->first();
                if ($matchedUser) {
                    $userId = $matchedUser->id;
                    $this->usersCache[$row['adviser']] = $userId; // cache it
                }
            }
        }

        $clientData = [
            'policy_no' => $row['policy_no'],
            'company' => $company,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'] ?? null,
            'dob' => $this->parseDate($row['dob'] ?? null),
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'address' => $row['address'] ?? null,
            'suburb' => $row['suburb'] ?? null,
            'city' => $row['city'] ?? null,
            'post_code' => $row['post_code'] ?? null,
            'login_date' => $this->parseDate($row['login_date'] ?? null),
            'anp' => isset($row['anp']) ? floatval(preg_replace('/[^\d.]/', '', $row['anp'])) : 0,
            'outcome' => $row['outcome'] ?? null,
            'adviser' => $row['adviser'] ?? null,
            'user_id' => $userId,
            'not_counting' => strtolower($row['not_counting'] ?? '') === 'yes' || $row['not_counting'] == 1,
            'compliance_by' => $row['compliance_by'] ?? null,
            'roa_due_date' => $this->parseDate($row['roa_due_on'] ?? null),
            'status' => $this->normalizeStatus($row['status'] ?? null),
            'sent_to_client' => ! empty($row['sent_to_client']) ? $row['sent_to_client'] : 'Pending',
        ];

        // Check if updating existing record
        if ($existingClient) {
            $existingClient->update($clientData);
            $this->rowsImported++;

            return null; // Return null because we manually updated it
        }

        $this->rowsImported++;

        return new Client($clientData);
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function firstNonEmpty(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function resolveCompany(mixed $value): ?string
    {
        $normalized = $this->normalizeCompanyName($value);

        if ($normalized === '') {
            return null;
        }

        if (isset($this->insurersCache[$normalized])) {
            return $this->insurersCache[$normalized];
        }

        $prefix = substr($normalized, 0, min(3, strlen($normalized)));
        if (strlen($prefix) < 3) {
            return null;
        }

        $matches = [];
        foreach ($this->insurersCache as $insurerNormalized => $insurerName) {
            if (! str_starts_with($insurerNormalized, $prefix)) {
                continue;
            }

            similar_text($normalized, $insurerNormalized, $similarity);
            $distance = levenshtein($normalized, $insurerNormalized);
            $allowedDistance = max(1, (int) floor(strlen($insurerNormalized) * 0.25));

            if ($similarity >= 70 || $distance <= $allowedDistance) {
                $matches[] = [
                    'name' => $insurerName,
                    'similarity' => $similarity,
                    'distance' => $distance,
                ];
            }
        }

        usort($matches, function (array $left, array $right): int {
            if ($left['similarity'] === $right['similarity']) {
                return $left['distance'] <=> $right['distance'];
            }

            return $right['similarity'] <=> $left['similarity'];
        });

        if (count($matches) === 1) {
            return $matches[0]['name'];
        }

        if (count($matches) > 1) {
            $best = $matches[0];
            $second = $matches[1];

            if ($best['similarity'] >= 80 && ($best['similarity'] - $second['similarity']) >= 8) {
                return $best['name'];
            }
        }

        return null;
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

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->rowsSkipped++;
            $this->skippedDetails[] = [
                'row' => 'Row '.$failure->row(),
                'reason' => implode(', ', $failure->errors()),
            ];
        }
    }
}
