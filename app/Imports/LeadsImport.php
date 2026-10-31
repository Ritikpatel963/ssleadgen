<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeadsImport implements SkipsEmptyRows, ToModel, WithHeadingRow
{
    public int $rowsImported = 0;

    public int $rowsSkipped = 0;

    /** @var array<int, array{row: string, reason: string}> */
    public array $skippedDetails = [];

    /** @var array<string, int> */
    protected array $sourceCache = [];

    /** @var array<string, int> */
    protected array $userCache = [];

    public function __construct()
    {
        $this->sourceCache = LeadSource::where('is_active', true)->pluck('id', 'name')->toArray();
        $this->userCache = User::pluck('id', 'name')->toArray();
    }

    public function model(array $row): Model|array|null
    {
        if (empty($row['first_name']) || (empty($row['email']) && empty($row['phone']))) {
            $this->rowsSkipped++;
            $this->skippedDetails[] = [
                'row' => json_encode($row),
                'reason' => 'First Name and either Email or Phone are required.',
            ];

            return null;
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'new')));
        $status = [
            'new lead' => 'new',
            'new' => 'new',
            'contacted' => 'contacted',
            'underwriting' => 'proposal',
            'proposal' => 'proposal',
            'policy issued' => 'won',
            'won' => 'won',
        ][$status] ?? 'new';

        $sourceName = trim((string) ($row['lead_source'] ?? $row['source'] ?? ''));
        $sourceId = $this->sourceCache[$sourceName] ?? null;
        $adviser = trim((string) ($row['adviser'] ?? ''));

        $this->rowsImported++;

        return new Lead([
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'] ?? '',
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? $row['mobile'] ?? null,
            'estimated_cover' => $row['estimated_cover'] ?? $row['cover_target'] ?? null,
            'lead_source_id' => $sourceId,
            'status' => $status,
            'user_id' => $this->userCache[$adviser] ?? null,
        ]);
    }
}
