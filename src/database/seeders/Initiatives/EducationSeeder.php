<?php
namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class EducationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $department = Department::where('code', 'SED')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister\'s Punjab Schools Meal Program',
                    'table_name' => 'punjab_schools_meal_program',
                ],

                [
                    'name' => 'Establishment of Maryam Nawaz Center of Academic Leadership (2 Public Schools) at Lahore',
                    'table_name' => 'maryam_nawaz_academic_leadership_center',
                ],

                [
                    'name' => 'Construction of Additional Classrooms in Public Schools of Punjab (through PEIMA)',
                    'table_name' => 'construction_additional_classrooms_peima',
                ],

                [
                    'name' => 'Provision of Missing Facilities and IT Labs in Public Schools of Punjab (through PEIMA)',
                    'table_name' => 'missing_facilities_it_labs_peima',
                ],

                [
                    'name' => 'Re-construction of Dangerous Buildings of Public Schools of Punjab (through PEIMA)',
                    'table_name' => 'reconstruction_dangerous_buildings_peima',
                ],

                [
                    'name' => 'Establishment of Schools of Eminence using Existing Infrastructure in all Districts',
                    'table_name' => 'schools_of_eminence_existing_infrastructure',
                ],

                [
                    'name' => 'Establishment of STEAM Labs across Punjab',
                    'table_name' => 'steam_labs_punjab',
                ],

                [
                    'name' => 'Establishment of Nawaz Sharif Centre of Excellence at Children Library Complex, Lahore (Phase-I)',
                    'table_name' => 'nawaz_sharif_centre_excellence_phase_1',
                ],

                [
                    'name' => 'Nawaz Sharif Centre of Excellence for ECE (Phase-II)',
                    'table_name' => 'nawaz_sharif_centre_excellence_ece_phase_2',
                ],

                [
                    'name' => 'Establishment of Nawaz Sharif Centre of Excellence for ECE & Children Library Complex in 10 Divisions',
                    'table_name' => 'nawaz_sharif_centre_excellence_10_divisions',
                ],

                [
                    'name' => 'Public Schools Re-Organization Program (PSRP) – Phase I',
                    'table_name' => 'public_school_reorganization_phase_1',
                ],

                [
                    'name' => 'Public Schools Re-Organization Program (PSRP) – Phase II',
                    'table_name' => 'public_school_reorganization_phase_2',
                ],

                [
                    'name' => 'Public Schools Re-Organization Program (PSRP) – Phase III',
                    'table_name' => 'public_school_reorganization_phase_3',
                ],

                [
                    'name' => 'Punjab Education Training & Assessment Authority (PECTAA)',
                    'table_name' => 'punjab_education_training_assessment_authority',
                ],

                [
                    'name' => 'Afternoon School Program (ASP)',
                    'table_name' => 'afternoon_school_program',
                ],

                [
                    'name' => 'Installation of Water Filtration Plants – 3 Schools',
                    'table_name' => 'water_filtration_plants_3_schools',
                ],

                [
                    'name' => 'Spoken English Initiative – PEF Schools',
                    'table_name' => 'spoken_english_initiative_pef',
                ],

                [
                    'name' => 'Establishment of First Nawaz Sharif School of Eminence, Jalalpur Pirwala, Multan',
                    'table_name' => 'nawaz_sharif_school_eminence_jalalpur_pirwala',
                ],

            ];

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
