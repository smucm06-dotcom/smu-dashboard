<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class EnvironmentClimateChangeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Environment & Climate Change Department
            $department = Department::where('code', 'ECC')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'EPA: Up gradation, Restructuring, Revamping and Automation',
                    'table_name' => 'ecc_epa_upgradation_restructuring',
                ],

                [
                    'name' => 'CM Punjab Green Credit Program',
                    'table_name' => 'ecc_cm_punjab_green_credit_program',
                ],

                [
                    'name' => 'Punjab Smog Mitigation And Response Initiative – Air Safe',
                    'table_name' => 'ecc_punjab_smog_mitigation_air_safe',
                ],

                [
                    'name' => 'CM Punjab Climate Leadership Development Internship Program (Phase-II)',
                    'table_name' => 'ecc_climate_leadership_internship_phase_2',
                ],

            ];

            // Insert or update without duplication
            foreach ($initiatives as $initiative) {

                Initiative::updateOrCreate(
                    [
                        'table_name' => $initiative['table_name'],
                    ],
                    [
                        'department_id' => $department->id,
                        'name' => $initiative['name'],
                        'is_active' => true,
                    ]
                );

            }

        });
    }
}
