<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class HousingUrbanDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Housing & Urban Development Department
            $department = Department::where('code', 'HUDPHE')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister Lahore Development Program (WASA, 9 Zones)',
                    'table_name' => 'hudphe_lahore_development_wasa_9_zones',
                ],

                [
                    'name' => "Chief Minister's Saaf Pani Program across Punjab",
                    'table_name' => 'hudphe_saaf_pani_across_punjab',
                ],

                [
                    'name' => 'Chief Minister WATSAN Program',
                    'table_name' => 'hudphe_watsan_program',
                ],

                [
                    'name' => 'Chief Minister Lahore Sanitation Package',
                    'table_name' => 'hudphe_lahore_sanitation_package',
                ],

                [
                    'name' => 'Chief Minister Saaf Pani Program',
                    'table_name' => 'hudphe_saaf_pani_program',
                ],

                [
                    'name' => 'Apni Chhat Apna Ghar Program',
                    'table_name' => 'hudphe_apni_chhat_apna_ghar',
                ],

                [
                    'name' => 'Apni Zameen Apna Ghar',
                    'table_name' => 'hudphe_apni_zameen_apna_ghar',
                ],

                [
                    'name' => 'Chief Minister Punjab Development Program – WASA component',
                    'table_name' => 'hudphe_punjab_development_wasa',
                ],

            ];

            // Insert or update initiatives without duplication
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
