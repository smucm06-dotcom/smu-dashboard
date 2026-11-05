<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class LocalGovernmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Local Government & Community Development
            $department = Department::where('code', 'LGCD')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister Lahore Development Program (MCL, 6 Zones)',
                    'table_name' => 'lgcd_lahore_development_mcl_6_zones',
                ],

                [
                    'name' => 'Chief Minister Lahore Development Program',
                    'table_name' => 'lgcd_lahore_development_program',
                ],

                [
                    'name' => 'Chief Minister Sustainable Developments Goals Program',
                    'table_name' => 'lgcd_sustainable_development_goals',
                ],

                [
                    'name' => 'Chief Minister Lahore Development Program (MCL, 3 Zones)',
                    'table_name' => 'lgcd_lahore_development_mcl_3_zones',
                ],

                [
                    'name' => 'Chief Minister LAHR Program',
                    'table_name' => 'lgcd_lahr_program',
                ],

                [
                    'name' => 'Chief Minister LAHR Programme-II',
                    'table_name' => 'lgcd_lahr_programme_phase_2',
                ],

                [
                    'name' => 'Chief Minister Punjab Development Program',
                    'table_name' => 'lgcd_punjab_development_program',
                ],

                [
                    'name' => 'Chief Minister Rural Sanitation Programe',
                    'table_name' => 'lgcd_rural_sanitation_program',
                ],

                [
                    'name' => 'Chief Minister Murree Development Program',
                    'table_name' => 'lgcd_murree_development_program',
                ],

                [
                    'name' => 'Murree Development Program -II',
                    'table_name' => 'lgcd_murree_development_phase_2',
                ],

                [
                    'name' => 'CM’s Program for Establishment of Model Village (Misali Gaon) by Improved Municipal Services',
                    'table_name' => 'lgcd_model_village_municipal_services',
                ],

                [
                    'name' => 'Chief Minister Program for Model Village (Misali Gaon)',
                    'table_name' => 'lgcd_model_village_misali_gaon',
                ],

                [
                    'name' => 'Punjab Cities Beautification Schemes',
                    'table_name' => 'lgcd_punjab_cities_beautification',
                ],

                [
                    'name' => 'Chief Minister Model Carts Bazaar',
                    'table_name' => 'lgcd_model_carts_bazaar',
                ],

                [
                    'name' => 'Suthra Punjab',
                    'table_name' => 'lgcd_suthra_punjab',
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
