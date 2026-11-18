<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of schools in the meal program. A new list replaces the old one.
    public function up(): void
    {
        Schema::create('punjab_schools_meal_program_schools', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'psmp_schools_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr#, district_name, tehsil_name, markaz_name, school_name, school_emis_code,
            // school_latitude, school_longitude
            $table->unsignedInteger('list_sr')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('markaz')->nullable();
            $table->string('school_name')->nullable();
            $table->string('emis_code', 20)->unique('psmp_schools_emis_unique');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index('district', 'psmp_schools_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_schools_meal_program_schools');
    }
};
