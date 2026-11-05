<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class HumanRightsMinorityAffairsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Human Rights & Minority Affairs Department
            $department = Department::where('code', 'HRMA')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Chief Minister's Minority Development Program",
                    'table_name' => 'hrma_minority_development_program',
                ],

                [
                    'name' => "Chief Minister's Minority Development Programme (Phase-II)",
                    'table_name' => 'hrma_minority_development_phase_2',
                ],

                [
                    'name' => "Chief Minister’s Minority Card Scheme",
                    'table_name' => 'hrma_minority_card_scheme',
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
