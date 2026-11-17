<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Models\Initiatives\LocalGovernment\UcMcVisit;

// Our visit file for UC MC Office Service Delivery. Reading, checking and saving work as for the
// other visit files: every row is checked first, and one bad row means nothing is saved. Every
// column of the visit sheet is kept, and the answers are kept as typed. "Null" is a blank, as on the
// other pages. The file's "Match Summary" sheet only counts how the coordinates were found, so it
// isn't read.
//
// The file has no EMIS code, so a visit is matched on its Timestamp and Email. Several rows can share
// both (old rows have a date only, and one form can cover two UCs), so those rows are numbered
// ("entry") in the order of their values and all of them are kept. The order of rows in the file
// doesn't matter, so the records CSV, sorted any way, matches the same rows.
class UcMcVisitImport extends PsrpVisitImport
{
    // Each label is the visit file's own heading, used in the upload's problem list. "headings" add
    // the records table's CSV headings, so that file can be uploaded again.
    private const COLUMNS = [
        'visited_at' => ['label' => 'Timestamp', 'type' => 'datetime', 'required' => true, 'headings' => ['visit date', 'visited at', 'visit date and time']],
        'email' => ['label' => 'Email', 'type' => 'text', 'required' => true, 'max' => 255, 'headings' => ['email address']],
        'agent' => ['label' => 'Agent', 'type' => 'text', 'max' => 100, 'headings' => []],
        'district' => ['label' => 'District', 'type' => 'text', 'max' => 100, 'headings' => ['district name']],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text', 'max' => 100, 'headings' => ['tehsil name']],
        'name' => ['label' => 'Name', 'type' => 'text', 'max' => 255, 'headings' => ['uc name', 'office name']],
        'uc_number' => ['label' => 'UC', 'type' => 'text', 'max' => 100, 'headings' => ['uc number', 'uc no']],
        'mc_name' => ['label' => 'MC', 'type' => 'text', 'max' => 100, 'headings' => ['mc name']],
        'office_open' => ['label' => 'Office open?', 'type' => 'text', 'max' => 255, 'headings' => []],
        'incharge_present' => ['label' => 'Incharge present?', 'type' => 'text', 'max' => 255, 'headings' => []],
        'service_delivery' => ['label' => 'Service delivery', 'type' => 'text', 'max' => 255, 'headings' => []],
        'schedule_displayed' => ['label' => 'Schedule displayed?', 'type' => 'text', 'max' => 255, 'headings' => []],
        'schedule_followed' => ['label' => 'Schedule followed?', 'type' => 'text', 'max' => 255, 'headings' => []],
        'unauthorized_private_person' => ['label' => 'Unauthorizedprivate person', 'type' => 'text', 'max' => 255, 'headings' => ['Unauthorized private person', 'Unauthorized/private person']],
        'dilapidated_building' => ['label' => 'Delipidated building', 'type' => 'text', 'max' => 255, 'headings' => ['Dilapidated building']],
        'office_cleanliness' => ['label' => 'Office Cleanliness', 'type' => 'text', 'max' => 255, 'headings' => []],
        'office_board_displayed' => ['label' => 'Office Board displayed', 'type' => 'text', 'max' => 255, 'headings' => []],
        // A coordinate that can't be used leaves the visit without a location, as on the other pages.
        'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'limit' => 90, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['lat']],
        'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'limit' => 180, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['long', 'lng', 'lon']],
    ];

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return UcMcVisit::class;
    }

    protected function keyColumns(): array
    {
        return ['visited_at', 'email', 'entry'];
    }

    protected function repeatColumn(): ?string
    {
        return 'entry';
    }

    // The database treats an email the same whatever its letters' case, so the key does too.
    protected function keyOf(array $record): string
    {
        return parent::keyOf(['email' => mb_strtolower((string) ($record['email'] ?? ''))] + $record);
    }

    protected function headingsNotFoundMessage(): string
    {
        return 'The column headings could not be found. The file needs a "Timestamp" column and an "Email" column.';
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        if ($column['type'] === 'text' && is_string($value)) {
            // Spaces around a value ("Yes ") are not part of it. Text that isn't valid UTF-8 is
            // trimmed of plain spaces only, never lost.
            $value = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value) ?? trim($value);

            // The records table's CSV download puts an apostrophe before text starting with = + - or @,
            // so spreadsheets don't run it as a formula. Uploading that file again gives the text back.
            if (preg_match('/^\'[=+\-@]/', $value) === 1) {
                $value = substr($value, 1);
            }
        }

        return parent::parse($value, $column, $dateOrder);
    }
}
