<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_school_reorganization_phase_1', function (Blueprint $table) {
            $table->id();

            // Excel: SR, Timestap, District, EMIS
            $table->unsignedInteger('source_sr')->nullable();
            $table->dateTime('visited_at', 6);
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20);

            // Salary and council
            $table->boolean('staff_salary_status')->nullable();
            $table->boolean('pts_council_established')->nullable();
            $table->unsignedInteger('council_meetings_count')->nullable();

            // Students
            $table->boolean('students_charged_fees')->nullable();
            $table->unsignedInteger('students_enrolled')->nullable();
            $table->unsignedInteger('students_at_handover')->nullable();
            $table->unsignedInteger('students_present')->nullable();

            // Teachers and building
            $table->unsignedInteger('total_teachers')->nullable();
            $table->unsignedInteger('total_classrooms')->nullable();
            $table->unsignedInteger('total_washrooms')->nullable();
            $table->unsignedInteger('dangerous_classrooms')->nullable();

            $table->boolean('handover_status')->nullable();
            $table->unsignedInteger('teachers_present')->nullable();
            $table->boolean('boundary_wall_available')->nullable();

            // Condition and cleanliness
            $table->string('classroom_condition', 50)->nullable();
            $table->string('washroom_cleanliness', 50)->nullable();
            $table->string('school_cleanliness', 50)->nullable();

            // Facilities
            $table->boolean('clean_drinking_water_available')->nullable();
            $table->boolean('free_textbooks_available')->nullable();
            $table->boolean('one_grade_per_classroom')->nullable();
            $table->boolean('furniture_available')->nullable();
            $table->boolean('first_aid_available')->nullable();

            // Teacher training
            $table->unsignedInteger('teachers_enrolled_pef_training')
                ->nullable();

            $table->unsignedInteger('teachers_nominated_pef_cpdp')
                ->nullable();

            // School identity and coordinates
            $table->string('school_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // A school can have multiple visits.
            // The same school and visit timestamp cannot be duplicated.
            $table->unique(
                ['emis_code', 'visited_at'],
                'psrp1_emis_visit_unique'
            );

            $table->index('district', 'psrp1_district_index');
            $table->index('visited_at', 'psrp1_visit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_1');
    }
};
