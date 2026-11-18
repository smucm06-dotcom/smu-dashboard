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

            // Excel: Sr, Date, District, Tehsil, Site Name
            // Sr is the row's number in the file, which is sorted by district, so it changes when a visit is
            // added in between. It is kept as typed but is not what a visit is matched on.
            $table->unsignedInteger('source_sr')->nullable();
            // The form's date and time. The earliest rows have a date only.
            $table->dateTime('visited_at');
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('site_name')->nullable();

            // Excel: Functionality of filtration plant, Total water taps, Functional water taps, Images
            // Functionality is "Functional", "Non Functional", or a sentence such as "Filtration plant inaccessible".
            $table->string('functionality')->nullable();
            $table->unsignedInteger('total_taps')->nullable();
            $table->unsignedInteger('functional_taps')->nullable();
            // The visit's photo links, one after another.
            $table->mediumText('images')->nullable();

            // The form's Yes / No questions, in the file's order, each answer as typed.
            $table->string('plant_unclean', 100)->nullable();                 // Uncleanliness of filtration plant
            $table->string('no_electricity', 100)->nullable();                // Unavailability of electricity
            $table->string('meter_disconnected', 100)->nullable();            // Electricity meter/connection disconnected
            $table->string('pending_electricity_bill', 100)->nullable();      // Pending electricity bill
            $table->string('motor_pump_non_functional', 100)->nullable();     // Non-functional motor/pump
            $table->string('non_functional_long', 100)->nullable();           // Filtration plant non-functional for long
            $table->string('operator_present', 100)->nullable();              // Operator/worker was present
            $table->string('private_company', 100)->nullable();               // Filtration plant is operating under a private company
            $table->string('found_at_location', 100)->nullable();             // Filtration plant found on the said location
            $table->string('lacks_repair_maintenance', 100)->nullable();      // Lack of repair and maintenance of filtration plant
            $table->string('tank_leakage', 100)->nullable();                  // Leakage in the water supply tank
            $table->string('broken_floor_tap_area', 100)->nullable();         // Broken floor and water tap area
            $table->string('tank_cover', 100)->nullable();                    // cover over water tank
            $table->string('contractor_left_incomplete', 100)->nullable();    // The contractor left without completing the filtration plant work
            $table->string('poor_water_quality', 100)->nullable();            // Poor quality of water
            $table->string('smell_in_water', 100)->nullable();                // Smell in water
            $table->string('non_functional_taps', 100)->nullable();           // Non-functional water taps
            $table->string('broken_taps', 100)->nullable();                   // Broken water taps
            $table->string('filter_needs_replacement', 100)->nullable();      // The filter needs to be replaced
            $table->string('filter_non_functional', 100)->nullable();         // Non-functional filter (coarse/fine)
            $table->string('room_and_filling_area_closed', 100)->nullable();  // Filtration plant's room and water filling area was closed
            $table->string('bill_paid_by_residents', 100)->nullable();        // Electricity bill payment by residents
            $table->string('operator_charging_citizens', 100)->nullable();    // Operator was charging amount from citizens for clean water
            $table->string('taps_stolen', 100)->nullable();                   // The water taps have been taken away by thieves
            $table->string('door_stolen', 100)->nullable();                   // The filtration plant door has been stolen
            $table->string('shut_down_early', 100)->nullable();               // The filtration plant is shut down early instead of operating according to its scheduled operating hours.
            $table->string('operator_unpaid', 100)->nullable();               // The plant operator has not received a salary for several months
            $table->string('dilapidated_building', 100)->nullable();          // dilapidated building of filtration plant
            $table->string('illegally_occupied', 100)->nullable();            // The filtration plant has been illegally occupied by an individual, ...
            $table->string('dogs_around_plant', 100)->nullable();             // Someone has kept several dogs around the filtration plant, ...
            $table->string('rats_inside_plant', 100)->nullable();             // Rats have been found inside the filtration plant setup, ...
            $table->string('non_operational_15_days', 100)->nullable();       // The filtration plant has remained non-operational for the past 15 days. ...
            $table->string('key_held_by_vendor', 100)->nullable();            // The filtration plant key is held by a street vendor (Chabri Shop), ...
            $table->string('taps_on_motor_no_filtration', 100)->nullable();   // Water taps are directly connected to the motor, and no filtration system is installed.
            $table->string('pfa_suspended_supply', 100)->nullable();          // The Punjab Food Authority has suspended the water supply due to substandard water quality.
            $table->string('bore_not_working', 100)->nullable();              // The filtration plant bore is not functioning properly ...
            $table->string('electricity_theft', 100)->nullable();             // Theft of Electricity from the Filtration Plant's Power Connection
            $table->string('direct_pole_connection', 100)->nullable();        // Electricity theft has been committed at the filtration plant by taking a direct connection from the electric pole.
            $table->string('cooler_instead_of_taps', 100)->nullable();        // Water cooler has been installed at this filtration plant instead of water taps

            // Excel: lat, long. Blank when a coordinate can't be used. Up to 8 decimal places are kept.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Not in the file: the row's number (1, 2, 3) among the rows with the same Date, in the order of
            // their values. Many visits were entered with the same Date, and Sr changes as the file grows,
            // so numbering them keeps every row.
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
