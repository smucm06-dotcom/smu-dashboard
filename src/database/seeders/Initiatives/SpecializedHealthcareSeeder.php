<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class SpecializedHealthcareSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Specialized Healthcare Department
            $department = Department::where('code', 'SHCME')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Nawaz Sharif Institute of Cancer Treatment and Research, Lahore',
                    'table_name' => 'shcme_nawaz_sharif_cancer_institute',
                ],

                [
                    'name' => 'Establishment of Children Hospital at Bahawalpur',
                    'table_name' => 'shcme_children_hospital_bahawalpur',
                ],

                [
                    'name' => 'Establishment of Nawaz Sharif Medical District at Lahore (Children Hospital-II, Institute of Surgical, Orthopedic & Medical Rehabilitation, Infectious Diseases Hospital, Center of Excellence for Nursing Education and Midwifery, Institute of Plastic Recons',
                    'table_name' => 'shcme_nawaz_sharif_medical_district_1',
                ],

                [
                    'name' => 'Nawaz Sharif Institute of Cardiology, Sargodha',
                    'table_name' => 'shcme_cardiology_institute_sargodha',
                ],

                [
                    'name' => 'Revamping of Old Blocks of Punjab Institute of Mental Health, Lahore',
                    'table_name' => 'shcme_pimh_old_blocks_revamping',
                ],

                [
                    'name' => 'Establishment of Nawaz Sharif Medical District at Lahore (Children Hospital-II and Institute of Genetic Blood Diseases, Institute of Surgical, Orthopedic & Medical Rehabilitation, Center of Excellence for Nursing Education)',
                    'table_name' => 'shcme_nawaz_sharif_medical_district_2',
                ],

                [
                    'name' => 'Home Delivery of Free Medicine',
                    'table_name' => 'shcme_home_delivery_free_medicine',
                ],

                [
                    'name' => 'Children Heart Surgery Program',
                    'table_name' => 'shcme_children_heart_surgery_program',
                ],

                [
                    'name' => "Chief Minister's Special Initiative for Transplant Program",
                    'table_name' => 'shcme_cm_transplant_program',
                ],

                [
                    'name' => "Chief Minister's Cardiac Surgery Program (CMCSP)",
                    'table_name' => 'shcme_cm_cardiac_surgery_program',
                ],

                [
                    'name' => "Chief Minister's Special Initiative for Cancer Patients (CMSICP)",
                    'table_name' => 'shcme_cm_cancer_patients_program',
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
