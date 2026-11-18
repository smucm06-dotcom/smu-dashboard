<?php

namespace App\Imports\Initiatives\HousingUrbanDevelopment;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Imports\SpreadsheetReader;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaVisit;

// Our visit file for Punjab Saaf Pani Authority (PSPA). Reading, checking and saving work as for the
// other visit files: every row is checked first, and one bad row means nothing is saved. Every column
// of the visit sheet is kept, and the answers are kept as typed. "Null" is a blank, as on the other
// pages. Remarks keep their line breaks; in every other column, spaces and line breaks become one space.
//
// The file has no plant code, so a visit is matched on its Date. Many visits were entered with the same
// Date (the earliest rows have a date only), so those rows are numbered ("entry") in the order of their
// values and all of them are kept, as on UC MC. The order of rows in the file doesn't matter, so the
// records CSV, sorted any way, matches the same rows.
class PspaVisitImport extends PsrpVisitImport
{
    // Each label is the visit file's own heading, used in the upload's problem list. "headings" add the
    // records table's shorter CSV headings, so that file can be uploaded again.
    private const COLUMNS = [
        'visited_at' => ['label' => 'Date', 'type' => 'datetime', 'required' => true, 'headings' => ['visit date', 'visited at', 'timestamp', 'visit date and time']],
        'district' => ['label' => 'District', 'type' => 'text', 'max' => 100, 'headings' => ['district name']],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text', 'max' => 100, 'headings' => ['tehsil name']],
        'site_name' => ['label' => 'Site Name', 'type' => 'text', 'max' => 255, 'headings' => ['name of site', 'site']],
        'functionality' => ['label' => 'Functionality of filtration plant', 'type' => 'text', 'max' => 255, 'headings' => ['functionality']],
        'total_taps' => ['label' => 'Total water taps', 'type' => 'integer', 'headings' => []],
        'functional_taps' => ['label' => 'Functional water taps', 'type' => 'integer', 'headings' => []],
        'water_quality' => ['label' => 'Quality of water', 'type' => 'text', 'max' => 100, 'headings' => ['water quality']],
        'worker_present' => ['label' => 'Filtration plant worker present', 'type' => 'text', 'max' => 100, 'headings' => ['worker present']],
        'plant_cleaned' => ['label' => 'Filtration plant cleaned', 'type' => 'text', 'max' => 100, 'headings' => ['plant cleaned']],
        'lacks_repair_maintenance' => ['label' => 'Water filtration plant lacks repair and maintenance', 'type' => 'text', 'max' => 100, 'headings' => ['lacks repair and maintenance']],
        'non_functional_long_time' => ['label' => 'Water filtration plant non-functional for a long time', 'type' => 'text', 'max' => 100, 'headings' => ['non functional for a long time']],
        'non_functional_taps' => ['label' => 'Non-functional water taps', 'type' => 'text', 'max' => 100, 'headings' => []],
        'broken_taps' => ['label' => 'Broken water taps', 'type' => 'text', 'max' => 100, 'headings' => []],
        'broken_tiles' => ['label' => 'Broken tiles', 'type' => 'text', 'max' => 100, 'headings' => []],
        'filter_needs_replacement' => ['label' => 'Filter needs replacement', 'type' => 'text', 'max' => 100, 'headings' => []],
        'poor_filtered_water' => ['label' => 'Poor quality of filtered water', 'type' => 'text', 'max' => 100, 'headings' => []],
        'electricity_available' => ['label' => 'electricity available', 'type' => 'text', 'max' => 100, 'headings' => []],
        'motor_pump_working' => ['label' => 'Motor pump working', 'type' => 'text', 'max' => 100, 'headings' => []],
        'filter_working' => ['label' => 'Filter working', 'type' => 'text', 'max' => 100, 'headings' => []],
        'odor_in_water' => ['label' => 'Odor in water', 'type' => 'text', 'max' => 100, 'headings' => []],
        'found_at_stated_location' => ['label' => 'Filtration plant found at the stated location', 'type' => 'text', 'max' => 100, 'headings' => ['found at stated location']],
        'fenced_and_locked' => ['label' => 'Site fenced and locked, so functionality of the plant could not be assessed', 'type' => 'text', 'max' => 100, 'headings' => ['fenced and locked']],
        'sewerage_non_functional' => ['label' => 'Sewerage system non-functional', 'type' => 'text', 'max' => 100, 'headings' => ['sewerage non functional']],
        'issues_found' => ['label' => 'Issues found', 'type' => 'text', 'max' => 100, 'headings' => []],
        'run_by_private_company' => ['label' => 'Filtration plant run by a private company', 'type' => 'text', 'max' => 100, 'headings' => ['run by a private company']],
        'operator_unpaid' => ['label' => 'Plant operator unpaid for several months', 'type' => 'text', 'max' => 100, 'headings' => ['operator unpaid']],
        'dilapidated_building' => ['label' => 'Dilapidated building', 'type' => 'text', 'max' => 100, 'headings' => []],
        'meter_cut_by_wapda' => ['label' => 'Electricity meter connection cut off by WAPDA', 'type' => 'text', 'max' => 100, 'headings' => ['meter cut off by wapda']],
        'contractor_left_incomplete' => ['label' => 'Contractor left without completing the filtration plant work', 'type' => 'text', 'max' => 100, 'headings' => ['contractor left work incomplete']],
        'tank_leak' => ['label' => 'Leak in water supply tank', 'type' => 'text', 'max' => 100, 'headings' => ['leak in supply tank']],
        'shut_down_early' => ['label' => 'Plant shut down early instead of running during scheduled hours', 'type' => 'text', 'max' => 100, 'headings' => ['shut down early']],
        'pending_electricity_bill' => ['label' => 'Pending electricity bill', 'type' => 'text', 'max' => 100, 'headings' => []],
        'enclosure_door_stolen' => ['label' => 'Door of the tap enclosure has been stolen', 'type' => 'text', 'max' => 100, 'headings' => ['tap enclosure door stolen']],
        'building_dilapidated_condition' => ['label' => 'Filtration plant building in dilapidated condition', 'type' => 'text', 'max' => 100, 'headings' => ['building in dilapidated condition']],
        'taps_stolen' => ['label' => 'Taps stolen by thieves', 'type' => 'text', 'max' => 100, 'headings' => ['taps stolen']],
        'bill_paid_by_residents' => ['label' => 'Electricity bill paid by residents', 'type' => 'text', 'max' => 100, 'headings' => ['bill paid by residents']],
        'tank_has_lid' => ['label' => 'Water tank has lid', 'type' => 'text', 'max' => 100, 'headings' => []],
        'purifying_chemical_available' => ['label' => 'chemical for purifying the water is available', 'type' => 'text', 'max' => 100, 'headings' => ['purifying chemical available']],
        'development_work_in_progress' => ['label' => 'Development work in progress at the filtration plant', 'type' => 'text', 'max' => 100, 'headings' => ['development work in progress']],
        'operator_charging_citizens' => ['label' => 'Operator was charging citizens money for clean water', 'type' => 'text', 'max' => 100, 'headings' => ['operator charging citizens']],
        'illegally_occupied' => ['label' => 'Plant illegally occupied by an individual and not properly maintained or repaired', 'type' => 'text', 'max' => 100, 'headings' => ['illegally occupied']],
        'taps_on_motor_no_filtration' => ['label' => 'Water taps connected directly to the motor and no filtration system installed', 'type' => 'text', 'max' => 100, 'headings' => ['taps on motor no filtration']],
        'electricity_theft' => ['label' => 'Electricity theft from the filtration plant connection', 'type' => 'text', 'max' => 100, 'headings' => ['electricity theft']],
        'connection_without_meter' => ['label' => 'Direct electricity connection taken from a pole without a meter', 'type' => 'text', 'max' => 100, 'headings' => ['connection without meter']],
        'rats_inside_plant' => ['label' => 'Rats found inside the plant, causing filth and highly unhygienic conditions', 'type' => 'text', 'max' => 100, 'headings' => ['rats inside plant']],
        'key_held_by_vendor' => ['label' => 'Plant key held by a street vendor who uses the space for storage and does not run the plant regularly', 'type' => 'text', 'max' => 100, 'headings' => ['key held by street vendor']],
        'cooler_instead_of_taps' => ['label' => 'Water cooler installed instead of water taps', 'type' => 'text', 'max' => 100, 'headings' => ['water cooler instead of taps']],
        'dogs_obstructing_citizens' => ['label' => 'Dogs kept around the plant by an individual, obstructing citizens from getting water', 'type' => 'text', 'max' => 100, 'headings' => ['dogs obstructing citizens']],
        'pfa_suspended_supply' => ['label' => 'Punjab Food Authority suspended water supply due to substandard water quality', 'type' => 'text', 'max' => 100, 'headings' => ['supply suspended by punjab food authority']],
        'bore_not_working' => ['label' => 'Plant bore not working properly and not drawing water, new bore needed as filter turns black within days', 'type' => 'text', 'max' => 100, 'headings' => ['bore not working']],
        'pipeline_leaking' => ['label' => 'Employee reported plant non-functional for fifteen days pipeline leaking at multiple points, with major cracks and leakage under water pressure', 'type' => 'text', 'max' => 100, 'headings' => ['pipeline leaking non functional 15 days']],
        'fire_damage' => ['label' => 'Fire damaged the building, equipment and other material', 'type' => 'text', 'max' => 100, 'headings' => ['fire damage']],
        // The longest an Excel cell can be, well within the table's medium text.
        'remarks' => ['label' => 'Remarks', 'type' => 'text', 'max' => 65535, 'lines' => true, 'headings' => []],
        'images' => ['label' => 'Images', 'type' => 'text', 'max' => 65535, 'headings' => ['photos', 'pictures']],
        // A coordinate that can't be used leaves the visit without a location, as on the other pages.
        'latitude' => ['label' => 'Lat', 'type' => 'decimal', 'limit' => 90, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['latitude']],
        'longitude' => ['label' => 'Long', 'type' => 'decimal', 'limit' => 180, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['longitude', 'lng', 'lon']],
    ];

    // Remarks are read with their line breaks; the other columns lose them below.
    public function __construct(SpreadsheetReader $reader)
    {
        parent::__construct($reader->keepingLineBreaks());
    }

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return PspaVisit::class;
    }

    protected function keyColumns(): array
    {
        return ['visited_at', 'entry'];
    }

    protected function repeatColumn(): ?string
    {
        return 'entry';
    }

    protected function headingsNotFoundMessage(): string
    {
        return 'The column headings could not be found. The file needs a "Date" column.';
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        // The file gives coordinates to up to 8 decimal places, and the table keeps all 8. One that can't
        // be used is left to the shared rule, which leaves it blank and counts it.
        if ($column['type'] === 'decimal'
            && (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))
            && abs((float) $value) <= $column['limit']) {
            return number_format((float) $value, 8, '.', '');
        }

        if ($column['type'] === 'text' && is_string($value)) {
            // Only remarks keep their line breaks. Text that isn't valid UTF-8 is trimmed of plain
            // spaces only, never lost.
            if (empty($column['lines'])) {
                $value = preg_replace('/[\s\x{00A0}]+/u', ' ', $value) ?? $value;
            }

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
