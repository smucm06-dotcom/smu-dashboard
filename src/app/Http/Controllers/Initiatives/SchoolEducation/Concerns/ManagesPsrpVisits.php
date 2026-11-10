<?php

namespace App\Http\Controllers\Initiatives\SchoolEducation\Concerns;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

// What every PSRP phase does the same way with its visit records: filters, upload and download.
trait ManagesPsrpVisits
{
    private function filters(Request $request): array
    {
        $from = $this->date($request, 'from');
        $to = $this->date($request, 'to');

        // Dates entered the wrong way round still mean the same range.
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'district' => $this->text($request, 'district'),
            'from' => $from,
            'to' => $to,
        ];
    }

    private function date(Request $request, string $key): string
    {
        $value = $this->text($request, $key);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts) !== 1) {
            return '';
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : '';
    }

    // Everything the findings need, in one download. The browser keeps a copy until the data changes.
    private function dashboardData(Request $request, object $dashboard)
    {
        $headers = [
            'ETag' => '"'.$dashboard->version().'"',
            'Cache-Control' => 'private, no-cache',
            'Vary' => 'Accept-Encoding',
        ];

        if (in_array($headers['ETag'], $request->getETags(), true)) {
            return response('', 304, $headers);
        }

        $json = json_encode($dashboard->data(), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json';

        // About 2 MB of text becomes about 0.25 MB.
        if (str_contains((string) $request->header('Accept-Encoding'), 'gzip')) {
            $json = gzencode($json, 6);
            $headers['Content-Encoding'] = 'gzip';
        }

        return response($json, 200, $headers);
    }

    // The schools the map shows for one issue. The page sends their visits, so the file always
    // holds exactly what is on the map, whatever filters are on.
    private function downloadMapSchools(Request $request, string $model, array $columns, string $prefix)
    {
        $sent = $request->input('ids');
        $ids = array_map('intval', array_filter(explode(',', is_string($sent) ? $sent : ''), 'ctype_digit'));

        abort_if($ids === [], 404);

        $visits = $model::query()
            ->whereIntegerInRaw('id', $ids)
            ->orderBy('source_sr')
            ->orderBy('id')
            ->cursor();

        $sentName = $request->input('file_name');
        $name = Str::limit(Str::slug(is_string($sentName) ? $sentName : ''), 60, '');

        return $this->downloadExcel($visits, $columns, $prefix.'-'.($name ?: 'schools').'-'.now()->format('Y-m-d').'.xlsx');
    }

    private function uploadVisits(Request $request, Initiative $initiative, PsrpVisitImport $import)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'extensions:csv,xlsx', 'max:20480'],
        ], [
            'file.required' => 'Choose a CSV or Excel (.xlsx) file to upload.',
            'file.file' => 'The file could not be uploaded. Please try again.',
            'file.uploaded' => 'The file could not be uploaded. Please try again.',
            'file.extensions' => 'Only CSV and Excel (.xlsx) files can be uploaded.',
            'file.max' => 'The file must be 20 MB or smaller.',
        ]);

        if ($validator->fails()) {
            $result = ['failure' => $validator->errors()->first('file')];
        } else {
            $file = $request->file('file');

            $result = $import->run(
                $file->getRealPath(),
                strtolower($file->getClientOriginalExtension())
            ) + ['fileName' => $file->getClientOriginalName()];

            if (! isset($result['failure'])) {
                $result['fileKept'] = $this->keepVisitsFile($initiative, $file, $result);
            }
        }

        // Filters are cleared so that newly added visits are not hidden by them.
        return redirect()
            ->to(route('initiatives.show', $initiative).'#psrp-import-result')
            ->with('psrpImport', $result);
    }

    // The rows are already saved, so a problem keeping the file must not fail the upload.
    private function keepVisitsFile(Initiative $initiative, UploadedFile $file, array $result): bool
    {
        try {
            InitiativeFile::storeUpload($initiative, $file, InitiativeFile::TYPE_OUR_VISITS, [
                'row_count' => $result['added'] + $result['updated'] + $result['unchanged'],
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function downloadCsv(iterable $rows, array $columns, string $fileName)
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $output = fopen('php://output', 'w');

            // Lets Excel read the file as UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_column($columns, 'label'), escape: '');

            foreach ($rows as $row) {
                $values = [];

                foreach ($columns as $key => $column) {
                    $values[] = $this->csvValue(data_get($row, $key), $column);
                }

                fputcsv($output, $values, escape: '');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function downloadExcel(iterable $rows, array $columns, string $fileName)
    {
        return response()->streamDownload(function () use ($rows, $columns) {
            $dateStyles = [];

            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValuesWithStyle(array_column($columns, 'label'), new Style(fontBold: true)));

            foreach ($rows as $row) {
                $cells = [];

                foreach ($columns as $key => $column) {
                    $value = data_get($row, $key) ?? ($column['blank'] ?? null);

                    // Real dates and numbers, so they can be sorted and added up in Excel.
                    $cells[] = match (true) {
                        $value !== null && $column['type'] === 'date' => Cell::fromValue(Carbon::parse($value), $dateStyles[$key] ??= new Style(format: $column['excel_format'])),
                        // Saved as 71.4, shown in Excel as 71% like the visit file (Special Education).
                        is_numeric($value) && $column['type'] === 'percent' => Cell::fromValue($value / 100, $dateStyles['%'] ??= new Style(format: '0%')),
                        is_numeric($value) && $column['type'] === 'number' => Cell::fromValue($value + 0),
                        default => Cell::fromValue($this->csvValue($value, $column)),
                    };
                }

                $writer->addRow(new Row($cells));
            }

            $writer->close();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function csvValue($value, array $column)
    {
        if ($value === null) {
            return '';
        }

        return match ($column['type']) {
            'boolean' => $value ? 'Yes' : 'No',
            // Keeps what a re-uploaded visit is matched on.
            'date' => Carbon::parse($value)->format($column['csv_format'] ?? 'Y-m-d H:i:s'),
            // Stops spreadsheets running a text value as a formula.
            'text', 'code' => preg_match('/^[=+\-@]/', (string) $value) === 1 ? "'{$value}" : $value,
            default => $value,
        };
    }

    private function text(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }
}
