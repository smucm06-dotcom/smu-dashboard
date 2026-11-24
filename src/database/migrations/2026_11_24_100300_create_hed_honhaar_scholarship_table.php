<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CM Punjab Honhaar Scholarship: every row of the call center's feedback files, kept as typed. Each file
    // adds its rows to the earlier files' (its Sr. No starts again at 1), and nothing is ever left out. The
    // last columns are worked out when the calls are linked to the department's list; they decide whether a
    // call counts on the page.
    public function up(): void
    {
        Schema::create('hed_honhaar_scholarship', function (Blueprint $table) {
            $table->id();

            // The calls file the row came from. Removing the file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'hed_honhaar_calls_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr. No, Call Date (blank for the Master file's calls)
            $table->unsignedInteger('source_sr');
            $table->date('call_date')->nullable();

            // Excel: Name of Applicant, CNIC, Contact No, Alt Contact No, Contact Valid?
            $table->string('applicant_name')->nullable();
            $table->string('cnic', 50)->nullable();
            $table->string('contact_no', 50)->nullable();
            $table->string('alt_contact_no', 50)->nullable();
            $table->string('contact_valid', 50)->nullable();

            // Excel: Degree Program, Amount (Fees), Institute Name, Division, District
            $table->string('degree_program')->nullable();
            $table->unsignedBigInteger('amount_fees')->nullable();
            $table->string('institute_name')->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();

            // Excel: Call Status and the call's questions, as typed (Yes, No ...)
            $table->string('call_status', 100)->nullable();
            $table->string('first_cheque_received', 100)->nullable();
            $table->string('receiving_scholarship', 100)->nullable();
            $table->string('bribe_paid', 100)->nullable();
            $table->string('year_of_degree', 100)->nullable();
            $table->string('satisfied', 100)->nullable();

            // Excel: Remarks, Any Other Issue
            $table->text('remarks')->nullable();
            $table->mediumText('any_other_issue')->nullable();

            // Every value but Sr. No, so a row that repeats an earlier one can be found.
            $table->char('content_hash', 40);

            // Worked out when the calls are linked to the department's list.
            // The student called, found by CNIC, else phone, else alt phone, and how.
            $table->foreignId('student_id')->nullable();
            $table->foreign('student_id', 'hed_honhaar_calls_student_foreign')
                ->references('id')
                ->on('hed_honhaar_scholarship_students')
                ->nullOnDelete();
            $table->string('matched_by', 20)->nullable();

            // Set when the row is part of a run of rows that repeats earlier rows in the same order (a file
            // pasted in twice): the earlier row it repeats. Such rows are kept but not counted.
            $table->foreignId('copy_of_id')->nullable();
            $table->foreign('copy_of_id', 'hed_honhaar_calls_copy_foreign')
                ->references('id')
                ->on('hed_honhaar_scholarship')
                ->nullOnDelete();

            // The district the call counts in: the student's district in the department's list, else the
            // District written in the calls file.
            $table->string('home_district', 100)->nullable();

            $table->timestamps();

            $table->unique(['initiative_file_id', 'source_sr'], 'hed_honhaar_calls_file_sr_unique');
            $table->index('call_date', 'hed_honhaar_calls_date_index');
            $table->index('home_district', 'hed_honhaar_calls_district_index');
            $table->index('content_hash', 'hed_honhaar_calls_hash_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hed_honhaar_scholarship');
    }
};
