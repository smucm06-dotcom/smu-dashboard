<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named as in Phase 1. They come from the visit file's Enrollment sheet.
        Schema::table('public_school_reorganization_phase_2', function (Blueprint $table) {
            $table->unsignedInteger('students_at_handover')->nullable()->after('handover_status');
            $table->unsignedInteger('students_enrolled')->nullable()->after('students_at_handover');
        });
    }

    public function down(): void
    {
        Schema::table('public_school_reorganization_phase_2', function (Blueprint $table) {
            $table->dropColumn(['students_at_handover', 'students_enrolled']);
        });
    }
};
