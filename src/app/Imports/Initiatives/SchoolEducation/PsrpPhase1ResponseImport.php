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

    private const COLUMNS = [
        'emis_code' => ['emis code', 'emis', 'emis no', 'emis number'],
        'sent_remarks' => ['analysis report remarks', 'report remarks', 'remarks'],
        'responded_at' => ['pspr pef response date', 'response date', 'reply date'],
        'response' => ['pspr pef response', 'department response', 'response', 'reply'],
    ];

    // Codes known to be mistyped in the department's sheets.
    private const EMIS_CORRECTIONS = ['311503116' => '31150316'];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function prepare(string $path, string $extension): array
    {
        $sheet = $this->readHeadedSheet($this->reader, $path, $extension, self::COLUMNS, ['emis_code', 'response'], self::MAX_ROWS);

        $rows = [];

        foreach ($sheet['rows'] as $row) {
            $emis = $this->emisCode($row['emis_code'] ?? null);
            $response = $this->cleanText($row['response'] ?? null);

            if ($emis === null || $response === null) {
                continue;
            }

            $rows[] = [
                'emis_code' => self::EMIS_CORRECTIONS[$emis] ?? $emis,
                'sent_remarks' => $this->cleanText($row['sent_remarks'] ?? null),
                'responded_at' => $this->date($row['responded_at'] ?? null),
                'response' => $response,
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

    private function missingHeadingsMessage(): string
    {
        return 'The column headings could not be found. The file needs an EMIS column and a response column, such as "PSPR-PEF Response".';
    }
}
