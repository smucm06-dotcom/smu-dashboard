<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of schools with approved additional classrooms. A new list replaces the old one.
    public function up(): void
    {
        Schema::create('additional_classrooms_schools', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'ac_schools_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr_No, EMIS, School Name, Level, Zone, District, Division, Tehsil,
            // Class Room Count, Lat, Lng
            $table->unsignedInteger('list_sr')->nullable();
            $table->string('emis_code', 20)->unique('ac_schools_emis_unique');
            $table->string('school_name')->nullable();
            $table->string('level', 50)->nullable();
            $table->string('zone', 50)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            // "Class Room Count": the classrooms approved for the school.
            $table->unsignedInteger('approved_classrooms')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index('district', 'ac_schools_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_classrooms_schools');
    }
};
