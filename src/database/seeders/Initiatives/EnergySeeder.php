<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class EnergySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Energy Department
            $department = Department::where('code', 'ENE')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Chief Minister's Punjab Free Solar Panel Scheme",
                    'table_name' => 'ene_punjab_free_solar_panel_scheme',
                ],

                [
                    'name' => 'Chief Minister Initiative for Promotion of Renewable Energy Technologies',
                    'table_name' => 'ene_renewable_energy_technologies',
                ],

                [
                    'name' => 'Chief Minister Fund for Indigenization of Solar Power Equipment (PC-II)',
                    'table_name' => 'ene_solar_power_equipment_indigenization_pc2',
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
