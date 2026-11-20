<?php

namespace App\Imports\Initiatives\LocalGovernment;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Models\Initiatives\LocalGovernment\PdpVisit;

// Our visit file for the Chief Minister Punjab Development Program (PDP). Reading, checking and saving work
// as for the other visit files: every row is checked first, and one bad row means nothing is saved. Every
// column of the visit sheet is kept, the answers and links as typed. "Null" is a blank, as on the other pages.
//
// The file has no code for a work site. A visit is matched on its Report DATE, District, City and category,
// so the same file uploaded again, or with more rows, updates its rows instead of adding copies. Rows that
// share all four are numbered ("entry") in the order of their values, so each is kept, and the order of rows
// in the file doesn't matter.
class PdpVisitImport extends PsrpVisitImport
{
    // The longest a coordinate typed without its decimal point can be and still be kept as typed.
    private const LARGEST_KEPT_NUMBER = 9999999999;

    // Part of the key: a blank one is kept as empty text, so the row is still matched when uploaded again.
    private const KEY_TEXT = ['district', 'city', 'category'];

    // Each label is the visit file's own heading, used in the upload's problem list. "headings" add the
    // records table's shorter CSV headings, so that file can be uploaded again.
    private const COLUMNS = [
        'visited_at' => ['label' => 'Report DATE', 'type' => 'date', 'required' => true, 'headings' => ['report date', 'date', 'visit date', 'smu report date']],
        'district' => ['label' => 'District', 'type' => 'text', 'max' => 100, 'headings' => ['district name']],
        'city' => ['label' => 'Cities', 'type' => 'text', 'max' => 100, 'headings' => ['city', 'city name', 'town']],
        'manholes_without_cover' => ['label' => 'Manholes without cover', 'type' => 'text', 'max' => 100, 'headings' => []],
        'workers_in_uniform' => ['label' => 'Workers wearing proposed uniform', 'type' => 'text', 'max' => 100, 'headings' => ['workers in uniform']],
        'workers_using_safety_equipment' => ['label' => 'Workers using proper safety equipment', 'type' => 'text', 'max' => 100, 'headings' => ['workers using safety equipment']],
        'safety_boards_installed' => ['label' => 'Safety board barriers installation', 'type' => 'text', 'max' => 100, 'headings' => ['safety boards installed']],
        'construction_waste_removed' => ['label' => 'C and D waste was removed', 'type' => 'text', 'max' => 100, 'headings' => ['c and d waste removed']],
        'dust_control_measures' => ['label' => 'Measures taken to control dust', 'type' => 'text', 'max' => 100, 'headings' => ['dust control measures']],
        'excavation_sites_secured' => ['label' => 'Excavation sites properly secured with barriers', 'type' => 'text', 'max' => 100, 'headings' => ['excavation sites secured']],
        'materials_organized' => ['label' => 'Construction materials properly organized and secured', 'type' => 'text', 'max' => 100, 'headings' => ['materials organized']],
        'slow_or_halted_work' => ['label' => 'slow or halted work', 'type' => 'text', 'max' => 100, 'headings' => []],
        'trees_protected' => ['label' => 'Care being taken to protect trees & green', 'type' => 'text', 'max' => 100, 'headings' => ['trees protected']],
        'traffic_managed' => ['label' => 'Traffic movement around the construction site managed', 'type' => 'text', 'max' => 100, 'headings' => ['traffic managed']],
        // The longest an Excel cell can be, well within the table's medium text.
        'images' => ['label' => 'Image Path', 'type' => 'text', 'max' => 65535, 'headings' => ['images', 'image', 'image paths', 'photos', 'pictures']],
        'videos' => ['label' => 'Videos', 'type' => 'text', 'max' => 65535, 'headings' => ['video', 'video path', 'video paths', 'video links']],
        'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'limit' => 90, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['lat']],
        'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'limit' => 180, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['long', 'lng', 'lon']],
        'category' => ['label' => 'category', 'type' => 'text', 'max' => 255, 'headings' => ['categories']],
    ];

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return PdpVisit::class;
    }

    protected function keyColumns(): array
    {
        return ['visited_at', 'district', 'city', 'category', 'entry'];
    }

    protected function repeatColumn(): ?string
    {
        return 'entry';
    }

    protected function headingsNotFoundMessage(): string
    {
        return 'The column headings could not be found. The file needs "Report DATE", "District", "Cities" and "category" columns.';
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        // A coordinate is kept as typed, to 8 decimal places, even one whose decimal point is missing, so
        // nothing in the file is lost; the page uses only a location inside Punjab. Text that can't be a
        // number is left to the shared rule, which leaves it blank and counts it.
        if ($column['type'] === 'decimal'
            && (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value))))
            && abs((float) $value) <= self::LARGEST_KEPT_NUMBER) {
            return number_format((float) $value, 8, '.', '');
        }

        if ($column['type'] === 'text' && is_string($value)) {
            // Spaces around a value ("Yes ") are not part of it. Text that isn't valid UTF-8 is trimmed of
            // plain spaces only, never lost.
            $value = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value) ?? trim($value);

            // The records table's CSV download puts an apostrophe before text starting with = + - or @,
            // so spreadsheets don't run it as a formula. Uploading that file again gives the text back.
            if (preg_match('/^\'[=+\-@]/', $value) === 1) {
                $value = substr($value, 1);
            }
        }

        $parsed = parent::parse($value, $column, $dateOrder);

        return $parsed === null && in_array($this->columnOf($column), self::KEY_TEXT, true) ? '' : $parsed;
    }

    // The table column a definition belongs to.
    private function columnOf(array $column): ?string
    {
        foreach (self::COLUMNS as $name => $definition) {
            if ($definition['label'] === $column['label']) {
                return $name;
            }
        }

        return null;
    }
}
