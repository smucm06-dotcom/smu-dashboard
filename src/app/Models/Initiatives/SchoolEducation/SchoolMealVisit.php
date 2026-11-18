<?php

namespace App\Models\Initiatives\SchoolEducation;

use Illuminate\Database\Eloquent\Model;

// One of our visits to a school in the Chief Minister's Punjab Schools Meal Program.
class SchoolMealVisit extends Model
{
    protected $table = 'punjab_schools_meal_program';

    protected function casts(): array
    {
        // The stock expiry date is left as written (2026-02-22), so the visit's details show it as a date.
        return [
            'visited_at' => 'datetime',
            'school_opened' => 'boolean',
            'days_open_per_week' => 'integer',
            'students_enrolled' => 'integer',
            'students_before_program' => 'integer',
            'students_present' => 'integer',
            'students_absent' => 'integer',
            'students_receiving_milk' => 'integer',
            'absent_yesterday_receiving_milk' => 'integer',
            'total_staff' => 'integer',
            'staff_absent' => 'integer',
            'milk_packs_received' => 'integer',
            'milk_packs_distributed' => 'integer',
            'milk_cartons_expired' => 'integer',
            'milk_packs_in_stock' => 'integer',
            'expired_stock_received' => 'boolean',
            'milk_caused_harm' => 'boolean',
            'milk_delivered_on_time' => 'boolean',
            'delivery_gap_days' => 'integer',
            'milk_stock_ever_received' => 'boolean',
            'milk_quality' => 'boolean',
            'poor_cleanliness' => 'boolean',
            'lack_of_disposal_facilities' => 'boolean',
            'inadequate_storage_facilities' => 'boolean',
            'large_portion_of_stock_received' => 'boolean',
            'milk_used_for_households' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
