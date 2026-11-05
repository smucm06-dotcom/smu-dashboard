<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_school_reorganization_phase_2', function (Blueprint $table) {
            $table->id();

            // Excel: SR, Date, District, EMIS Code
            $table->unsignedInteger('source_sr')->nullable();
            $table->date('visited_at');
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20);

            // Handover and furniture
            $table->boolean('handover_status')->nullable();
            $table->boolean('furniture_available')->nullable();

            // Building and furniture counts
            $table->unsignedInteger('total_washrooms')->nullable();
            $table->unsignedInteger('total_classrooms')->nullable();
            $table->unsignedInteger('total_desks')->nullable();
            $table->unsignedInteger('total_room_chairs')->nullable();
            $table->unsignedInteger('total_air_fans')->nullable();

            // Condition and cleanliness
            $table->string('classroom_condition', 50)->nullable();
            $table->string('washroom_cleanliness', 50)->nullable();
            $table->string('school_cleanliness', 50)->nullable();

            // Facilities and safety
            $table->boolean('clean_drinking_water_available')->nullable();
            $table->boolean('boundary_wall_available')->nullable();
            $table->unsignedInteger('dangerous_classrooms')->nullable();

            // School identity and coordinates
            $table->string('school_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // One record per school per visit date.
            $table->unique(
                ['emis_code', 'visited_at'],
                'psrp2_emis_visit_unique'
            );

            $table->index('district', 'psrp2_district_index');
            $table->index('visited_at', 'psrp2_visit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_2');
    }
};
