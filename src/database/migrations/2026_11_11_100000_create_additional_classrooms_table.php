<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to schools where additional classrooms are being built, one row per school per visit date.
    public function up(): void
    {
        Schema::create('additional_classrooms', function (Blueprint $table) {
            $table->id();

            // Excel: SR, Date, District, School EMIS Code, School Level, School Type (Boys/Girls/Co-educational)
            $table->unsignedInteger('source_sr')->nullable();
            $table->date('visited_at');
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20);
            $table->string('school_level', 50)->nullable();
            $table->string('school_type', 50)->nullable();

            // Students and staff. Blank where the file says "Null" (visits in the summer vacation).
            $table->unsignedInteger('students_enrolled')->nullable();
            $table->unsignedInteger('students_present')->nullable();
            $table->unsignedInteger('total_teachers')->nullable();
            $table->unsignedInteger('teachers_present')->nullable();
            $table->unsignedInteger('non_teaching_staff')->nullable();
            $table->unsignedInteger('non_teaching_staff_present')->nullable();

            // The school's existing rooms, then the approved new classrooms.
            $table->unsignedInteger('total_classrooms')->nullable();
            $table->unsignedInteger('total_washrooms')->nullable();
            $table->unsignedInteger('approved_classrooms')->nullable();
            $table->unsignedInteger('constructed_classrooms')->nullable();
            $table->unsignedInteger('under_construction_classrooms')->nullable();

            // Construction, as written in the file. Free text can run long, so it gets room.
            $table->string('construction_status', 50)->nullable();
            $table->mediumText('not_started_reason')->nullable();
            $table->boolean('obstruction')->nullable();
            $table->mediumText('obstruction_type')->nullable();
            $table->mediumText('construction_stage')->nullable();
            $table->boolean('labour_present')->nullable();
            $table->string('inspection_frequency')->nullable();
            $table->mediumText('new_classroom_facilities')->nullable();

            // The school's condition
            $table->string('classroom_condition', 50)->nullable();
            $table->string('washroom_condition', 50)->nullable();
            $table->string('overall_cleanliness', 50)->nullable();
            $table->boolean('clean_drinking_water')->nullable();
            $table->boolean('boundary_wall_available')->nullable();
            $table->boolean('construction_quality_satisfactory')->nullable();

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['emis_code', 'visited_at'], 'ac_visits_emis_visit_unique');
            $table->index('district', 'ac_visits_district_index');
            $table->index('visited_at', 'ac_visits_visit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_classrooms');
    }
};
