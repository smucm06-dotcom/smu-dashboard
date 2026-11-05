<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class LabourHumanResourceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Labour & Human Resource Department
            $department = Department::where('code', 'LHR')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Maryam Nawaz Social Security Ration Card for the Secured Workers',
                    'table_name' => 'lhr_social_security_ration_card',
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
