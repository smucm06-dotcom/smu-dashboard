<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The department's list of institutes. A new list replaces the old one.
    public function up(): void
    {
        Schema::create('special_education_institutes_list', function (Blueprint $table) {
            $table->id();

            // Removing the list's file removes its rows.
            $table->foreignId('initiative_file_id');
            $table->foreign('initiative_file_id', 'spe_list_file_foreign')
                ->references('id')
                ->on('initiative_files')
                ->cascadeOnDelete();

            // Excel: Code, EMIS Code, Institution Name, Zone, District, Latitude, Longitude
            $table->unsignedInteger('list_code')->nullable();
            $table->string('emis_code', 20);
            $table->string('institution_name')->nullable();
            $table->string('zone', 50)->nullable();
            $table->string('district', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // The list gives two institutes in different districts the same code (8010103 in
            // Sahiwal and in Sargodha), so a code is only unique within a district.
            $table->unique(['emis_code', 'district'], 'spe_list_emis_district_unique');
            $table->index('district', 'spe_list_district_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_education_institutes_list');
    }
};
