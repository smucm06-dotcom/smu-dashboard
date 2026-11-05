<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class HigherEducationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Higher Education Department
            $department = Department::where('code', 'HED')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'CM Punjab Honhaar Scholarship Program',
                    'table_name' => 'hed_honhaar_scholarship_program',
                ],

                [
                    'name' => 'CM Initiative for Establishment of IT Labs in Colleges',
                    'table_name' => 'hed_it_labs_colleges',
                ],

                [
                    'name' => 'CM Punjab Laptop Program',
                    'table_name' => 'hed_cm_punjab_laptop_program',
                ],

                [
                    'name' => 'CM Initiative for Establishment of New Colleges',
                    'table_name' => 'hed_establishment_new_colleges',
                ],

                [
                    'name' => 'Chief Minister Initiative for Establishment / Upgradation of Sub-Campuses of existing Universities',
                    'table_name' => 'hed_university_sub_campuses',
                ],

                [
                    'name' => 'CM Initiative for provision of Missing Facilities in Colleges',
                    'table_name' => 'hed_missing_facilities_colleges',
                ],

                [
                    'name' => 'CM Initiative for Establishment of IT Labs in Colleges (Phase-II)',
                    'table_name' => 'hed_it_labs_colleges_phase_2',
                ],

                [
                    'name' => 'Audit and Feasibility / Sustainability Analysis of 10 Top-Ranked Public Sector Universities in Punjab',
                    'table_name' => 'hed_universities_audit_feasibility',
                ],

                [
                    'name' => 'Strengthening of HED Secretariat and Provision of ERP System for Colleges',
                    'table_name' => 'hed_secretariat_erp_colleges',
                ],

                [
                    'name' => 'Punjab Higher Education Commission (PHEC)',
                    'table_name' => 'hed_punjab_higher_education_commission',
                ],

                [
                    'name' => 'Punjab Education Endowment Fund (PEEF)',
                    'table_name' => 'hed_punjab_education_endowment_fund',
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
