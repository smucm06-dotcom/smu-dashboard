<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The department's list of schools. Contact numbers and CNICs in the file are not stored.
        Schema::create('public_school_reorganization_phase_1_schools', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'psrp1_schools_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            $table->string('emis_code', 20)->unique('psrp1_schools_emis_unique');
            $table->string('school_name')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('tehsil', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('operator')->nullable();
            $table->string('handover_status', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            $table->index('district', 'psrp1_schools_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_1_schools');
    }
};
