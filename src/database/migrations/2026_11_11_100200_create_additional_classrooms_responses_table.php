<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's response to our reports, one row per remark in a report.
    public function up(): void
    {
        Schema::create('additional_classrooms_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'ac_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr.No., District, EMIS Code, SMU Visit Date, Analysis Report Remark,
            // Department Response Date, Department Response. A report's Sr, district, EMIS code
            // and dates are written once (merged cells) and fill each of its rows.
            $table->unsignedInteger('source_sr')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20)->nullable();
            $table->date('visit_date')->nullable();
            // Remarks and replies run long; these hold the longest an Excel cell can be.
            $table->mediumText('sent_remarks')->nullable();
            $table->date('responded_at')->nullable();
            // Blank when the department did not answer that remark.
            $table->mediumText('response')->nullable();

            $table->timestamps();

            $table->index('emis_code', 'ac_responses_emis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_classrooms_responses');
    }
};
