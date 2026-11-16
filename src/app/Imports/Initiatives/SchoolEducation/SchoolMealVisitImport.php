<?php

namespace App\Imports\Initiatives\SchoolEducation;

use App\Models\Initiatives\SchoolEducation\SchoolMealVisit;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

// Our visit file for the Chief Minister's Punjab Schools Meal Program. Reading, checking and saving
// work as for the other visit files: every row is checked first, and one bad row means nothing is
// saved. Every column of the file is kept except Additional Remarks, which the user doesn't want stored
// (the upload lists it as not saved). Visits are matched on EMIS code and timestamp, because a school
// can be visited twice on the same day.
class SchoolMealVisitImport extends PsrpVisitImport
{
    // The longest free text kept, well above the 32,767 characters an Excel cell can hold.
    private const LONG_TEXT = 65535;

    // Each column's label is also its heading in the records table's CSV download, so that file can be
    // uploaded again. "headings" are the visit file's own headings.
    private const COLUMNS = [
        'visited_at' => ['label' => 'Visit date and time', 'type' => 'datetime', 'required' => true, 'headings' => ['timestamp', 'visit date', 'visited at']],
        'district_agent' => ['label' => 'District agent', 'type' => 'text', 'max' => 100, 'headings' => []],
        'email' => ['label' => 'Email', 'type' => 'text', 'max' => 255, 'headings' => ['email address']],
        'district' => ['label' => 'District', 'type' => 'district', 'max' => 100, 'headings' => ['district name']],
        'emis_code' => ['label' => 'EMIS', 'type' => 'code', 'required' => true, 'max' => 20, 'headings' => ['emis code', 'school emis code', 'emis no']],
        'school_name' => ['label' => 'School name', 'type' => 'text', 'max' => 255, 'headings' => []],
        'school_opened' => ['label' => 'School opened', 'type' => 'boolean', 'headings' => []],
        'days_open_per_week' => ['label' => 'Days open in a week', 'type' => 'integer', 'headings' => ['How many days school open in a week']],
        'students_enrolled' => ['label' => 'Students enrolled', 'type' => 'integer', 'headings' => ['Total students enrolled']],
        'students_before_program' => ['label' => 'Students before the program', 'type' => 'integer', 'headings' => ['Total students before school nutrition program']],
        'students_present' => ['label' => 'Students present', 'type' => 'integer', 'headings' => ['Students attendance on visit day']],
        'students_absent' => ['label' => 'Students absent', 'type' => 'integer', 'headings' => ['Total absent students']],
        'students_receiving_milk' => ['label' => 'Students to receive milk', 'type' => 'integer', 'headings' => ["Total students in the school who are to receive the 'Milk Pack'"]],
        'absent_yesterday_receiving_milk' => ['label' => 'Absent yesterday, to receive milk', 'type' => 'integer', 'headings' => ['Students who were absent yesterday and are to receive a milk pack.']],
        'total_staff' => ['label' => 'Total staff', 'type' => 'integer', 'headings' => []],
        'staff_absent' => ['label' => 'Staff absent', 'type' => 'integer', 'headings' => ['Total absent staff']],
        'milk_packs_received' => ['label' => 'Milk packs received', 'type' => 'integer', 'headings' => ['How many packs of milk have been received so far?']],
        'milk_packs_distributed' => ['label' => 'Milk packs distributed', 'type' => 'integer', 'headings' => ['How many milk packs have been distributed among students to date?']],
        'milk_cartons_expired' => ['label' => 'Milk cartons expired', 'type' => 'integer', 'headings' => ['How many milk cartons have expired to date?']],
        'milk_packs_in_stock' => ['label' => 'Milk packs in stock', 'type' => 'integer', 'headings' => ['How many milk packs are in stock as of today?']],
        'stock_expiry_date' => ['label' => 'Stock expiry date', 'type' => 'expiry_date', 'headings' => []],
        'expired_stock_received' => ['label' => 'Expired stock received', 'type' => 'boolean', 'headings' => ['Has the school received any expired stock?']],
        'milk_caused_harm' => ['label' => 'Milk caused harm', 'type' => 'boolean', 'headings' => ['Has the milk provided to the students caused any harm? (Such as an illness)']],
        'milk_delivered_on_time' => ['label' => 'Delivered on time', 'type' => 'boolean', 'headings' => ['Is the milk delivered on time?']],
        'delivery_gap_days' => ['label' => 'Days until milk arrives', 'type' => 'integer', 'headings' => ['How many days later does the school receive milk packets?']],
        'milk_stock_ever_received' => ['label' => 'Milk stock ever received', 'type' => 'boolean', 'headings' => ['school ever received milk stock']],
        'milk_quality' => ['label' => 'Milk quality satisfactory', 'type' => 'boolean', 'headings' => ['Was the quality of the milk packets satisfactory?']],
        'poor_cleanliness' => ['label' => 'Poor cleanliness', 'type' => 'boolean', 'headings' => ['Poor cleanliness at the school']],
        'lack_of_disposal_facilities' => ['label' => 'Lack of disposal facilities', 'type' => 'boolean', 'headings' => ['Lack of proper disposal facilities for used milk cartons']],
        'inadequate_storage_facilities' => ['label' => 'Inadequate storage facilities', 'type' => 'boolean', 'headings' => ['Inadequate storage facilities for milk packs']],
        'large_portion_of_stock_received' => ['label' => 'Large portion of stock received', 'type' => 'boolean', 'headings' => ['A large portion of the stock was received.']],
        'milk_used_for_households' => ['label' => 'Milk used for households', 'type' => 'boolean', 'headings' => ['Teachers and other influential people use milk packets for their households.']],
        'overall_rating' => ['label' => 'Overall rating', 'type' => 'text', 'max' => 100, 'headings' => []],
        'photo_links' => ['label' => 'Photos', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => ['Photo Link']],
        'media_links' => ['label' => 'Audio / video', 'type' => 'text', 'max' => self::LONG_TEXT, 'headings' => ['Media Link']],
        'latitude' => ['label' => 'Lat', 'type' => 'decimal', 'limit' => 90, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['latitude']],
        'longitude' => ['label' => 'Long', 'type' => 'decimal', 'limit' => 180, 'blanks' => self::NO_LOCATION_VALUES, 'headings' => ['longitude', 'lng', 'lon']],
    ];

    protected function columns(): array
    {
        return self::COLUMNS;
    }

    protected function model(): string
    {
        return SchoolMealVisit::class;
    }

    protected function parse(mixed $value, array $column, ?string $dateOrder): ?string
    {
        // The records table's CSV download puts an apostrophe before text starting with = + - or @,
        // so spreadsheets don't run it as a formula. Uploading that file again gives the text back as it was.
        if ($column['type'] === 'text' && is_string($value) && preg_match('/^\'[=+\-@]/', $value) === 1) {
            $value = substr($value, 1);
        }

        if ($column['type'] !== 'expiry_date') {
            return parent::parse($value, $column, $dateOrder);
        }

        // Blanks such as "Null" are treated as in every other column.
        $text = parent::parse($value, ['type' => 'text', 'max' => 100] + $column, $dateOrder);

        return $text === null ? null : $this->expiryDate($value, $text);
    }

    // Unlike a visit date, an expiry date is often still to come, so it may be in the future
    // and is never turned round. Written dates are read day first, as in the rest of the file.
    private function expiryDate(mixed $value, string $text): string
    {
        $date = null;

        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } elseif (is_numeric($text)) {
            $days = (float) $text;
            $date = $days >= 1 && $days < 100000 ? Carbon::create(1899, 12, 30)->addDays((int) floor($days)) : null;
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $text, $parts) === 1) {
            $date = checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? Carbon::create((int) $parts[1], (int) $parts[2], (int) $parts[3]) : null;
        } elseif (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $text, $parts) === 1) {
            $date = checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3]) ? Carbon::create((int) $parts[3], (int) $parts[2], (int) $parts[1]) : null;
        }

        if ($date === null) {
            throw new InvalidArgumentException('Not a valid date. Use a format like 2026-02-22.');
        }

        if ($date->year < 2000 || $date->year > 2100) {
            throw new InvalidArgumentException('This date is outside the expected range, from 2000 to 2100.');
        }

        return $date->format('Y-m-d');
    }
}
