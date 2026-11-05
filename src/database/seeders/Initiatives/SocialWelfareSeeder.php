<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class SocialWelfareSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Social Welfare Department
            $department = Department::where('code', 'SW')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Family Support and Mediation Complexes (FSMCs) "Sukh Da Vehra" in Punjab',
                    'table_name' => 'sw_family_support_mediation_sukh_da_vehra',
                ],

                [
                    'name' => 'Up-gradation of Sanatzars across Punjab',
                    'table_name' => 'sw_upgradation_sanatzars_punjab',
                ],

                [
                    'name' => 'Establishment of Old Age Home (Aafiat) at Gujranwala, DG Khan, Lahore and Operationalization of Old Age Homes at Muzaffargarh, Sargodha and Bahawalpur',
                    'table_name' => 'sw_old_age_homes_aafiat',
                ],

                [
                    'name' => 'Punjab Dhee Rani Programme',
                    'table_name' => 'sw_punjab_dhee_rani_programme',
                ],

                [
                    'name' => 'Assistive Devices and Wheel Chair (ADWC) Program',
                    'table_name' => 'sw_assistive_devices_wheelchair_program',
                ],

                [
                    'name' => 'Chief Minister’s Himmat Card Program',
                    'table_name' => 'sw_cm_himmat_card_program',
                ],

                [
                    'name' => 'Flood Relief 2025',
                    'table_name' => 'sw_flood_relief_2025',
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
