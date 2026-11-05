<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class HomeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Home Department
            $department = Department::where('code', 'HOM')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Hostel facility for Women Scientist (PAFDA and PFSA)',
                    'table_name' => 'hom_hostel_women_scientists_pafda_pfsa',
                ],

                [
                    'name' => 'Establishment Of Crime Scene Units (CSUs) of PFSA at 28 Districts',
                    'table_name' => 'hom_crime_scene_units_pfsa_28_districts',
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
