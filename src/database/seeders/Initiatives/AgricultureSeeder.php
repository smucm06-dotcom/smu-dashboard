<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class AgricultureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $department = Department::where('code', 'AGR')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Chief Minister’s Punjab Green Tractor Program (Phase-II)",
                    'table_name' => 'agr_green_tractor_phase_2',
                ],

                [
                    'name' => "Chief Minister's Program for Water Efficient Agriculture",
                    'table_name' => 'agr_water_efficient_agriculture',
                ],

                [
                    'name' => 'Transformation of Agriculture in Pothohar',
                    'table_name' => 'agr_transformation_pothohar',
                ],

                [
                    'name' => 'Work for Punjab: Internship Scheme for Agriculture Graduates (Phase-II) (2026-27 to 2027-28)',
                    'table_name' => 'agr_internship_graduates_phase_2',
                ],

                [
                    'name' => 'Chief Minister’s Punjab Hi Power Tractor Program',
                    'table_name' => 'agr_hi_power_tractor_program',
                ],

                [
                    'name' => 'Chief Minister’s Punjab Green Tractor Program (Low Power) (Phase-IV) (2026-27)',
                    'table_name' => 'agr_green_tractor_low_power_phase_4',
                ],

                [
                    'name' => "Chief Minister's Punjab Hi-Tech Farm Mechanization Financing Program",
                    'table_name' => 'agr_hi_tech_mechanization',
                ],

                [
                    'name' => 'Chief Minister’s Punjab Green Tractor Program (Hi Power) (Phase-V) (2026-27)',
                    'table_name' => 'agr_green_tractor_hi_power_phase_5',
                ],

                [
                    'name' => 'Chief Minister’s Water Conservation and Livelihood Project in Punjab Barani Tract',
                    'table_name' => 'agr_water_conservation_barani',
                ],

                [
                    'name' => 'Chief Minister Punjab Kissan Card (Phase-II) (2026-27 to 2027-28)',
                    'table_name' => 'agr_kissan_card_phase_2',
                ],

                [
                    'name' => 'Establishment of Model Agriculture Mall (2024-25 to 2025-26)',
                    'table_name' => 'agr_model_agriculture_mall',
                ],

                [
                    'name' => 'Transformation of Agriculture in Thal (2026-27 to 2028-29)',
                    'table_name' => 'agr_transformation_thal',
                ],

                [
                    'name' => "Chief Minister's Punjab Kissan Card (2024-25 to 2025-26)",
                    'table_name' => 'agr_kissan_card_phase_1',
                ],

                [
                    'name' => "Chief Minister's Program for Solarization of Agricultural Tubewell in Punjab (2024-25 to 2025-26)",
                    'table_name' => 'agr_solarization_tubewells',
                ],

                [
                    'name' => 'Internship Scheme for Agriculture Graduates (2024-25 to 2025-26)',
                    'table_name' => 'agr_internship_graduates_2024',
                ],

                [
                    'name' => 'Internship Scheme for Agriculture Graduates',
                    'table_name' => 'agr_internship_graduates',
                ],

                [
                    'name' => 'Import Substitution and Export Enhancement through Promotion of Sesame, Soybean and Canola (2024-25 to 2026-27)',
                    'table_name' => 'agr_oilseed_promotion_2024',
                ],

                [
                    'name' => "Chief Minister's Fund for Promotion of E-mechanization through Indigenous Manufacturing of Agricultural Machinery",
                    'table_name' => 'agr_e_mechanization_fund',
                ],

                [
                    'name' => "Chief Minister's Fund for Promotion of E-mechanization through Indigenous Manufacturing of Agricultural Machinery (2024-25 to 2025-26)",
                    'table_name' => 'agr_e_mechanization_fund_2024',
                ],

                [
                    'name' => 'Import Substitution and Export Enhancement through Promotion of Sesame, Soybean and Canola',
                    'table_name' => 'agr_oilseed_promotion',
                ],

                [
                    'name' => "Chief Minister's Program for Water Efficient Agriculture (2025-26 to 2027-28)",
                    'table_name' => 'agr_water_efficient_agriculture_2025',
                ],

                [
                    'name' => 'Transformation of Agriculture in Pothohar and District Khushab (2025-26 to 2027-28)',
                    'table_name' => 'agr_pothohar_khushab_2025',
                ],

                [
                    'name' => "Chief Minister's Punjab Hi-Tech Farm Mechanization Financing Program (2025-26 to 2028-29)",
                    'table_name' => 'agr_hi_tech_mechanization_2025',
                ],

                [
                    'name' => 'Chief Minister’s Water Conservation and Livelihood Project in Punjab Barani Tract (2025-26 to 2027-28)',
                    'table_name' => 'agr_water_conservation_barani_2025',
                ],

                [
                    'name' => 'Establishment of Model Agriculture Malls in Punjab, Phase-II (2025-26 to 2026-27)',
                    'table_name' => 'agr_model_agriculture_malls_phase_2',
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
