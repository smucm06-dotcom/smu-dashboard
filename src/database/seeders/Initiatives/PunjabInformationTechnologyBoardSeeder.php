<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class PunjabInformationTechnologyBoardSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Punjab Information Technology Board
            $department = Department::where('code', 'PITB')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister IT Internship Program',
                    'table_name' => 'pitb_cm_it_internship_program',
                ],

                [
                    'name' => 'Punjab e-Commerce Initiative',
                    'table_name' => 'pitb_punjab_ecommerce_initiative',
                ],

                [
                    'name' => 'Accelerate Punjab',
                    'table_name' => 'pitb_accelerate_punjab',
                ],

                [
                    'name' => 'AI and Emerging Technologies for Governance',
                    'table_name' => 'pitb_ai_emerging_technologies_governance',
                ],

                [
                    'name' => 'Establishment of Chief Minister IT Park',
                    'table_name' => 'pitb_cm_it_park',
                ],

                [
                    'name' => 'Establishment of CM Complaint Helpline',
                    'table_name' => 'pitb_cm_complaint_helpline',
                ],

                [
                    'name' => 'Global IT Certifications Program',
                    'table_name' => 'pitb_global_it_certifications',
                ],

                [
                    'name' => 'Punjab Dastak Program',
                    'table_name' => 'pitb_punjab_dastak_program',
                ],

                [
                    'name' => 'Establishment of Makerspace Punjab',
                    'table_name' => 'pitb_makerspace_punjab',
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
