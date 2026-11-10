<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to special education institutes, one row per institute per visit date.
    public function up(): void
    {
        Schema::create('special_education_institutes', function (Blueprint $table) {
            $table->id();

            // Excel: Sr, Date, District (the column has no heading), EMIS Code, Name of Institution
            $table->unsignedInteger('source_sr')->nullable();
            $table->date('visited_at');
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20);
            $table->string('institution_name')->nullable();

            // Staff and students. The file's percentage columns are worked out from these.
            $table->boolean('principal_present')->nullable();
            $table->unsignedInteger('total_teachers')->nullable();
            $table->unsignedInteger('present_teachers')->nullable();
            $table->unsignedInteger('total_psychologists')->nullable();
            $table->unsignedInteger('present_psychologists')->nullable();
            $table->unsignedInteger('total_admin_staff')->nullable();
            $table->unsignedInteger('present_admin_staff')->nullable();
            $table->boolean('bus_driver_post_vacant')->nullable();
            $table->unsignedInteger('students_enrolled')->nullable();
            $table->unsignedInteger('students_present')->nullable();

            // What students are given or charged
            $table->boolean('fee_charged')->nullable();
            $table->boolean('monthly_stipend')->nullable();

            // Building and cleanliness
            $table->unsignedInteger('total_classrooms')->nullable();
            $table->unsignedInteger('total_washrooms')->nullable();
            $table->boolean('separate_classroom_per_grade')->nullable();
            $table->string('washroom_cleanliness', 50)->nullable();
            $table->string('classroom_cleanliness', 50)->nullable();

            // Facilities. Transport is "Yes", "No" or "Transport service not available".
            $table->boolean('free_books')->nullable();
            $table->boolean('free_uniform')->nullable();
            $table->string('free_transport', 50)->nullable();
            $table->boolean('furniture_available')->nullable();
            $table->boolean('first_aid_available')->nullable();
            $table->boolean('clean_drinking_water_available')->nullable();
            $table->boolean('boundary_wall_available')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Worked out by the database, so they always match the counts. Blank when the total is 0.
            $table->decimal('teachers_present_percent', 6, 1)->nullable()
                ->virtualAs('ROUND(present_teachers * 100 / NULLIF(total_teachers, 0), 1)');
            $table->decimal('psychologists_present_percent', 6, 1)->nullable()
                ->virtualAs('ROUND(present_psychologists * 100 / NULLIF(total_psychologists, 0), 1)');
            $table->decimal('admin_staff_present_percent', 6, 1)->nullable()
                ->virtualAs('ROUND(present_admin_staff * 100 / NULLIF(total_admin_staff, 0), 1)');
            $table->decimal('students_present_percent', 6, 1)->nullable()
                ->virtualAs('ROUND(students_present * 100 / NULLIF(students_enrolled, 0), 1)');

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['emis_code', 'visited_at'], 'spe_inst_emis_visit_unique');
            $table->index('district', 'spe_inst_district_index');
            $table->index('visited_at', 'spe_inst_visit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_institutes');
    }
};
