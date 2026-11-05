<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class PlanningDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Planning & Development Department
            $department = Department::where('code', 'PND')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Punjab Socio Economic Registry (PSER)',
                    'table_name' => 'pnd_punjab_socio_economic_registry',
                ],

                [
                    'name' => 'Establishment of CM Complaint Helpline',
                    'table_name' => 'pnd_cm_complaint_helpline',
                ],

                [
                    'name' => 'Comprehensive Door to Door Socio Economic Survey of Punjab for establishing Punjab Socio Economic Registry (PSER) - PC-II',
                    'table_name' => 'pnd_pser_door_to_door_survey_pc2',
                ],

                [
                    'name' => 'Punjab Dastak Programme',
                    'table_name' => 'pnd_punjab_dastak_programme',
                ],

                [
                    'name' => 'Work For Punjab: Chief Minister IT Internship Program',
                    'table_name' => 'pnd_cm_it_internship_program',
                ],

                [
                    'name' => 'Work For Punjab: Chief Minister IT Internship Program - Phase II',
                    'table_name' => 'pnd_cm_it_internship_phase_2',
                ],

                [
                    'name' => 'Chief Minister Priority Program',
                    'table_name' => 'pnd_cm_priority_program',
                ],

                [
                    'name' => 'Global IT Certifications Program',
                    'table_name' => 'pnd_global_it_certifications',
                ],

                [
                    'name' => 'Punjab e-Commerce Initiative',
                    'table_name' => 'pnd_punjab_ecommerce_initiative',
                ],

                [
                    'name' => 'Accelerate Punjab',
                    'table_name' => 'pnd_accelerate_punjab',
                ],

                [
                    'name' => 'AI and Emerging Technologies for Governance',
                    'table_name' => 'pnd_ai_emerging_technologies_governance',
                ],

                [
                    'name' => 'Chief Minister Equalization Program for South Punjab',
                    'table_name' => 'pnd_cm_equalization_south_punjab',
                ],

                [
                    'name' => 'Uplifting of Priority Areas',
                    'table_name' => 'pnd_uplifting_priority_areas',
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
