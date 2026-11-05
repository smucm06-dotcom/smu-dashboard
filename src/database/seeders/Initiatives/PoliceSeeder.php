<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class PoliceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Police Department
            $department = Department::where('code', 'POL')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Safe City Kacha Area',
                    'table_name' => 'pol_safe_city_kacha_area',
                ],

                [
                    'name' => 'EXPANSION OF IPNV SITES OF LAHORE',
                    'table_name' => 'pol_expansion_ipnv_sites_lahore',
                ],

                [
                    'name' => 'Chief Minister Safe Cities Program',
                    'table_name' => 'pol_cm_safe_cities_program',
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
