<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's response to our reports, one row per plant in a report. Every column is kept.
    public function up(): void
    {
        Schema::create('punjab_saaf_pani_authority_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'pspa_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr. No., Site Name, CODE, District, Overall Issues, Action Taken, SMU Report Date,
            // Department Response Date, Non-functional, Department Response, NO FOUND, Department Response
            $table->unsignedInteger('source_sr')->nullable();
            $table->string('site_name')->nullable();
            // The plant's code in the department list. Blank where the file shows Excel's "#N/A".
            $table->string('plant_code', 20)->nullable();
            $table->string('district', 100)->nullable();
            // Issues and actions run long; these hold the longest an Excel cell can be.
            $table->mediumText('overall_issues')->nullable();
            $table->mediumText('action_taken')->nullable();
            // As typed: often several dates in one cell ("01.01.2026 02.01.2026 03.01.2026").
            $table->string('smu_report_date')->nullable();
            $table->date('response_date')->nullable();
            // Was the plant non-functional, and the department's response on that (the first "Department
            // Response" column).
            $table->string('non_functional', 100)->nullable();
            $table->mediumText('non_functional_response')->nullable();
            // Was the plant not found ("NO FOUND"), and the department's response on that (the second
            // "Department Response" column).
            $table->string('not_found', 100)->nullable();
            $table->mediumText('not_found_response')->nullable();

            $table->timestamps();

            $table->index('plant_code', 'pspa_responses_plant_code_index');
            $table->index('district', 'pspa_responses_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_saaf_pani_authority_responses');
    }
};
