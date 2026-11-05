<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class PunjabSafeCitiesAuthoritySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Punjab Safe Cities Authority
            $department = Department::where('code', 'PSCA')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Punjab Smart Safe Cities Program',
                    'table_name' => 'psca_punjab_smart_safe_cities_program',
                ],

                [
                    'name' => 'CM Maryam Nawaz Free Wifi Initiative',
                    'table_name' => 'psca_cm_maryam_nawaz_free_wifi',
                ],

                [
                    'name' => 'Virtual Women Police Station',
                    'table_name' => 'psca_virtual_women_police_station',
                ],

                [
                    'name' => 'Virtual Center for Child Safety',
                    'table_name' => 'psca_virtual_center_child_safety',
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
