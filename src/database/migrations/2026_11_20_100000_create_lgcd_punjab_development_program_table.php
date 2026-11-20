<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to the work sites of the Chief Minister Punjab Development Program (PDP), one row per row
    // of the visit file. Every column of the file is kept, in its order.
    public function up(): void
    {
        Schema::create('lgcd_punjab_development_program', function (Blueprint $table) {
            $table->id();

            // Excel: Report DATE. The file gives the date of the SMU report the visit went into, and no
            // other date, so a visit is dated by its report.
            $table->date('visited_at');

            // Excel: District, Cities. As typed ("TTS" for Toba Tek Singh). Part of what a re-uploaded row is
            // matched on, so a blank is kept as empty text rather than NULL.
            $table->string('district', 100)->default('');
            $table->string('city', 100)->default('');

            // The checks, as typed: Yes or No. Some ask whether a problem was seen ("Manholes without cover"),
            // others whether something was done right ("Measures taken to control dust").
            $table->string('manholes_without_cover', 100)->nullable();
            $table->string('workers_in_uniform', 100)->nullable();
            $table->string('workers_using_safety_equipment', 100)->nullable();
            $table->string('safety_boards_installed', 100)->nullable();
            $table->string('construction_waste_removed', 100)->nullable();
            $table->string('dust_control_measures', 100)->nullable();
            $table->string('excavation_sites_secured', 100)->nullable();
            $table->string('materials_organized', 100)->nullable();
            $table->string('slow_or_halted_work', 100)->nullable();
            $table->string('trees_protected', 100)->nullable();
            $table->string('traffic_managed', 100)->nullable();

            // Excel: Image Path, Videos. Every link, as typed, separated by commas. A cell can hold dozens of
            // links; these hold the longest an Excel cell can be.
            $table->mediumText('images')->nullable();
            $table->mediumText('videos')->nullable();

            // Excel: Latitude, Longitude. Kept as typed, even a number with its decimal point missing
            // (31165614), so nothing in the file is lost. The page uses only a location inside Punjab, and
            // the records table's data checks list the others.
            $table->decimal('latitude', 18, 8)->nullable();
            $table->decimal('longitude', 18, 8)->nullable();

            // Excel: category, such as underground_storage_tank. Any text, as more categories will come.
            $table->string('category')->default('');

            // Not in the file: the row's number (1, 2, 3) among rows with the same report date, district,
            // city and category, in the order of their values, so two such rows are both kept.
            $table->unsignedInteger('entry')->default(1);

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['visited_at', 'district', 'city', 'category', 'entry'], 'lgcd_pdp_visits_key_unique');
            $table->index('district', 'lgcd_pdp_visits_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_punjab_development_program');
    }
};
