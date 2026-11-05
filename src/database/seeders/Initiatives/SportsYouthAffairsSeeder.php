<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class SportsYouthAffairsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Sports & Youth Affairs Department
            $department = Department::where('code', 'SYA')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Chief Minister Khelta Punjab',
                    'table_name' => 'sya_cm_khelta_punjab',
                ],

                [
                    'name' => 'Revival of Sports Across Punjab',
                    'table_name' => 'sya_revival_sports_punjab',
                ],

                [
                    'name' => 'Feasibility and Seed Money for the Project, Maryam Nawaz Sports City Lahore',
                    'table_name' => 'sya_maryam_nawaz_sports_city_lahore',
                ],

                [
                    'name' => 'Chief Minister’s Youth Engagement Programme',
                    'table_name' => 'sya_cm_youth_engagement_programme',
                ],

                [
                    'name' => 'Program for Rehabilitation of Existing Sports Facilities',
                    'table_name' => 'sya_rehabilitation_sports_facilities',
                ],

                [
                    'name' => 'Chief Minister Internship Program',
                    'table_name' => 'sya_cm_internship_program',
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
