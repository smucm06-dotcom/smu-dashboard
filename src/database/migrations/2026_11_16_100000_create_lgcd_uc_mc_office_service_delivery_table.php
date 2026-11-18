<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Our visits to UC and MC offices for UC MC Office Service Delivery, one row per row of the visit
    // file. Every column of the file is kept, in its order.
    public function up(): void
    {
        Schema::create('lgcd_uc_mc_office_service_delivery', function (Blueprint $table) {
            $table->id();

            // Excel: Timestamp, Email, Agent, District, Tehsil, Name, UC, MC
            // The form's timestamp. Older rows have a date only, newer ones the time too.
            $table->dateTime('visited_at');
            $table->string('email');
            $table->string('agent', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            // The office's name as typed (a UC's name, often in Urdu, or an MC's code).
            $table->string('name')->nullable();
            // A visit is to a UC or to an MC: one of these is given. UC can hold several UCs ("98,99"),
            // so both are kept as typed.
            $table->string('uc_number', 100)->nullable();
            $table->string('mc_name', 100)->nullable();

            // The answers, as typed: Yes or No, and "Not applicable" for the schedule. Blank ("Null" in the
            // file) when not asked: the office was closed, or the question was added to the form later.
            $table->string('office_open')->nullable();
            $table->string('incharge_present')->nullable();
            $table->string('service_delivery')->nullable();
            $table->string('schedule_displayed')->nullable();
            $table->string('schedule_followed')->nullable();
            // "Unauthorizedprivate person" and "Delipidated building" in the file
            $table->string('unauthorized_private_person')->nullable();
            $table->string('dilapidated_building')->nullable();
            $table->string('office_cleanliness')->nullable();
            $table->string('office_board_displayed')->nullable();

            // Excel: Latitude, Longitude. Blank when the office's location isn't known.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Not in the file: the row's number (1, 2, 3) among the rows with the same timestamp and
            // email, in the order of their values. Old rows have a date only, and one form can cover two
            // UCs, so several rows can share both. Numbering them keeps every row.
            $table->unsignedInteger('entry')->default(1);

            $table->timestamps();

            // A re-uploaded visit updates its row instead of adding another.
            $table->unique(['visited_at', 'email', 'entry'], 'lgcd_ucmc_visits_key_unique');
            $table->index('district', 'lgcd_ucmc_visits_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_uc_mc_office_service_delivery');
    }
};
