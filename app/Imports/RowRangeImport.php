<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;

class RowRangeImport implements Import, ToCollection, WithHeadingRow, WithStartRow, WithLimit, WithChunkReading, WithCalculatedFormulas
{
    public array $data = [];

    private ?int $chunkOffset = null;

    public function __construct(
        private int $firstRow,
        private int $rowCount,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->data = $rows->map(fn (Collection $row) => $row->toArray())->all();
    }

    public function startRow(): int
    {
        return $this->firstRow;
    }

    public function limit(): int
    {
        return $this->chunkOffset === null
            ? $this->firstRow + $this->rowCount - 1
            : $this->rowCount;
    }

    public function chunkSize(): int
    {
        return $this->rowCount;
    }

    public function setChunkOffset(int $row): void
    {
        $this->chunkOffset = $row;
    }
}