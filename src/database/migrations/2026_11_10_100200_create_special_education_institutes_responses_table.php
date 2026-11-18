<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's reply to each issue in our reports, one row per issue.
    public function up(): void
    {
        Schema::create('special_education_institutes_responses', function (Blueprint $table) {
            $table->id();

            // Removing a response file removes the replies that came from it.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'spe_responses_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // The file names each institute but has no EMIS code, so it is found from the name.
            // Blank until an institute with that name has been uploaded in the list or a visit.
            $table->string('emis_code', 20)->nullable();

            // Excel: Sr.No., District, Institute Name, Overall Key Issues, Department Response,
            // SMU Report Date, Department Response Date
            $table->unsignedInteger('source_sr')->nullable();
            $table->string('district', 100)->nullable();
            // The file leaves the name out for some institutes. Their replies keep a blank name
            // rather than being added to the institute above them.
            $table->string('institution_name')->nullable();
            // Replies run to a few thousand characters. These hold the longest an Excel cell can be.
            $table->mediumText('issue')->nullable();
            $table->mediumText('response')->nullable();
            $table->date('reported_at')->nullable();
            $table->date('responded_at')->nullable();

            $table->timestamps();

            $table->index('emis_code', 'spe_responses_emis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_institutes_responses');
    }
};
