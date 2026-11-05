<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class SkillsDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Skills Development & Entrepreneurship Department
            $department = Department::where('code', 'SDE')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister Skills Development initiatives (Introduction of IT Courses in TEVTA Punjab)',
                    'table_name' => 'sde_cm_skills_development_it_tevta',
                ],

                [
                    'name' => "CM's Punjab Industrial Clusters Pathways",
                    'table_name' => 'sde_punjab_industrial_clusters_pathways',
                ],

                [
                    'name' => 'Umeed - Skills for Inclusion',
                    'table_name' => 'sde_umeed_skills_for_inclusion',
                ],

                [
                    'name' => "CM'S Youth Employability in BPO's",
                    'table_name' => 'sde_youth_employability_bpos',
                ],

                [
                    'name' => "CM's Advance Technology Program",
                    'table_name' => 'sde_advance_technology_program',
                ],

                [
                    'name' => "CM's Parwaaz Card for International Placement",
                    'table_name' => 'sde_parwaaz_card_international_placement',
                ],

                [
                    'name' => 'Establishing TEVTA as an Overseas Employment Promoter (OEP) for Export of Skilled Youth in Punjab',
                    'table_name' => 'sde_tevta_overseas_employment_promoter',
                ],

                [
                    'name' => "Chief Minister's Skilled Punjab Program",
                    'table_name' => 'sde_skilled_punjab_program',
                ],

                [
                    'name' => 'Empowering Women by Way of IT Training across Punjab',
                    'table_name' => 'sde_women_it_training_punjab',
                ],

                [
                    'name' => 'Empowering Rural Women By Way of IT-II',
                    'table_name' => 'sde_rural_women_it_phase_2',
                ],

                [
                    'name' => "CM's Skills for Agriculture & Green Environment (SAGE)",
                    'table_name' => 'sde_skills_agriculture_green_environment',
                ],

                [
                    'name' => 'Chief Minister Skill Development Program for Transgenders',
                    'table_name' => 'sde_skill_development_transgenders',
                ],

                [
                    'name' => 'Expansion of Construction and Hospitality Labs in TEVTA',
                    'table_name' => 'sde_construction_hospitality_labs_tevta',
                ],

                [
                    'name' => "CM's Program for Language Learning and Improvements",
                    'table_name' => 'sde_language_learning_improvements',
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
