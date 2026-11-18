<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replies read from the department's response files.
        Schema::create('public_school_reorganization_phase_1_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'psrp1_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr., EMIS, District, School Name, SMU Audit Report Date, PEF Response Date,
            // Remarks of SMU, Response of PEF. Every column is kept, in the file's order.
            $table->unsignedInteger('source_sr')->nullable();
            $table->string('emis_code', 20);
            // District and school name as written in the file.
            $table->string('district', 100)->nullable();
            $table->string('school_name')->nullable();
            // The date of SMU's audit report. Earlier files gave the SMU visit date here.
            $table->date('audit_report_date')->nullable();
            $table->date('responded_at')->nullable();
            // Remarks and replies run long; these hold the longest an Excel cell can be.
            $table->mediumText('sent_remarks')->nullable();
            // Empty when the department left the reply blank.
            $table->mediumText('response');

            $table->timestamps();

            $table->index('emis_code', 'psrp1_responses_emis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_1_responses');
    }
};
