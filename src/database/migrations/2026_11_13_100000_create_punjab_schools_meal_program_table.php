<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to schools in the Chief Minister's Punjab Schools Meal Program, one row per visit.
    // Every column of the visit file is kept, in its order. Blank counts and answers are written
    // "Null" in the file and are saved as empty.
    public function up(): void
    {
        Schema::create('punjab_schools_meal_program', function (Blueprint $table) {
            $table->id();

            // Excel: Timestamp, District Agent, Email, District, EMIS, School Name
            // The form's timestamp. A school can be visited twice on the same day, so the time counts too.
            $table->dateTime('visited_at');
            $table->string('district_agent', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20);
            $table->string('school_name')->nullable();

            // Whether the school was open, and on how many days a week
            $table->boolean('school_opened')->nullable();
            $table->unsignedInteger('days_open_per_week')->nullable();

            // Students and staff
            $table->unsignedInteger('students_enrolled')->nullable();
            $table->unsignedInteger('students_before_program')->nullable();
            $table->unsignedInteger('students_present')->nullable();
            $table->unsignedInteger('students_absent')->nullable();
            $table->unsignedInteger('students_receiving_milk')->nullable();
            $table->unsignedInteger('absent_yesterday_receiving_milk')->nullable();
            $table->unsignedInteger('total_staff')->nullable();
            $table->unsignedInteger('staff_absent')->nullable();

            // Milk packs received, given out, expired and in stock
            $table->unsignedInteger('milk_packs_received')->nullable();
            $table->unsignedInteger('milk_packs_distributed')->nullable();
            $table->unsignedInteger('milk_cartons_expired')->nullable();
            $table->unsignedInteger('milk_packs_in_stock')->nullable();
            $table->date('stock_expiry_date')->nullable();

            // The yes/no questions about the milk
            $table->boolean('expired_stock_received')->nullable();
            $table->boolean('milk_caused_harm')->nullable();
            $table->boolean('milk_delivered_on_time')->nullable();
            $table->unsignedInteger('delivery_gap_days')->nullable();
            $table->boolean('milk_stock_ever_received')->nullable();
            $table->boolean('milk_quality')->nullable();

            // The form's main issues, one Yes/No column each. "None" is all of them No.
            $table->boolean('poor_cleanliness')->nullable();
            $table->boolean('lack_of_disposal_facilities')->nullable();
            $table->boolean('inadequate_storage_facilities')->nullable();
            $table->boolean('large_portion_of_stock_received')->nullable();
            $table->boolean('milk_used_for_households')->nullable();

            // The field team's rating and the links to its photos and recordings. The file's
            // Additional Remarks are not kept, as the user asked.
            $table->string('overall_rating', 100)->nullable();
            $table->mediumText('photo_links')->nullable();
            $table->mediumText('media_links')->nullable();

            // "Not Found" in the file means no location.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['emis_code', 'visited_at'], 'psmp_visits_emis_visit_unique');
            $table->index('district', 'psmp_visits_district_index');
            $table->index('visited_at', 'psmp_visits_visit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_schools_meal_program');
    }
};
