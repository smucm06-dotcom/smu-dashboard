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

            $table->string('emis_code', 20);
            $table->text('sent_remarks')->nullable();
            $table->date('responded_at')->nullable();
            $table->text('response');

            $table->timestamps();

            $table->index('emis_code', 'psrp1_responses_emis_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_1_responses');
    }
};
