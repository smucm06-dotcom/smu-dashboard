<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class ForestWildlifeFisheriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Forest, Wildlife & Fisheries Department
            $department = Department::where('code', 'FWF')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Establishment of Model Fish Market, Lahore (Feasibility Study)',
                    'table_name' => 'fwf_model_fish_market_feasibility',
                ],

                [
                    'name' => 'Establishment of Model Fish Market, Lahore',
                    'table_name' => 'fwf_model_fish_market_lahore',
                ],

                [
                    'name' => 'Establishment of Shrimp Estate with Value Chain components at Chak 58NB, Tehsil & District Sargodha',
                    'table_name' => 'fwf_shrimp_estate_chak_58nb',
                ],

                [
                    'name' => 'Establishment of Shrimp Farms at 57-NB, Chak 58-NB, Chak 59-NB and Nawabpur, Tehsil & District Sargodha',
                    'table_name' => 'fwf_shrimp_farms_sargodha',
                ],

                [
                    'name' => 'Establishment of Shrimp Farms at Rakh Ali Wali, Muzaffargarh',
                    'table_name' => 'fwf_shrimp_farms_rakh_ali_wali',
                ],

                [
                    'name' => 'Establishment of 1st Shrimp Estate with Value Chain Components at Shah Garh, Muzaffargarh',
                    'table_name' => 'fwf_shrimp_estate_shah_garh',
                ],

                [
                    'name' => 'Establishment of Shrimp Estate with Value Chain Components at Rakh Ali Wali, Muzaffargarh',
                    'table_name' => 'fwf_shrimp_estate_rakh_ali_wali',
                ],

                [
                    'name' => 'Feasibility Study on Large-Scale Shrimp Farming (PC-II)',
                    'table_name' => 'fwf_shrimp_farming_feasibility_pc2',
                ],

                [
                    'name' => 'Establishment of Aquaculture Malls in Punjab',
                    'table_name' => 'fwf_aquaculture_malls_punjab',
                ],

                [
                    'name' => 'Establishment of Hatchery Facilities for Pangasius and other high value fish species',
                    'table_name' => 'fwf_pangasius_hatchery_facilities',
                ],

                [
                    'name' => 'Aquaculture Shrimp Farming in Punjab',
                    'table_name' => 'fwf_aquaculture_shrimp_farming',
                ],

                [
                    'name' => 'CM Plant for Pakistan',
                    'table_name' => 'fwf_cm_plant_for_pakistan',
                ],

                [
                    'name' => 'CM Initiative for Agro Forestry for Forest Waste Land',
                    'table_name' => 'fwf_agro_forestry_forest_waste_land',
                ],

                [
                    'name' => 'Punjab Wildlife Census',
                    'table_name' => 'fwf_punjab_wildlife_census',
                ],

                [
                    'name' => 'Eco-tourism at Changa Manga and Wetlands, Uchhali & Chashma',
                    'table_name' => 'fwf_ecotourism_changa_manga_wetlands',
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
