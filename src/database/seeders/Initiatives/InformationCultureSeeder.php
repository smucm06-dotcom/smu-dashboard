<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class InformationCultureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Information & Culture Department
            $department = Department::where('code', 'INC')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Up-gradation of Al-Hamra Arts Council at Mall Road and Cultural Complex near Gaddafi Stadium (LAC)',
                    'table_name' => 'inc_al_hamra_arts_council_upgradation',
                ],

                [
                    'name' => 'Construction of State-of-the-Art DGPR Complex at Head Office, Lahore',
                    'table_name' => 'inc_dgpr_complex_lahore',
                ],

                [
                    'name' => 'Feasibility and Seed Money for Film City and Cinema Development Funds',
                    'table_name' => 'inc_film_city_cinema_feasibility',
                ],

                [
                    'name' => 'Chief Minister Film City Program',
                    'table_name' => 'inc_cm_film_city_program',
                ],

                [
                    'name' => 'Construction of Civic Infrastructure & Allied Works, Lahore',
                    'table_name' => 'inc_civic_infrastructure_allied_works',
                ],

                [
                    'name' => 'Construction of Administrative and Studio Complex, Lahore',
                    'table_name' => 'inc_administrative_studio_complex',
                ],

                [
                    'name' => 'Construction of Convention Centre, Lahore',
                    'table_name' => 'inc_convention_centre_lahore',
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
