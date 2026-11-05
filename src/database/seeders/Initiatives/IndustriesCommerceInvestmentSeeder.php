<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class IndustriesCommerceInvestmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Industries, Commerce & Investment Department
            $department = Department::where('code', 'IND')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Garment cities in Punjab',
                    'table_name' => 'ind_garment_cities_punjab',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar',
                    'table_name' => 'ind_cm_punjab_asaan_karobar',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar Finance- Center',
                    'table_name' => 'ind_asaan_karobar_finance_center',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar Finance-South',
                    'table_name' => 'ind_asaan_karobar_finance_south',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar Finance- North',
                    'table_name' => 'ind_asaan_karobar_finance_north',
                ],

                [
                    'name' => 'CM Punjab Asaan Export Finance for SMEs',
                    'table_name' => 'ind_asaan_export_finance_smes',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar finance',
                    'table_name' => 'ind_asaan_karobar_finance',
                ],

                [
                    'name' => 'CM Punjab Asaan Karobar card',
                    'table_name' => 'ind_asaan_karobar_card',
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
