<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CM Punjab Honhaar Scholarship: the department's lists spell one university up to four ways. The
    // "Universities" sheet of our calls file joins them: one row here for each spelling, with the single name
    // and the type it stands for. A newer sheet replaces these rows; until then they stay, even if the calls
    // file that brought them is deleted.
    public function up(): void
    {
        Schema::create('hed_honhaar_scholarship_institutions', function (Blueprint $table) {
            $table->id();

            // A spelling used in the department's list, e.g. "The University of the Punjab, Lahore".
            $table->string('spelling')->unique('hed_honhaar_institutions_spelling_unique');

            // Excel ("Universities" sheet): University / College (one spelling), Type
            $table->string('name');
            $table->string('type', 100)->nullable();

            // The calls file the sheet came from.
            $table->foreignId('initiative_file_id')->nullable();
            $table->foreign('initiative_file_id', 'hed_honhaar_institutions_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hed_honhaar_scholarship_institutions');
    }
};
