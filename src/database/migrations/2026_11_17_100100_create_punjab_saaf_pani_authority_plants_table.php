<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of water filtration plants for Punjab Saaf Pani Authority (PSPA). Every
    // column is kept as typed. A new list replaces the old one.
    public function up(): void
    {
        Schema::create('punjab_saaf_pani_authority_plants', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'pspa_plants_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Code, Division, Region, District, Tehsil, Name of Site, Latitude, Longitude
            // The plant's code (N1, C4430, S223), which the response file's CODE refers to.
            $table->string('code', 20)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('site_name')->nullable();
            // Blank when the file gives none, or one that can't be a coordinate (such as "74.1932.79").
            // The file gives up to 8 decimal places, so all 8 are kept.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->timestamps();

            $table->index('code', 'pspa_plants_code_index');
            $table->index('district', 'pspa_plants_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_saaf_pani_authority_plants');
    }
};
