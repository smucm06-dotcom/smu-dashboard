<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Official names and locations from the visit file's EMIS Lookup sheet. The department's
        // list mostly holds addresses, so schools not visited yet are named from here.
        Schema::create('public_school_reorganization_phase_2_school_names', function (Blueprint $table) {
            $table->id();
            $table->string('emis_code', 20)->unique('psrp2_school_names_emis_unique');
            $table->string('school_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_school_reorganization_phase_2_school_names');
    }
};
