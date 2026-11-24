<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CM Punjab Honhaar Scholarship: the department's list of scholarship students (Phase-II 20K + Active
    // Students 28287, compiled). Every column is kept as typed. A new list replaces the old one, and the calls
    // are linked to the new list's students again.
    public function up(): void
    {
        Schema::create('hed_honhaar_scholarship_students', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'hed_honhaar_students_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr. No, Source File, SN in Source File
            $table->unsignedInteger('source_sr');
            $table->string('source_file')->nullable();
            $table->unsignedInteger('source_file_sn')->nullable();

            // Excel: Student District, Institute District, Institute
            $table->string('student_district', 100)->nullable();
            $table->string('institute_district', 100)->nullable();
            $table->string('institute')->nullable();

            // Excel: Name, Contact #, CNIC, Application ID
            $table->string('name')->nullable();
            $table->string('contact', 50)->nullable();
            $table->string('cnic', 50)->nullable();
            $table->string('application_id', 50)->nullable();

            // Excel: Program / Discipline, Gender, Father Name
            $table->string('program')->nullable();
            $table->string('gender', 50)->nullable();
            $table->string('father_name')->nullable();

            // Excel: Tuition Fee excl. Boarding, Lodging & Security (PKR), Total Amount (PKR). Text, because
            // the file writes some as text ("Annual System", "49500 (1st Semester)").
            $table->string('tuition_fee', 100)->nullable();
            $table->string('total_amount', 100)->nullable();

            // Excel: Status, Institute Type, Same Number in Rows
            $table->string('status', 100)->nullable();
            $table->string('institute_type', 100)->nullable();
            $table->unsignedSmallInteger('same_number_rows')->nullable();

            // Excel: Student District From, Institute District From, District (as in file), Contact (as in
            // file), Data Notes
            $table->string('student_district_from')->nullable();
            $table->string('institute_district_from')->nullable();
            $table->string('district_as_in_file')->nullable();
            $table->string('contact_as_in_file', 100)->nullable();
            $table->text('data_notes')->nullable();

            $table->timestamps();

            $table->index('student_district', 'hed_honhaar_students_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hed_honhaar_scholarship_students');
    }
};
