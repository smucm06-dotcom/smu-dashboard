<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class SpecialEducationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Special Education Department
            $department = Department::where('code', 'SPE')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Chief Minister's Punjab Maryam Nawaz Khud-mukhtar Program (e-Earn Training and Skill Development through strengthening of existing VTCs of Special Education Department)",
                    'table_name' => 'spe_maryam_nawaz_khud_mukhtar_program',
                ],

                [
                    'name' => 'Chief Minister Meal Program for enrolled special students in Special Education Institutions working across the Punjab',
                    'table_name' => 'spe_cm_meal_program_special_students',
                ],

                [
                    'name' => 'Construction and Establishment of Autism School at Lahore',
                    'table_name' => 'spe_autism_school_lahore',
                ],

                [
                    'name' => 'Umbrella scheme for the Construction and Establishment of Satellite Campus of Maryam Nawaz Autism School at each division in Punjab',
                    'table_name' => 'spe_autism_school_satellite_campuses',
                ],

                [
                    'name' => 'Enhancing Surveillance Capabilities at Special Education Schools (Phase II)',
                    'table_name' => 'spe_surveillance_special_schools_phase_2',
                ],

                [
                    'name' => 'Provision of Transport Facility to Special Education Institutions',
                    'table_name' => 'spe_transport_special_education',
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
