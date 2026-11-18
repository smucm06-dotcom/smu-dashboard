<?php

namespace App\Imports\Initiatives\HousingUrbanDevelopment;

use App\Imports\Initiatives\SchoolEducation\PsrpVisitImport;
use App\Models\Initiatives\HousingUrbanDevelopment\PspaVisit;

// Our visit file for Punjab Saaf Pani Authority (PSPA). Reading, checking and saving work as for the
// other visit files: every row is checked first, and one bad row means nothing is saved. Every column
// of the visit sheet is kept, and the answers are kept as typed. "Null" is a blank, as on the other pages.
//
// The file has no plant code, and its Sr is only the row's number in a file sorted by district, so it
// changes when a visit is added in between. A visit is matched on its Date instead. Many visits were
// entered with the same Date (the earliest rows have a date only), so those rows are numbered ("entry")
// in the order of their values and all of them are kept, as on UC MC. The order of rows in the file
// doesn't matter, so the records CSV, sorted any way, matches the same rows.
class PspaVisitImport extends PsrpVisitImport
{
    // Each label is the visit file's own heading, used in the upload's problem list. "headings" add the
    // records table's shorter CSV headings, so that file can be uploaded again.
    private const COLUMNS = [
        'source_sr' => ['label' => 'Sr', 'type' => 'integer', 'headings' => ['sr no', 's no', 'serial no']],
        'visited_at' => ['label' => 'Date', 'type' => 'datetime', 'required' => true, 'headings' => ['visit date', 'visited at', 'timestamp', 'visit date and time']],
        'district' => ['label' => 'District', 'type' => 'text', 'max' => 100, 'headings' => ['district name']],
        'tehsil' => ['label' => 'Tehsil', 'type' => 'text', 'max' => 100, 'headings' => ['tehsil name']],
        'site_name' => ['label' => 'Site Name', 'type' => 'text', 'max' => 255, 'headings' => ['name of site', 'site']],
        'functionality' => ['label' => 'Functionality of filtration plant', 'type' => 'text', 'max' => 255, 'headings' => ['functionality']],
        'total_taps' => ['label' => 'Total water taps', 'type' => 'integer', 'headings' => []],
        'functional_taps' => ['label' => 'Functional water taps', 'type' => 'integer', 'headings' => []],
        // The longest an Excel cell can be, well within the table's medium text.
        'images' => ['label' => 'Images', 'type' => 'text', 'max' => 65535, 'headings' => ['photos', 'pictures']],
        'plant_unclean' => ['label' => 'Uncleanliness of filtration plant', 'type' => 'text', 'max' => 100, 'headings' => ['plant unclean']],
        'no_electricity' => ['label' => 'Unavailability of electricity', 'type' => 'text', 'max' => 100, 'headings' => ['no electricity']],
        'meter_disconnected' => ['label' => 'Electricity meter/connection disconnected', 'type' => 'text', 'max' => 100, 'headings' => ['meter disconnected']],
        'pending_electricity_bill' => ['label' => 'Pending electricity bill', 'type' => 'text', 'max' => 100, 'headings' => []],
        'motor_pump_non_functional' => ['label' => 'Non-functional motor/pump', 'type' => 'text', 'max' => 100, 'headings' => ['motor pump non functional']],
        'non_functional_long' => ['label' => 'Filtration plant non-functional for long', 'type' => 'text', 'max' => 100, 'headings' => ['non functional for long']],
        'operator_present' => ['label' => 'Operator/worker was present', 'type' => 'text', 'max' => 100, 'headings' => ['operator present']],
        'private_company' => ['label' => 'Filtration plant is operating under a private company', 'type' => 'text', 'max' => 100, 'headings' => ['private company']],
        'found_at_location' => ['label' => 'Filtration plant found on the said location', 'type' => 'text', 'max' => 100, 'headings' => ['found at location']],
        'lacks_repair_maintenance' => ['label' => 'Lack of repair and maintenance of filtration plant', 'type' => 'text', 'max' => 100, 'headings' => ['lacks repair and maintenance']],
        'tank_leakage' => ['label' => 'Leakage in the water supply tank', 'type' => 'text', 'max' => 100, 'headings' => ['tank leakage']],
        'broken_floor_tap_area' => ['label' => 'Broken floor and water tap area', 'type' => 'text', 'max' => 100, 'headings' => ['broken floor and tap area']],
        'tank_cover' => ['label' => 'cover over water tank', 'type' => 'text', 'max' => 100, 'headings' => []],
        'contractor_left_incomplete' => ['label' => 'The contractor left without completing the filtration plant work', 'type' => 'text', 'max' => 100, 'headings' => ['contractor left work incomplete']],
        'poor_water_quality' => ['label' => 'Poor quality of water', 'type' => 'text', 'max' => 100, 'headings' => []],
        'smell_in_water' => ['label' => 'Smell in water', 'type' => 'text', 'max' => 100, 'headings' => []],
        'non_functional_taps' => ['label' => 'Non-functional water taps', 'type' => 'text', 'max' => 100, 'headings' => []],
        'broken_taps' => ['label' => 'Broken water taps', 'type' => 'text', 'max' => 100, 'headings' => []],
        'filter_needs_replacement' => ['label' => 'The filter needs to be replaced', 'type' => 'text', 'max' => 100, 'headings' => ['filter needs replacing']],
        'filter_non_functional' => ['label' => 'Non-functional filter (coarse/fine)', 'type' => 'text', 'max' => 100, 'headings' => ['filter non functional']],
        'room_and_filling_area_closed' => ['label' => "Filtration plant's room and water filling area was closed", 'type' => 'text', 'max' => 100, 'headings' => ['room and filling area closed']],
        'bill_paid_by_residents' => ['label' => 'Electricity bill payment by residents', 'type' => 'text', 'max' => 100, 'headings' => ['bill paid by residents']],
        'operator_charging_citizens' => ['label' => 'Operator was charging amount from citizens for clean water', 'type' => 'text', 'max' => 100, 'headings' => ['operator charging citizens']],
        'taps_stolen' => ['label' => 'The water taps have been taken away by thieves', 'type' => 'text', 'max' => 100, 'headings' => ['taps stolen']],
        'door_stolen' => ['label' => 'The filtration plant door has been stolen', 'type' => 'text', 'max' => 100, 'headings' => ['door stolen']],
        'shut_down_early' => ['label' => 'The filtration plant is shut down early instead of operating according to its scheduled operating hours.', 'type' => 'text', 'max' => 100, 'headings' => ['shut down early']],
        'operator_unpaid' => ['label' => 'The plant operator has not received a salary for several months', 'type' => 'text', 'max' => 100, 'headings' => ['operator unpaid']],
        'dilapidated_building' => ['label' => 'dilapidated building of filtration plant', 'type' => 'text', 'max' => 100, 'headings' => ['dilapidated building']],
        'illegally_occupied' => ['label' => 'The filtration plant has been illegally occupied by an individual, and no proper care or maintenance of the plant is being carried out', 'type' => 'text', 'max' => 100, 'headings' => ['illegally occupied']],
        'dogs_around_plant' => ['label' => 'Someone has kept several dogs around the filtration plant, which is preventing citizens from getting water', 'type' => 'text', 'max' => 100, 'headings' => ['dogs around plant']],
        'rats_inside_plant' => ['label' => 'Rats have been found inside the filtration plant setup, resulting in significant uncleanliness and highly unhygienic conditions.', 'type' => 'text', 'max' => 100, 'headings' => ['rats inside plant']],
        'non_operational_15_days' => ['label' => 'The filtration plant has remained non-operational for the past 15 days. Multiple pipeline leakages and damage to one filtration vessel have rendered the system partially dysfunctional, with only one vessel currently in operation.', 'type' => 'text', 'max' => 100, 'headings' => ['non operational 15 days']],
        'key_held_by_vendor' => ['label' => 'The filtration plant key is held by a street vendor (Chabri Shop), who uses the premises for storage and does not operate the plant regularly.', 'type' => 'text', 'max' => 100, 'headings' => ['key held by street vendor']],
        'taps_on_motor_no_filtration' => ['label' => 'Water taps are directly connected to the motor, and no filtration system is installed.', 'type' => 'text', 'max' => 100, 'headings' => ['taps on motor no filtration']],
        'pfa_suspended_supply' => ['label' => 'The Punjab Food Authority has suspended the water supply due to substandard water quality.', 'type' => 'text', 'max' => 100, 'headings' => ['supply suspended by punjab food authority']],
        'bore_not_working' => ['label' => 'The filtration plant bore is not functioning properly and is unable to extract water efficiently. A new bore needs to be installed, as the filter turns black within a few days.', 'type' => 'text', 'max' => 100, 'headings' => ['bore not working']],
        'electricity_theft' => ['label' => "Theft of Electricity from the Filtration Plant's Power Connection", 'type' => 'text', 'max' => 100, 'headings' => ['electricity theft']],
        'direct_pole_connection' => ['label' => 'Electricity theft has been committed at the filtration plant by taking a direct connection from the electric pole.', 'type' => 'text', 'max' => 100, 'headings' => ['direct connection from pole']],
        'cooler_instead_of_taps' => ['label' => 'Water cooler has been installed at this filtration plant instead of water taps', 'type' => 'text', 'max' => 100, 'headings' => ['water cooler instead of taps']],
        // A coordinate that can't be used leaves the visit without a location, as on the other pages.
        'latitude' => ['label' => 'lat', 'type' => 'decimal', 'limit' => 90, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['latitude']],
        'longitude' => ['label' => 'long', 'type' => 'decimal', 'limit' => 180, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['longitude', 'lng', 'lon']],
    ];

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

    // Visits sharing a Date are numbered in the order of what was recorded, leaving out Sr: it is only
    // the row's place in the file, and changes when a visit is added in between.
    protected function repeatOrder(array $record): string
    {
        unset($record['source_sr']);

        return parent::repeatOrder($record);
    }

    protected function headingsNotFoundMessage(): string
    {
        return 'The column headings could not be found. The file needs a "Date" column.';
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        // Coordinates are kept to 8 decimal places. One that can't be used is left to the shared rule,
        // which leaves it blank and counts it.
        if ($column['type'] === 'decimal'
            && (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))
            && abs((float) $value) <= $column['limit']) {
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

        return parent::parse($value, $column, $dateOrder);
    }
}
