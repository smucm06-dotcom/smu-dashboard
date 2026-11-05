<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Visits saved before the upload learned to correct these. See PsrpPhase2Import.
    public function up(): void
    {
        DB::table('public_school_reorganization_phase_2')
            ->where('visited_at', '<', '2025-01-01')
            ->update(['visited_at' => DB::raw('DATE_ADD(visited_at, INTERVAL 1 YEAR)')]);

        DB::table('public_school_reorganization_phase_2')
            ->where('emis_code', '34320282')
            ->update(['district' => 'Sialkot']);
    }

    public function down(): void
    {
        // The earlier values were typing slips, so there is nothing worth restoring.
    }
};
