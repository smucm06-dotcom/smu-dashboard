<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's responses to our reports, one row per SMU report: the report's text and the
    // department's answer, both in full. Every column is kept.
    public function up(): void
    {
        Schema::create('lgcd_punjab_development_program_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'lgcd_pdp_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Response Letter Date, SMU Report Date, SMU Report, Dept. Response
            $table->date('response_letter_date')->nullable();
            $table->date('smu_report_date')->nullable();
            // The report and the answer run long, city by city on their own lines; these hold the longest
            // an Excel cell can be, with the line breaks kept.
            $table->mediumText('smu_report')->nullable();
            $table->mediumText('dept_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_punjab_development_program_responses');
    }
};
