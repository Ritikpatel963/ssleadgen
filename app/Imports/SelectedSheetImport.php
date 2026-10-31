<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SelectedSheetImport implements Import, WithMultipleSheets, WithChunkReading
{
    public function __construct(
        private Import $sheetImport,
        private int $sheetIndex,
    ) {}

    public static function forFile(string $filePath, Import $sheetImport, int $sheetIndex): Import
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($extension, ['csv', 'txt'], true)) {
            return $sheetImport;
        }

        return new self($sheetImport, $sheetIndex);
    }

    public function sheets(): array
    {
        return [$this->sheetIndex => $this->sheetImport];
    }

    public function chunkSize(): int
    {
        return $this->sheetImport instanceof WithChunkReading
            ? $this->sheetImport->chunkSize()
            : 1000;
    }
}