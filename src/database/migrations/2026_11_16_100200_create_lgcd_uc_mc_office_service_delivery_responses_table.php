<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's response to our reports, one row per district in a letter. Every column is kept.
    public function up(): void
    {
        Schema::create('lgcd_uc_mc_office_service_delivery_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'lgcd_ucmc_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr No, SMU Report Date, Letter Date, Division, District, Issues Identified,
            // Cases Reported, Cases Admitted, Cases Denied, Cases Justified, Cases Unaddressed, Action Taken
            $table->unsignedInteger('source_sr')->nullable();
            $table->date('smu_report_date')->nullable();
            $table->date('letter_date')->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            // Issues and actions run long; these hold the longest an Excel cell can be.
            $table->mediumText('issues_identified')->nullable();
            $table->unsignedInteger('cases_reported')->nullable();
            $table->unsignedInteger('cases_admitted')->nullable();
            $table->unsignedInteger('cases_denied')->nullable();
            $table->unsignedInteger('cases_justified')->nullable();
            $table->unsignedInteger('cases_unaddressed')->nullable();
            $table->mediumText('action_taken')->nullable();

            $table->timestamps();

            $table->index('district', 'lgcd_ucmc_responses_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_uc_mc_office_service_delivery_responses');
    }
};
