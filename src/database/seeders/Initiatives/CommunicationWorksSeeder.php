<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class CommunicationWorksSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Communication & Works Department
            $department = Department::where('code', 'CW')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Roads Restoration Program (RRP)',
                    'table_name' => 'cw_roads_restoration_program',
                ],

                [
                    'name' => 'Road Rehabilitation Program-II',
                    'table_name' => 'cw_road_rehabilitation_program_2',
                ],

                [
                    'name' => 'Sarkain Bahal Punjab Kushhal Program',
                    'table_name' => 'cw_sarkain_bahal_punjab_kushhal',
                ],

                [
                    'name' => 'Rehabilitation of Multan-Vehari Road Length 93.5 Km',
                    'table_name' => 'cw_multan_vehari_road_rehabilitation',
                ],

                [
                    'name' => 'Integrated Road Infrastructure Improvement Program',
                    'table_name' => 'cw_integrated_road_infrastructure',
                ],

                [
                    'name' => 'Reconstruction/ Rehabilitation of GT road from Quaid-e-Azam Interchange (Lahore Ring Road) to Wahga Border, District Lahore',
                    'table_name' => 'cw_gt_road_wahga_border_rehabilitation',
                ],

                [
                    'name' => 'Dualization of Faisalabad Chiniot Sargodha road from Kamalpur Interchange to Tehsil chowk Chiniot, District Faisalabad & Chiniot Length 24.00 Km.',
                    'table_name' => 'cw_faisalabad_chiniot_road_dualization',
                ],

                [
                    'name' => 'Construction of Additional Carriageway (Dehli - Multan Road) from Pull 114/16-R to Pir Murad Morr, Length=50.60KM, District Vehari',
                    'table_name' => 'cw_dehli_multan_pull114_pir_murad',
                ],

                [
                    'name' => 'Construction of Additional Carriageway (Dehli-Multan Road) from babar Chowk to Drammawala Morr, Length=19.20 Km',
                    'table_name' => 'cw_dehli_multan_babar_drammawala',
                ],

                [
                    'name' => 'Construction of Additional Carriageway (Dehli - Multan Road) from Drammawala Morr to Pull 114/10-R, Length=6.00KM, District Khanewal',
                    'table_name' => 'cw_dehli_multan_drammawala_pull114',
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
