<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class HealthPopulationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Health & Population Department
            $department = Department::where('code', 'HEP')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Cath Labs at selected DHQ Hospitals in Punjab (Phase-II)',
                    'table_name' => 'hep_cath_labs_phase_2',
                ],

                [
                    'name' => 'Establishment of Cardiac Surgery Units at 15 DHQ Hospitals with Cath Labs',
                    'table_name' => 'hep_cardiac_surgery_units_15_dhq',
                ],

                [
                    'name' => 'Revamping / Reconstruction of non 24/7 MNHCs across Punjab',
                    'table_name' => 'hep_revamping_non_247_mnhcs',
                ],

                [
                    'name' => 'Provision of Missing Essential Equipment at Secondary Hospitals across Punjab',
                    'table_name' => 'hep_missing_equipment_secondary_hospitals',
                ],

                [
                    'name' => 'Provision of MRI Machines in DHQ Hospitals across Punjab',
                    'table_name' => 'hep_mri_machines_dhq_hospitals',
                ],

                [
                    'name' => 'Provision of CT Scan Machines in 24 DHQ Hospitals across Punjab',
                    'table_name' => 'hep_ct_scan_machines_24_dhq',
                ],

                [
                    'name' => 'Establishment of Cath Labs at Selected DHQ Hospitals in Punjab (Phase-3)',
                    'table_name' => 'hep_cath_labs_phase_3',
                ],

                [
                    'name' => 'Revamping / Reconstruction of Remaining / Leftover 24/7 MNHCs across Punjab',
                    'table_name' => 'hep_revamping_remaining_247_mnhcs',
                ],

                [
                    'name' => 'Revamping / Reconstruction of existing Dispensaries and MCH Centers across Punjab to Operationalize as MNHCs (Category A & B)',
                    'table_name' => 'hep_dispensaries_mch_to_mnhcs',
                ],

                [
                    'name' => 'Maryam Nawaz Community Health Services Program through Outsourced Community Health Inspectors',
                    'table_name' => 'hep_community_health_inspectors',
                ],

                [
                    'name' => 'Maryam Nawaz Rural Hospitals through Performance Based Financing (PBF)',
                    'table_name' => 'hep_rural_hospitals_pbf',
                ],

                [
                    'name' => 'Chief Minister Punjab Health Reforms Initiative Maryam Nawaz Health Clinic',
                    'table_name' => 'hep_cm_health_reforms_health_clinic',
                ],

                [
                    'name' => 'Revamping of Remaining / Leftover 24/7 BHUs across Punjab',
                    'table_name' => 'hep_revamping_remaining_247_bhus',
                ],

                [
                    'name' => 'Provision of Equipment and Furniture & Fixture of Remaining BHUs and RHCs (Phase-II)',
                    'table_name' => 'hep_equipment_furniture_bhus_rhcs_phase_2',
                ],

                [
                    'name' => 'Revamping / Reconstruction of existing Dispensaries and MCH Centers across Punjab',
                    'table_name' => 'hep_revamping_dispensaries_mch_centers',
                ],

                [
                    'name' => 'Reconstruction of Dilapidated BHUs and RHCs of Punjab (80 BHUs and 6 RHCs)',
                    'table_name' => 'hep_reconstruction_80_bhus_6_rhcs',
                ],

                [
                    'name' => 'Provision of Equipment and Furniture & Fixture of Dispensaries and MCH Centers across Punjab',
                    'table_name' => 'hep_equipment_furniture_dispensaries_mch',
                ],

                [
                    'name' => 'Revamping of non 24/7 BHUs across Punjab',
                    'table_name' => 'hep_revamping_non_247_bhus',
                ],

                [
                    'name' => 'Program for Revamping of RHCs (Phase II)',
                    'table_name' => 'hep_revamping_rhcs_phase_2',
                ],

                [
                    'name' => 'Program for revamping of BHUs of North and Central Punjab (Phase II)',
                    'table_name' => 'hep_revamping_bhus_north_central_phase_2',
                ],

                [
                    'name' => 'Establishment of Cath Labs at selected DHQ Hospitals and developing Allied Facilities in existing buildings',
                    'table_name' => 'hep_cath_labs_allied_facilities',
                ],

                [
                    'name' => 'Chief Minister Population Management and Family planning program',
                    'table_name' => 'hep_population_management_family_planning',
                ],

                [
                    'name' => 'Community Empowerment and Strengthening of Healthcare Access through Maryam Nawaz Health Clinics',
                    'table_name' => 'hep_community_empowerment_health_clinics',
                ],

                [
                    'name' => 'Clinic on Wheels',
                    'table_name' => 'hep_clinic_on_wheels',
                ],

                [
                    'name' => 'Field Hospitals',
                    'table_name' => 'hep_field_hospitals',
                ],

                [
                    'name' => 'Free Medicine Program (Hepatitis & TB)',
                    'table_name' => 'hep_free_medicine_hepatitis_tb',
                ],

                [
                    'name' => 'District and Tehsil Headquarter Hospitals (DHQs and THQs)',
                    'table_name' => 'hep_dhq_thq_hospitals',
                ],

                [
                    'name' => 'Chief Minister Dialysis Program Card',
                    'table_name' => 'hep_cm_dialysis_program_card',
                ],

                [
                    'name' => 'Insulin Card for Diabetic Patients (Type-I children)',
                    'table_name' => 'hep_insulin_card_type_1_children',
                ],

                [
                    'name' => 'Sehat Sahulat Program / Universal Health Insurance (UHI)',
                    'table_name' => 'hep_sehat_sahulat_uhi',
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
