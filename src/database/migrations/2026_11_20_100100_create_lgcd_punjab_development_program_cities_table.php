<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of PDP cities with each one's scope of work (the "Scope of Work" sheet). Every
    // column is kept as typed. A new list replaces the old one.
    public function up(): void
    {
        Schema::create('lgcd_punjab_development_program_cities', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'lgcd_pdp_cities_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr#, City, District, Division
            $table->unsignedInteger('list_sr')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('division', 100)->nullable();

            // Excel: Population, Sewer (km), Storm Drain (km), UGSTs, UGST Cap (MG), Disposal Station - New,
            // Disposal Station - Rehab, WWTP (MGD), Pavement (km), Machinery, Solar (KW), Street Lights.
            // Counts are whole numbers; measures keep up to 6 decimal places, as the file gives (0.047846).
            $table->unsignedInteger('population')->nullable();
            $table->decimal('sewer_km', 14, 6)->nullable();
            $table->decimal('storm_drain_km', 14, 6)->nullable();
            $table->unsignedInteger('ugsts')->nullable();
            $table->decimal('ugst_capacity_mg', 14, 6)->nullable();
            $table->unsignedInteger('disposal_stations_new')->nullable();
            $table->unsignedInteger('disposal_stations_rehab')->nullable();
            $table->decimal('wwtp_mgd', 14, 6)->nullable();
            $table->decimal('pavement_km', 14, 6)->nullable();
            $table->unsignedInteger('machinery')->nullable();
            $table->decimal('solar_kw', 14, 6)->nullable();
            $table->unsignedInteger('street_lights')->nullable();

            $table->timestamps();

            $table->index('district', 'lgcd_pdp_cities_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_punjab_development_program_cities');
    }
};
