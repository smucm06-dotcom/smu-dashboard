<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Finance Department
            $department = Department::where('code', 'FIN')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Acquisition of Land and Shifting of Utilities',
                    'table_name' => 'fin_acquisition_land_shifting_utilities',
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
