<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to water filtration plants for Punjab Saaf Pani Authority (PSPA), one row per row of the
    // visit file. Every column of the file is kept, in its order.
    public function up(): void
    {
        Schema::create('punjab_saaf_pani_authority', function (Blueprint $table) {
            $table->id();

            // Excel: Date, District, Tehsil, Site Name
            // The form's date and time. The earliest rows have a date only.
            $table->dateTime('visited_at');
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('site_name')->nullable();

            // Excel: Functionality of filtration plant, Total water taps, Functional water taps, Quality of water
            // Functionality is Yes or No, or a sentence such as "Filtration plant completely closed".
            $table->string('functionality')->nullable();
            $table->unsignedInteger('total_taps')->nullable();
            $table->unsignedInteger('functional_taps')->nullable();
            $table->string('water_quality', 100)->nullable();

            // The form's Yes / No questions, in the file's order, each answer as typed.
            $table->string('worker_present', 100)->nullable();                  // Filtration plant worker present
            $table->string('plant_cleaned', 100)->nullable();                   // Filtration plant cleaned
            $table->string('lacks_repair_maintenance', 100)->nullable();        // Water filtration plant lacks repair and maintenance
            $table->string('non_functional_long_time', 100)->nullable();        // Water filtration plant non-functional for a long time
            $table->string('non_functional_taps', 100)->nullable();             // Non-functional water taps
            $table->string('broken_taps', 100)->nullable();                     // Broken water taps
            $table->string('broken_tiles', 100)->nullable();                    // Broken tiles
            $table->string('filter_needs_replacement', 100)->nullable();        // Filter needs replacement
            $table->string('poor_filtered_water', 100)->nullable();             // Poor quality of filtered water
            $table->string('electricity_available', 100)->nullable();           // electricity available
            $table->string('motor_pump_working', 100)->nullable();              // Motor pump working
            $table->string('filter_working', 100)->nullable();                  // Filter working
            $table->string('odor_in_water', 100)->nullable();                   // Odor in water
            $table->string('found_at_stated_location', 100)->nullable();        // Filtration plant found at the stated location
            $table->string('fenced_and_locked', 100)->nullable();               // Site fenced and locked, so functionality of the plant could not be assessed
            $table->string('sewerage_non_functional', 100)->nullable();         // Sewerage system non-functional
            $table->string('issues_found', 100)->nullable();                    // Issues found
            $table->string('run_by_private_company', 100)->nullable();          // Filtration plant run by a private company
            $table->string('operator_unpaid', 100)->nullable();                 // Plant operator unpaid for several months
            $table->string('dilapidated_building', 100)->nullable();            // Dilapidated building
            $table->string('meter_cut_by_wapda', 100)->nullable();              // Electricity meter connection cut off by WAPDA
            $table->string('contractor_left_incomplete', 100)->nullable();      // Contractor left without completing the filtration plant work
            $table->string('tank_leak', 100)->nullable();                       // Leak in water supply tank
            $table->string('shut_down_early', 100)->nullable();                 // Plant shut down early instead of running during scheduled hours
            $table->string('pending_electricity_bill', 100)->nullable();        // Pending electricity bill
            $table->string('enclosure_door_stolen', 100)->nullable();           // Door of the tap enclosure has been stolen
            $table->string('building_dilapidated_condition', 100)->nullable();  // Filtration plant building in dilapidated condition
            $table->string('taps_stolen', 100)->nullable();                     // Taps stolen by thieves
            $table->string('bill_paid_by_residents', 100)->nullable();          // Electricity bill paid by residents
            $table->string('tank_has_lid', 100)->nullable();                    // Water tank has lid
            $table->string('purifying_chemical_available', 100)->nullable();    // chemical for purifying the water is available
            $table->string('development_work_in_progress', 100)->nullable();    // Development work in progress at the filtration plant
            $table->string('operator_charging_citizens', 100)->nullable();      // Operator was charging citizens money for clean water
            $table->string('illegally_occupied', 100)->nullable();              // Plant illegally occupied by an individual and not properly maintained or repaired
            $table->string('taps_on_motor_no_filtration', 100)->nullable();     // Water taps connected directly to the motor and no filtration system installed
            $table->string('electricity_theft', 100)->nullable();               // Electricity theft from the filtration plant connection
            $table->string('connection_without_meter', 100)->nullable();        // Direct electricity connection taken from a pole without a meter
            $table->string('rats_inside_plant', 100)->nullable();               // Rats found inside the plant, causing filth and highly unhygienic conditions
            $table->string('key_held_by_vendor', 100)->nullable();              // Plant key held by a street vendor who uses the space for storage and does not run the plant regularly
            $table->string('cooler_instead_of_taps', 100)->nullable();          // Water cooler installed instead of water taps
            $table->string('dogs_obstructing_citizens', 100)->nullable();       // Dogs kept around the plant by an individual, obstructing citizens from getting water
            $table->string('pfa_suspended_supply', 100)->nullable();            // Punjab Food Authority suspended water supply due to substandard water quality
            $table->string('bore_not_working', 100)->nullable();                // Plant bore not working properly and not drawing water, new bore needed as filter turns black within days
            $table->string('pipeline_leaking', 100)->nullable();                // Employee reported plant non-functional for fifteen days pipeline leaking at multiple points, ...
            $table->string('fire_damage', 100)->nullable();                     // Fire damaged the building, equipment and other material

            // Excel: Remarks, Images. Remarks are paragraphs, often in Urdu, kept with their line breaks.
            // Images are the visit's photo links, one after another.
            $table->mediumText('remarks')->nullable();
            $table->mediumText('images')->nullable();

            // Excel: Lat, Long. Blank when a coordinate can't be used. The file gives up to 8 decimal
            // places, so all 8 are kept.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Not in the file: the row's number (1, 2, 3) among the rows with the same Date, in the order of
            // their values. The file has no plant code, and many visits were entered with the same Date, so
            // numbering them keeps every row.
            $table->unsignedInteger('entry')->default(1);

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['visited_at', 'entry'], 'pspa_visits_key_unique');
            $table->index('district', 'pspa_visits_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_saaf_pani_authority');
    }
};
