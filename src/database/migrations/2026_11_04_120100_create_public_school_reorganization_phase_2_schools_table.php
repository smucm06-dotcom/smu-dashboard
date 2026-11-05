<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The department's list of schools. Contact numbers and CNICs in the file are not stored.
        Schema::create('public_school_reorganization_phase_2_schools', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'psrp2_schools_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            $table->string('emis_code', 20)->unique('psrp2_schools_emis_unique');
            $table->string('app_id', 20)->nullable();
            $table->string('school_name')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('operator')->nullable();

            // What the school had before handing over, as reported to the department.
            $table->unsignedInteger('students_enrolled')->nullable();
            $table->unsignedInteger('computers')->nullable();
            $table->unsignedInteger('washrooms')->nullable();
            $table->unsignedInteger('classrooms')->nullable();
            $table->unsignedInteger('desks_per_classroom')->nullable();
            $table->unsignedInteger('chairs_per_classroom')->nullable();
            $table->unsignedInteger('fans_per_classroom')->nullable();
            $table->boolean('clean_water_available')->nullable();
            $table->string('water_source_1', 100)->nullable();
            $table->string('water_source_2', 100)->nullable();

            $table->text('remarks')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index('district', 'psrp2_schools_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_2_schools');
    }
};
