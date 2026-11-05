<?php

namespace Database\Seeders\Initiatives;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Initiative;

class TransportMassTransitSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Find Transport & Mass Transit Department
            $department = Department::where('code', 'TMT')
                ->firstOrFail();

            $initiatives = [

                [
                    'name' => 'Development of Mass Transit System in Faisalabad',
                    'table_name' => 'tmt_mass_transit_faisalabad',
                ],

                [
                    'name' => 'Development of Mass Transit System in Gujranwala',
                    'table_name' => 'tmt_mass_transit_gujranwala',
                ],

                [
                    'name' => 'Construction of Tourist Glass Train from Islamabad to Murree',
                    'table_name' => 'tmt_tourist_glass_train_murree',
                ],

                [
                    'name' => 'Feasibility Study of Mass Transit System in Sargodha',
                    'table_name' => 'tmt_mass_transit_sargodha_feasibility',
                ],

                [
                    'name' => 'Feasibility Study Development of Road Safety Audit System in Punjab through Pilot Implementation',
                    'table_name' => 'tmt_road_safety_audit_feasibility',
                ],

                [
                    'name' => 'Rehabilitation, Remodeling and Solarization of Existing Lahore Metro Bus System (Green Line) Stations',
                    'table_name' => 'tmt_lahore_metro_green_line_stations',
                ],

                [
                    'name' => 'Chief Minister Induction of Buses Program',
                    'table_name' => 'tmt_cm_induction_buses_program',
                ],

                [
                    'name' => 'Feasibility Study for Mass Transit Corridor(s) in Lahore with Integrated Connectivity to the Proposed Multimodal Transport Terminal',
                    'table_name' => 'tmt_lahore_mass_transit_corridors',
                ],

                [
                    'name' => 'Feasibility Study For Proposed Multimodal Transport Terminals In Lahore',
                    'table_name' => 'tmt_multimodal_terminals_lahore',
                ],

                [
                    'name' => 'Chief Minister Bus Depots Program',
                    'table_name' => 'tmt_cm_bus_depots_program',
                ],

                [
                    'name' => 'Project Management Unit (PMU) for Development of Mass Transit Projects',
                    'table_name' => 'tmt_mass_transit_pmu',
                ],

                [
                    'name' => 'Induction of 630 Eco-Friendly Buses and Construction of Bus Depots in Lahore, Multan, Rawalpindi, Bahawalpur, Faisalabad',
                    'table_name' => 'tmt_630_eco_friendly_buses_depots',
                ],

                [
                    'name' => 'Feasibility study by consultants for new mass transit systems in Lahore',
                    'table_name' => 'tmt_lahore_mass_transit_feasibility',
                ],

                [
                    'name' => 'Updation of Transport Modelling and Feasibility study for Mass transit system in Faisalabad',
                    'table_name' => 'tmt_faisalabad_transport_modelling',
                ],

                [
                    'name' => 'Feasibility study by consultants for mass transit systems in Gujranwala',
                    'table_name' => 'tmt_gujranwala_mass_transit_feasibility',
                ],

                [
                    'name' => 'Seed Money for development of Mass Transit System on Yellow Line in Lahore',
                    'table_name' => 'tmt_lahore_yellow_line_seed_money',
                ],

                [
                    'name' => 'Development of Command & Control Centre / Transport Tower in Lahore',
                    'table_name' => 'tmt_transport_tower_lahore',
                ],

                [
                    'name' => 'Establishment of High Speed Rail from Lahore to Rawalpindi',
                    'table_name' => 'tmt_high_speed_rail_lahore_rawalpindi',
                ],

                [
                    'name' => 'Feasibility, Seed Money for Establishment of High-speed Rail from Lahore to Rawalpindi',
                    'table_name' => 'tmt_high_speed_rail_feasibility',
                ],

                [
                    'name' => 'Feasibility, Seed Money for Establishment of Regional Railways Tracks in Punjab',
                    'table_name' => 'tmt_regional_railways_feasibility',
                ],

                [
                    'name' => 'Feasibility, Seed Money for Establishment of Air Punjab',
                    'table_name' => 'tmt_air_punjab_feasibility',
                ],

                [
                    'name' => 'Provision of Interest Free Electric Taxi in Lahore',
                    'table_name' => 'tmt_interest_free_electric_taxi',
                ],

                [
                    'name' => 'Interest-Free Bikes for Students',
                    'table_name' => 'tmt_interest_free_bikes_students',
                ],

                [
                    'name' => 'Provision of 100,000 Electric Bikes to Students by the Bank of Punjab under PCAP',
                    'table_name' => 'tmt_100000_electric_bikes_pcap',
                ],

                [
                    'name' => 'Development of Urban Bus Depot & Allied Infrastructure for Electric Buses in Lahore under Punjab Clean Air Program (P-CAP) by World Bank',
                    'table_name' => 'tmt_lahore_electric_bus_depot_pcap',
                ],

                [
                    'name' => 'Empower Her Bus Shelter Initiative',
                    'table_name' => 'tmt_empower_her_bus_shelter',
                ],

                [
                    'name' => 'Development of Depot & Allied Infrastructure for New Electric Buses in Multan',
                    'table_name' => 'tmt_multan_electric_bus_depot',
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
