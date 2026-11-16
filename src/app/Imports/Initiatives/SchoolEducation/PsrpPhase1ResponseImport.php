<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Imports\InitiativeFileImport;
use App\Imports\ReadsHeadedSheet;
use App\Imports\SpreadsheetReader;
use App\Models\InitiativeFile;
use App\Models\Initiatives\SchoolEducation\PsrpPhase1Response;
use RuntimeException;

class PsrpPhase1ResponseImport implements InitiativeFileImport
{
    use ReadsHeadedSheet;

    private const MAX_ROWS = 100000;

    // Every column of the department's file: Sr., EMIS, District, School Name, SMU Audit Report Date,
    // PEF Response Date, Remarks of SMU, Response of PEF. The earlier file's headings still work
    // (SMU Visit Date, PSPR-PEF Response Date, Analysis Report Remarks, PSPR-PEF Response).
    private const COLUMNS = [
        'source_sr' => ['sr', 'sr no', 's no', 'sno', 'serial', 'serial no'],
        'emis_code' => ['emis code', 'emis', 'emis no', 'emis number'],
        'district' => ['district', 'district name'],
        'school_name' => ['school name', 'name of school'],
        'audit_report_date' => ['smu audit report date', 'audit report date', 'smu visit date', 'visit date'],
        'responded_at' => ['pef response date', 'pspr pef response date', 'response date', 'reply date'],
        'sent_remarks' => ['remarks of smu', 'smu remarks', 'analysis report remarks', 'report remarks', 'remarks'],
        'response' => ['response of pef', 'pef response', 'pspr pef response', 'department response', 'response', 'reply'],
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['emis_code', 'response'], self::MAX_ROWS);

        $rows = [];

        foreach ($sheet['rows'] as $row) {
            $emis = $this->emisCode($row['emis_code'] ?? null);

            // A row with no EMIS code, such as a title or a blank line, is not a reply.
            if ($emis === null) {
                continue;
            }

            // Every column is saved. A blank reply is kept too, as on Phase 2; the page leaves
            // it out of the figures, as before.
            $rows[] = [
                'source_sr' => is_numeric($row['source_sr'] ?? null) && (float) $row['source_sr'] >= 0 ? (int) $row['source_sr'] : null,
                'emis_code' => $emis,
                'district' => $this->cleanText($row['district'] ?? null, 100),
                'school_name' => $this->cleanText($row['school_name'] ?? null, 255),
                'audit_report_date' => $this->date($row['audit_report_date'] ?? null),
                'responded_at' => $this->date($row['responded_at'] ?? null),
                'sent_remarks' => $this->textOrBlank($row['sent_remarks'] ?? null),
                'response' => $this->textOrBlank($row['response'] ?? null) ?? '',
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No replies with an EMIS code were found in this file.');
        }

        return ['rows' => $rows, 'sheet' => $sheet['sheet']];
    }

    public function save(array $prepared, InitiativeFile $file): void
    {
        $now = now();

        foreach (array_chunk($prepared['rows'], 500) as $chunk) {
            PsrpPhase1Response::query()->insert(array_map(
                fn ($row) => $row + ['initiative_file_id' => $file->id, 'created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }

    // The newer file writes "—" where there is no reply or remark, as the earlier one wrote "-".
    private function textOrBlank(mixed $value): ?string
    {
        $text = $this->cleanText($value);

        return $text === null || preg_match('/^[\p{Pd}\s]+$/u', $text) === 1 ? null : $text;
    }

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column and a response column, such as "Response of PEF".';
    }
}
