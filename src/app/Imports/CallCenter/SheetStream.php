<?php

namespace App\Imports\CallCenter;

use DateTimeInterface;
use Generator;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\ErrorCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\SheetWithVisibilityInterface;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;
use ZipArchive;

// Reads a call center file one row at a time, so a file of a million rows never has to fit in memory.
// Cell values are cleaned the same way as the other uploads (App\Imports\SpreadsheetReader): runs of spaces
// become one and the ends are trimmed.
class SheetStream
{
    private const PREVIEW_ROWS = 10;

    // Each sheet's index, name and first rows, read without going through the whole file.
    public function sheets(string $path, string $extension): array
    {
        $sheets = [];
        $reader = $this->open($path, $extension);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $preview = [];

                foreach ($sheet->getRowIterator() as $number => $row) {
                    $values = array_map($this->value(...), $row->cells);

                    if ($this->filled($values)) {
                        $preview[$number] = $values;
                    }

                    if (count($preview) === self::PREVIEW_ROWS) {
                        break;
                    }
                }

                $sheets[] = [
                    'index' => $sheet->getIndex(),
                    'name' => $sheet->getName(),
                    'visible' => ! $sheet instanceof SheetWithVisibilityInterface || $sheet->isVisible(),
                    'preview' => $preview,
                ];
            }
        } catch (Throwable $e) {
            throw $this->unreadable($e);
        } finally {
            $reader->close();
        }

        return $sheets;
    }

    // The sheet's non-empty rows, each with its row number as Excel shows it.
    public function rows(string $path, string $extension, int $sheetIndex): Generator
    {
        $reader = $this->open($path, $extension);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== $sheetIndex) {
                    continue;
                }

                foreach ($sheet->getRowIterator() as $number => $row) {
                    $values = array_map($this->value(...), $row->cells);

                    if ($this->filled($values)) {
                        yield $number => $values;
                    }
                }

                break;
            }
        } catch (Throwable $e) {
            throw $this->unreadable($e);
        } finally {
            $reader->close();
        }
    }

    // About how many rows the sheet has, for the progress bar: Excel writes it at the top of the sheet, so the
    // file need not be read twice. Returns null when it is not written there (CSV files are counted).
    public function rowCount(string $path, string $extension, int $sheetIndex): ?int
    {
        if ($extension === 'csv') {
            return $this->countCsvLines($path);
        }

        try {
            return $this->xlsxDimension($path, $sheetIndex);
        } catch (Throwable) {
            return null;
        }
    }

    private function xlsxDimension(string $path, int $sheetIndex): ?int
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        try {
            // The workbook lists its sheets in order; its relationships say which file holds each one.
            $workbook = (string) $zip->getFromName('xl/workbook.xml');
            $relations = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');

            preg_match_all('/<sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $ids);
            $id = $ids[1][$sheetIndex] ?? null;

            if ($id === null || preg_match('/<Relationship\b[^>]*\bId="'.preg_quote($id, '/').'"[^>]*\bTarget="([^"]+)"/', $relations, $target) !== 1) {
                return null;
            }

            $file = ltrim($target[1], '/');
            $file = str_starts_with($file, 'xl/') ? $file : 'xl/'.$file;

            // The dimension is near the top of the sheet's XML, e.g. <dimension ref="A1:T96721"/>.
            $stream = $zip->getStream($file);

            if ($stream === false) {
                return null;
            }

            $head = (string) fread($stream, 4096);
            fclose($stream);

            return preg_match('/<dimension\s+ref="[A-Z]+\d+:[A-Z]+(\d+)"/', $head, $match) === 1 ? (int) $match[1] : null;
        } finally {
            $zip->close();
        }
    }

    private function countCsvLines(string $path): ?int
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $lines = 0;

        while (! feof($handle)) {
            $lines += substr_count((string) fread($handle, 1 << 20), "\n");
        }

        fclose($handle);

        return $lines;
    }

    private function open(string $path, string $extension): XlsxReader|CsvReader
    {
        // Empty rows are kept while reading so row numbers match what the user sees in Excel.
        $reader = $extension === 'xlsx'
            ? new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true), new SharedStringsCache)
            : new CsvReader(new CsvOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));

        try {
            $reader->open($path);
        } catch (Throwable $e) {
            throw $this->unreadable($e);
        }

        return $reader;
    }

    private function unreadable(Throwable $e): RuntimeException
    {
        return new RuntimeException(
            'This file could not be read. Check that it is a valid CSV or Excel (.xlsx) file and try again.',
            previous: $e
        );
    }

    private function filled(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function value(Cell $cell): mixed
    {
        $value = match (true) {
            $cell instanceof FormulaCell => $cell->getComputedValue(),
            $cell instanceof ErrorCell => $cell->getRawValue(),
            default => $cell->getValue(),
        };

        if ($value instanceof DateTimeInterface || is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $value));
    }
}
