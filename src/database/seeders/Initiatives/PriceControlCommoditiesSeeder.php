<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class PriceControlCommoditiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Price Control & Commodities Management
            $department = Department::where('code', 'PCCM')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Chief Minister's Program for Sahulat Bazaars in Punjab",
                    'table_name' => 'pccm_cm_sahulat_bazaars_punjab',
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
