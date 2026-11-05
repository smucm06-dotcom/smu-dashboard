<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class LivestockDairyDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Livestock & Dairy Development Department
            $department = Department::where('code', 'LDD')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'CM Punjab: Progressive Control of Foot and Mouth Disease (FMD) in Punjab',
                    'table_name' => 'ldd_foot_mouth_disease_control',
                ],

                [
                    'name' => 'CM Punjab: Rehabilitation of One Veterinary Hospital at Tehsil to Model Veterinary Hospital',
                    'table_name' => 'ldd_model_veterinary_hospital',
                ],

                [
                    'name' => 'CM Punjab: Provision of Mobile Veterinary Dispensaries (MVDs)',
                    'table_name' => 'ldd_mobile_veterinary_dispensaries',
                ],

                [
                    'name' => 'CM Punjab: Herd Transformation to Enhance Livestock Productivity (Phase I)',
                    'table_name' => 'ldd_herd_transformation_phase_1',
                ],

                [
                    'name' => 'CM Punjab: Provision of X-ray & Surgical Facilities at District Level for Animal Health and Welfare',
                    'table_name' => 'ldd_xray_surgical_facilities',
                ],

                [
                    'name' => 'CM Punjab Livestock Card',
                    'table_name' => 'ldd_livestock_card',
                ],

                [
                    'name' => 'CM Punjab Livestock Card Phase-II',
                    'table_name' => 'ldd_livestock_card_phase_2',
                ],

                [
                    'name' => 'Livestock Asset transfer to Rural women in South Punjab',
                    'table_name' => 'ldd_livestock_asset_transfer_rural_women',
                ],

                [
                    'name' => 'CM Punjab: Empowering Rural Women through Livestock Support',
                    'table_name' => 'ldd_empowering_rural_women_livestock',
                ],

                [
                    'name' => 'CM Punjab: Dairy Supply Chain Support Project',
                    'table_name' => 'ldd_dairy_supply_chain_support',
                ],

                [
                    'name' => "CM Punjab's Internship Program for Vets and Paravets",
                    'table_name' => 'ldd_internship_vets_paravets',
                ],

                [
                    'name' => 'Livestock Farmers Training Project',
                    'table_name' => 'ldd_livestock_farmers_training',
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
