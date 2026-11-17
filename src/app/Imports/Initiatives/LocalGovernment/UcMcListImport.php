<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\LocalGovernment\UcMcUnionCouncil;
use RuntimeException;

// The department's list of union councils for UC MC Office Service Delivery. Every row and every
// column is kept as typed, including "Missing"; "Null" is a blank. The list has no code that is
// unique to a UC, so nothing is merged: the whole list is replaced by the next one.
class UcMcListImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;
    use ReadsUcMcValues;

    private const MAX_ROWS = 100000;

    private const COLUMNS = [
        'list_sr' => ['sr no', 'sr', 's no', 'sno', 'serial no'],
        'division' => ['division', 'division name'],
        'district' => ['district', 'district name'],
        'tehsil' => ['tehsil', 'tehsil name'],
        'uc_type' => ['uc type', 'type'],
        'uc_no_and_name' => ['uc no and name', 'uc number and name', 'uc no name', 'uc name'],
        'uc_no' => ['uc no', 'uc number', 'uc'],
        'standard' => ['standard', 'standard name'],
        'municipal_committee' => ['name of municipal committee', 'municipal committee', 'name of mc', 'mc name'],
    ];

    // [column => the file's heading, the longest value the table keeps]
    private const TEXT_COLUMNS = [
        'division' => ['Division', 100],
        'district' => ['District', 100],
        'tehsil' => ['Tehsil', 100],
        'uc_type' => ['UC Type', 100],
        'uc_no_and_name' => ['UC No. and Name', 255],
        'uc_no' => ['UC No.', 100],
        'standard' => ['Standard', 255],
        'municipal_committee' => ['Name of Municipal Committee', 255],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['district', 'uc_no'], self::MAX_ROWS);

        $rows = [];
        $problems = [];

        foreach ($sheet['rows'] as $number => $row) {
            $values = ['list_sr' => $this->wholeNumber($row['list_sr'] ?? null, $number, 'Sr. No.', $problems)];

            foreach (self::TEXT_COLUMNS as $column => [$label, $max]) {
                $values[$column] = $this->fittingText($row[$column] ?? null, $max, $number, $label, $problems);
            }

            // A blank line holds nothing to keep.
            if (array_filter($values, fn ($value) => $value !== null) === []) {
                continue;
            }

            $rows[] = $values;
        }

        $this->refuseIfProblems($problems);

        if ($rows === []) {
            throw new RuntimeException('No UCs were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    // The new list replaces the old one. A failed upload never reaches this point.
    public function save(array $prepared, InitiativeFile $file): void
    {
        UcMcUnionCouncil::query()->delete();

        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            UcMcUnionCouncil::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs a "District" column and a "UC No." column.';
    }
}
