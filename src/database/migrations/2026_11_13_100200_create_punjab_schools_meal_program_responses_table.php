<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's response to our letters, one row per school in a letter.
    public function up(): void
    {
        Schema::create('punjab_schools_meal_program_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'psmp_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr. No., District, EMIS, SMU Observation, SMU letter data, Response,
            // Department Response date. One observation and response can cover several schools
            // (merged cells), and each of those schools' rows gets them.
            $table->unsignedInteger('source_sr')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('emis_code', 20)->nullable();
            // Observations and responses run long; these hold the longest an Excel cell can be.
            $table->mediumText('smu_observation')->nullable();
            // Rows whose observation is one merged cell in the sheet share this number: the sheet row
            // the cell starts on. Blank when the row's observation is typed on its own.
            $table->unsignedInteger('observation_block')->nullable();
            // "SMU letter data" in the file: the date of our letter to the department.
            $table->date('letter_date')->nullable();
            $table->mediumText('response')->nullable();
            $table->date('responded_at')->nullable();

            $table->timestamps();

            $table->index('emis_code', 'psmp_responses_emis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('punjab_schools_meal_program_responses');
    }
};
