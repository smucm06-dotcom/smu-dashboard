<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class WomenDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Women Development Department
            $department = Department::where('code', 'WDD')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Day care Centers through Punjab Day Care Fund society',
                    'table_name' => 'wdd_day_care_centers_punjab',
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
