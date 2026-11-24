<?php

namespace App\Imports\Initiatives\HigherEducation;

use App\Imports\CallCenter\ImportFailed;
use App\Imports\CallCenter\RowImport;
use App\Imports\CallCenter\UploadProgress;
use App\Models\InitiativeFile;
use App\Models\Initiatives\HigherEducation\HonhaarInstitution;
use Illuminate\Support\Facades\DB;

// CM Punjab Honhaar Scholarship: our calls file, the call center's feedback calls. Every row is added to the
// rows of the earlier files and kept as typed. When the file also has a "Universities" sheet, its single
// name for each university replaces the earlier one.
class HonhaarCallImport extends RowImport
{
    protected function table(): string
    {
        return 'hed_honhaar_scholarship';
    }

    protected function keyColumn(): string
    {
        return 'source_sr';
    }

    protected function columns(): array
    {
        return [
            'source_sr' => ['headings' => ['Sr. No', 'Sr No', 'Sr #', 'S. No', 'Compiled Sr. No'], 'type' => 'int', 'required' => true],
            'call_date' => ['headings' => ['Call Date'], 'type' => 'date', 'required' => true],
            'applicant_name' => ['headings' => ['Name of Applicant'], 'type' => 'text'],
            'cnic' => ['headings' => ['CNIC'], 'type' => 'text', 'max' => 50, 'required' => true],
            'contact_no' => ['headings' => ['Contact No'], 'type' => 'text', 'max' => 50, 'required' => true],
            'alt_contact_no' => ['headings' => ['Alt Contact No'], 'type' => 'text', 'max' => 50],
            'contact_valid' => ['headings' => ['Contact Valid?'], 'type' => 'text', 'max' => 50],
            'degree_program' => ['headings' => ['Degree Program'], 'type' => 'text'],
            'amount_fees' => ['headings' => ['Amount (Fees)'], 'type' => 'int'],
            'institute_name' => ['headings' => ['Institute Name'], 'type' => 'text'],
            'division' => ['headings' => ['Division'], 'type' => 'text', 'max' => 100],
            'district' => ['headings' => ['District'], 'type' => 'text', 'max' => 100],
            'call_status' => ['headings' => ['Call Status'], 'type' => 'text', 'max' => 100, 'required' => true],
            'first_cheque_received' => ['headings' => ['First Cheque/Installment Received?'], 'type' => 'text', 'max' => 100, 'required' => true],
            'receiving_scholarship' => ['headings' => ['Receiving Scholarship (under scheme)?'], 'type' => 'text', 'max' => 100, 'required' => true],
            'bribe_paid' => ['headings' => ['Bribe Paid to Dept/Institution?'], 'type' => 'text', 'max' => 100, 'required' => true],
            'year_of_degree' => ['headings' => ['Year of Degree'], 'type' => 'text', 'max' => 100, 'required' => true],
            'satisfied' => ['headings' => ['Satisfied with Programme?'], 'type' => 'text', 'max' => 100, 'required' => true],
            'remarks' => ['headings' => ['Remarks'], 'type' => 'text', 'max' => 65000, 'required' => true],
            'any_other_issue' => ['headings' => ['Any Other Issue'], 'type' => 'text', 'max' => 16000000],
        ];
    }

    // Every value but Sr. No, so a row repeating an earlier one is found (see HonhaarLinker).
    protected function extra(array $row): array
    {
        unset($row['source_sr']);
        ksort($row);

        return ['content_hash' => sha1(json_encode($row, JSON_UNESCAPED_UNICODE))];
    }

    public function import(string $path, string $extension, InitiativeFile $file, UploadProgress $progress): array
    {
        $result = parent::import($path, $extension, $file, $progress);

        return $result + ['universities' => $extension === 'xlsx' ? $this->universities($path, $file) : null];
    }

    // The "Universities" sheet: one row per university or college, with its type and the spellings of it in the
    // department's list, e.g. "University of the Punjab [2348] | The University of the Punjab, Lahore [1633]".
    // Returns how many were read, or null when the file has no such sheet.
    private function universities(string $path, InitiativeFile $file): ?array
    {
        $columns = [
            'name' => ['University / College (one spelling)'],
            'type' => ['Type'],
            'spellings' => ['Spellings joined (department list rows)', 'Spellings joined'],
        ];

        foreach ($this->stream->sheets($path, 'xlsx') as $sheet) {
            foreach ($sheet['preview'] as $headingNumber => $values) {
                $map = [];

                foreach ($values as $index => $value) {
                    foreach ($columns as $key => $headings) {
                        if (is_string($value) && in_array(self::normal($value), array_map(self::normal(...), $headings), true)) {
                            $map[$key] = $index;
                        }
                    }
                }

                if (count($map) === count($columns)) {
                    return $this->saveUniversities($path, $sheet['index'], $headingNumber, $map, $file);
                }
            }
        }

        return null;
    }

    private function saveUniversities(string $path, int $sheetIndex, int $headingNumber, array $map, InitiativeFile $file): array
    {
        $rows = [];
        $names = [];
        $now = now();

        foreach ($this->stream->rows($path, 'xlsx', $sheetIndex) as $number => $values) {
            $name = $values[$map['name']] ?? null;

            if ($number <= $headingNumber || ! is_string($name) || $name === '') {
                continue;
            }

            $type = $values[$map['type']] ?? null;
            $names[$name] = true;

            foreach (explode(' | ', (string) ($values[$map['spellings']] ?? '')) as $part) {
                // The number of list rows in brackets is left off.
                $spelling = trim((string) preg_replace('/\s*\[\d[\d,]*\]$/', '', $part));

                if ($spelling === '') {
                    continue;
                }

                if (isset($rows[$spelling]) && $rows[$spelling]['name'] !== $name) {
                    throw new ImportFailed('In the Universities sheet, "'.$spelling.'" is joined to both "'.$rows[$spelling]['name'].'" and "'.$name.'". Join each spelling to one name and upload the file again.');
                }

                $rows[$spelling] = [
                    'spelling' => mb_substr($spelling, 0, 255),
                    'name' => mb_substr($name, 0, 255),
                    'type' => is_string($type) && $type !== '' ? mb_substr($type, 0, 100) : null,
                    'initiative_file_id' => $file->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows === []) {
            return null;
        }

        DB::table((new HonhaarInstitution)->getTable())->delete();

        foreach (array_chunk(array_values($rows), self::CHUNK) as $chunk) {
            DB::table((new HonhaarInstitution)->getTable())->insert($chunk);
        }

        return ['names' => count($names), 'spellings' => count($rows)];
    }
}
