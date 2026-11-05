<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class MinesMineralsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Mines & Minerals Department
            $department = Department::where('code', 'MM')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Mines and Minerals Ration Card Scheme',
                    'table_name' => 'mm_mines_minerals_ration_card',
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
