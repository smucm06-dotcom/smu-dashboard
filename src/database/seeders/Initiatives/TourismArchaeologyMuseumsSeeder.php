<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class TourismArchaeologyMuseumsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Tourism, Archaeology & Museums Department
            $department = Department::where('code', 'TAM')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => "Preservation/Restoration, Improvement, Management and Upgradation St. Andrew's Presbyterian Church, Lahore",
                    'table_name' => 'tam_st_andrews_church_lahore',
                ],

                [
                    'name' => "Work for Punjab Chief Minister's Magnificent Tourism Internship Programme – (Phase II)",
                    'table_name' => 'tam_tourism_internship_phase_2',
                ],

                [
                    'name' => 'Conservation, Rehabilitation and Improvement of Summit Minar, Lahore',
                    'table_name' => 'tam_summit_minar_lahore',
                ],

                [
                    'name' => 'Preservation/Restoration, Improvement, Management and Upgradation of Rohtas Fort, District Jhelum',
                    'table_name' => 'tam_rohtas_fort_jhelum',
                ],

                [
                    'name' => 'Preservation/Restoration, Improvement, Management and Upgradation of Hiran Minar, District Sheikhupura',
                    'table_name' => 'tam_hiran_minar_sheikhupura',
                ],

                [
                    'name' => 'Preservation/Restoration, Improvement, Management and Upgradation of World Heritage Site Taxila, District Rawalpindi',
                    'table_name' => 'tam_taxila_world_heritage_site',
                ],

                [
                    'name' => 'Preservation/Restoration, Improvement, Management and Upgradation of Derawar Fort, District Bahawalpur',
                    'table_name' => 'tam_derawar_fort_bahawalpur',
                ],

                [
                    'name' => 'Preservation/Restoration, Improvement, Management and Upgradation of Harrapa, District Sahiwal',
                    'table_name' => 'tam_harrapa_sahiwal',
                ],

                [
                    'name' => 'Rehabilitation of Javaid Manzil (Iqbal Museum), Lahore',
                    'table_name' => 'tam_javaid_manzil_iqbal_museum',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Dharabi Dam, District Chakwal',
                    'table_name' => 'tam_tdcp_dharabi_dam',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Kallar Kahar, District Chakwal',
                    'table_name' => 'tam_tdcp_kallar_kahar',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Khabbeki Lake, District Khushab',
                    'table_name' => 'tam_tdcp_khabbeki_lake',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Changa Manga, District Kasur',
                    'table_name' => 'tam_tdcp_changa_manga',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Derawar Fort Cholistan, District Bahawalpur',
                    'table_name' => 'tam_tdcp_derawar_fort_cholistan',
                ],

                [
                    'name' => "Chief Minister's Magnificient Punjab Tourism Internship",
                    'table_name' => 'tam_cm_tourism_internship',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Patriata Chairlift and Cable Car Resort and Tourist Information Center, Murree',
                    'table_name' => 'tam_tdcp_patriata_chairlift',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Indus Picnic Resort, Daud Khel District Mianwali',
                    'table_name' => 'tam_tdcp_indus_picnic_resort',
                ],

                [
                    'name' => 'Improvement, Revitalization, and Upgradation of TDCP Tourist Resort Jallo, District Lahore',
                    'table_name' => 'tam_tdcp_jallo_lahore',
                ],

                [
                    'name' => 'Master Plan for Conservation, Rehabilitation, Improvement and Upgradation of Lahore Museum',
                    'table_name' => 'tam_lahore_museum_master_plan',
                ],

                [
                    'name' => 'Establishment of Tourist Resort and Rehabilitation of Park at Trimmu Head Works, District Jhang',
                    'table_name' => 'tam_trimmu_headworks_jhang',
                ],

                [
                    'name' => 'Religious Trail Punjab',
                    'table_name' => 'tam_religious_trail_punjab',
                ],

                [
                    'name' => 'North / Potohar Leisure Belt',
                    'table_name' => 'tam_north_potohar_leisure_belt',
                ],

                [
                    'name' => 'Lahore Heritage',
                    'table_name' => 'tam_lahore_heritage',
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
