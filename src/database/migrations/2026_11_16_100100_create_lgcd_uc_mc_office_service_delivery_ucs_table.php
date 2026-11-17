<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of union councils for UC MC Office Service Delivery. Every column is kept
    // as typed ("Null" in the file is saved as empty). A new list replaces the old one.
    public function up(): void
    {
        Schema::create('lgcd_uc_mc_office_service_delivery_ucs', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'lgcd_ucmc_ucs_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Sr. No., Division, District, Tehsil, UC Type, UC No. and Name, UC No., Standard,
            // Name of Municipal Committee
            $table->unsignedInteger('list_sr')->nullable();
            $table->string('division', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('uc_type', 100)->nullable();
            $table->string('uc_no_and_name')->nullable();
            // Text, because some rows say "Missing".
            $table->string('uc_no', 100)->nullable();
            $table->string('standard')->nullable();
            $table->string('municipal_committee')->nullable();

            $table->timestamps();

            $table->index('district', 'lgcd_ucmc_ucs_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgcd_uc_mc_office_service_delivery_ucs');
    }
};
