<?php

// The short name of every initiative, keyed by its table_name and grouped by department.
//
// Admins see it when choosing what a user can open, and each permission's name is built from it:
// School Education + "PSRP Phase 1" becomes sed.psrp_phase_1
//
// To change one, edit it here and run: php artisan db:seed --class=AccessControlSeeder
// Users keep their access, because permissions are linked to initiatives by id, not by name.
// Names must stay different within a department. An initiative missing from this list gets a
// name made from its table_name.

return [
    // Agriculture (AGR)
    'agr_e_mechanization_fund' => 'E-Mechanization Fund',
    'agr_e_mechanization_fund_2024' => 'E-Mechanization Fund 2024-26',
    'agr_green_tractor_hi_power_phase_5' => 'Green Tractor Hi Power Phase 5',
    'agr_green_tractor_low_power_phase_4' => 'Green Tractor Low Power Phase 4',
    'agr_green_tractor_phase_2' => 'Green Tractor Phase 2',
    'agr_hi_power_tractor_program' => 'Hi Power Tractor',
    'agr_hi_tech_mechanization' => 'Hi-Tech Mechanization',
    'agr_hi_tech_mechanization_2025' => 'Hi-Tech Mechanization 2025-29',
    'agr_internship_graduates' => 'Graduates Internship',
    'agr_internship_graduates_2024' => 'Graduates Internship 2024-26',
    'agr_internship_graduates_phase_2' => 'Graduates Internship Phase 2',
    'agr_kissan_card_phase_1' => 'Kissan Card Phase 1',
    'agr_kissan_card_phase_2' => 'Kissan Card Phase 2',
    'agr_model_agriculture_mall' => 'Model Agriculture Mall',
    'agr_model_agriculture_malls_phase_2' => 'Model Agriculture Malls Phase 2',
    'agr_oilseed_promotion' => 'Oilseed Promotion',
    'agr_oilseed_promotion_2024' => 'Oilseed Promotion 2024-27',
    'agr_pothohar_khushab_2025' => 'Pothohar and Khushab 2025-28',
    'agr_solarization_tubewells' => 'Tubewell Solarization',
    'agr_transformation_pothohar' => 'Pothohar Transformation',
    'agr_transformation_thal' => 'Thal Transformation',
    'agr_water_conservation_barani' => 'Barani Water Conservation',
    'agr_water_conservation_barani_2025' => 'Barani Water Conservation 2025-28',
    'agr_water_efficient_agriculture' => 'Water Efficient Agriculture',
    'agr_water_efficient_agriculture_2025' => 'Water Efficient Agriculture 2025-28',

    // Communication & Works (CW)
    'cw_dehli_multan_babar_drammawala' => 'Dehli-Multan Babar to Drammawala',
    'cw_dehli_multan_drammawala_pull114' => 'Dehli-Multan Drammawala to Pull 114',
    'cw_dehli_multan_pull114_pir_murad' => 'Dehli-Multan Pull 114 to Pir Murad',
    'cw_faisalabad_chiniot_road_dualization' => 'Faisalabad Chiniot Road',
    'cw_gt_road_wahga_border_rehabilitation' => 'GT Road Wahga Border',
    'cw_integrated_road_infrastructure' => 'Integrated Road Infrastructure',
    'cw_multan_vehari_road_rehabilitation' => 'Multan Vehari Road',
    'cw_road_rehabilitation_program_2' => 'Road Rehabilitation 2',
    'cw_roads_restoration_program' => 'Roads Restoration',
    'cw_sarkain_bahal_punjab_kushhal' => 'Sarkain Bahal Punjab Kushhal',

    // Environment & Climate Change (ECC)
    'ecc_climate_leadership_internship_phase_2' => 'Climate Leadership Internship 2',
    'ecc_cm_punjab_green_credit_program' => 'Green Credit',
    'ecc_epa_upgradation_restructuring' => 'EPA Upgradation',
    'ecc_punjab_smog_mitigation_air_safe' => 'Smog Mitigation Air Safe',

    // Energy (ENE)
    'ene_punjab_free_solar_panel_scheme' => 'Free Solar Panel',
    'ene_renewable_energy_technologies' => 'Renewable Energy',
    'ene_solar_power_equipment_indigenization_pc2' => 'Solar Equipment PC-II',

    // Finance (FIN)
    'fin_acquisition_land_shifting_utilities' => 'Land and Utilities',

    // Forest, Wildlife & Fisheries (FWF)
    'fwf_agro_forestry_forest_waste_land' => 'Agro Forestry',
    'fwf_aquaculture_malls_punjab' => 'Aquaculture Malls',
    'fwf_aquaculture_shrimp_farming' => 'Aquaculture Shrimp Farming',
    'fwf_cm_plant_for_pakistan' => 'Plant for Pakistan',
    'fwf_ecotourism_changa_manga_wetlands' => 'Changa Manga Eco-Tourism',
    'fwf_model_fish_market_feasibility' => 'Model Fish Market Feasibility',
    'fwf_model_fish_market_lahore' => 'Model Fish Market',
    'fwf_pangasius_hatchery_facilities' => 'Pangasius Hatchery',
    'fwf_punjab_wildlife_census' => 'Wildlife Census',
    'fwf_shrimp_estate_chak_58nb' => 'Shrimp Estate Chak 58NB',
    'fwf_shrimp_estate_rakh_ali_wali' => 'Shrimp Estate Rakh Ali Wali',
    'fwf_shrimp_estate_shah_garh' => 'Shrimp Estate Shah Garh',
    'fwf_shrimp_farming_feasibility_pc2' => 'Shrimp Farming Feasibility',
    'fwf_shrimp_farms_rakh_ali_wali' => 'Shrimp Farms Rakh Ali Wali',
    'fwf_shrimp_farms_sargodha' => 'Shrimp Farms Sargodha',

    // Higher Education (HED)
    'hed_cm_punjab_laptop_program' => 'Laptop Program',
    'hed_establishment_new_colleges' => 'New Colleges',
    'hed_honhaar_scholarship_program' => 'Honhaar Scholarship',
    'hed_it_labs_colleges' => 'College IT Labs',
    'hed_it_labs_colleges_phase_2' => 'College IT Labs Phase 2',
    'hed_missing_facilities_colleges' => 'College Missing Facilities',
    'hed_punjab_education_endowment_fund' => 'PEEF',
    'hed_punjab_higher_education_commission' => 'PHEC',
    'hed_secretariat_erp_colleges' => 'Secretariat and College ERP',
    'hed_universities_audit_feasibility' => 'Universities Audit',
    'hed_university_sub_campuses' => 'University Sub-Campuses',

    // Health & Population (HEP)
    'hep_cardiac_surgery_units_15_dhq' => 'Cardiac Units 15 DHQ',
    'hep_cath_labs_allied_facilities' => 'Cath Labs Allied Facilities',
    'hep_cath_labs_phase_2' => 'Cath Labs Phase 2',
    'hep_cath_labs_phase_3' => 'Cath Labs Phase 3',
    'hep_clinic_on_wheels' => 'Clinic on Wheels',
    'hep_cm_dialysis_program_card' => 'Dialysis Card',
    'hep_cm_health_reforms_health_clinic' => 'MN Health Clinic Reforms',
    'hep_community_empowerment_health_clinics' => 'MN Health Clinics Community',
    'hep_community_health_inspectors' => 'Community Health Inspectors',
    'hep_ct_scan_machines_24_dhq' => 'CT Scan 24 DHQ',
    'hep_dhq_thq_hospitals' => 'DHQ and THQ Hospitals',
    'hep_dispensaries_mch_to_mnhcs' => 'Dispensaries to MNHCs',
    'hep_equipment_furniture_bhus_rhcs_phase_2' => 'BHU RHC Equipment Phase 2',
    'hep_equipment_furniture_dispensaries_mch' => 'Dispensary MCH Equipment',
    'hep_field_hospitals' => 'Field Hospitals',
    'hep_free_medicine_hepatitis_tb' => 'Free Medicine Hepatitis TB',
    'hep_insulin_card_type_1_children' => 'Insulin Card',
    'hep_missing_equipment_secondary_hospitals' => 'Secondary Hospital Equipment',
    'hep_mri_machines_dhq_hospitals' => 'MRI DHQ Hospitals',
    'hep_population_management_family_planning' => 'Population and Family Planning',
    'hep_reconstruction_80_bhus_6_rhcs' => 'Rebuild 80 BHUs 6 RHCs',
    'hep_revamping_bhus_north_central_phase_2' => 'BHU Revamp North Central 2',
    'hep_revamping_dispensaries_mch_centers' => 'Dispensary MCH Revamp',
    'hep_revamping_non_247_bhus' => 'Non 24/7 BHU Revamp',
    'hep_revamping_non_247_mnhcs' => 'Non 24/7 MNHC Revamp',
    'hep_revamping_remaining_247_bhus' => 'Remaining 24/7 BHU Revamp',
    'hep_revamping_remaining_247_mnhcs' => 'Remaining 24/7 MNHC Revamp',
    'hep_revamping_rhcs_phase_2' => 'RHC Revamp Phase 2',
    'hep_rural_hospitals_pbf' => 'Rural Hospitals PBF',
    'hep_sehat_sahulat_uhi' => 'Sehat Sahulat UHI',

    // Home (HOM)
    'hom_crime_scene_units_pfsa_28_districts' => 'Crime Scene Units',
    'hom_hostel_women_scientists_pafda_pfsa' => 'Women Scientists Hostel',

    // Human Rights & Minority Affairs (HRMA)
    'hrma_minority_card_scheme' => 'Minority Card',
    'hrma_minority_development_phase_2' => 'Minority Development Phase 2',
    'hrma_minority_development_program' => 'Minority Development',

    // Housing Urban Development & Public Health Engineering (HUDPHE)
    'hudphe_apni_chhat_apna_ghar' => 'Apni Chhat Apna Ghar',
    'hudphe_apni_zameen_apna_ghar' => 'Apni Zameen Apna Ghar',
    'hudphe_lahore_development_wasa_9_zones' => 'Lahore WASA 9 Zones',
    'hudphe_lahore_sanitation_package' => 'Lahore Sanitation',
    'hudphe_punjab_development_wasa' => 'Punjab Development WASA',
    'hudphe_saaf_pani_across_punjab' => 'Saaf Pani Across Punjab',
    'hudphe_saaf_pani_program' => 'Saaf Pani',
    'hudphe_watsan_program' => 'WATSAN',

    // Information & Culture (INC)
    'inc_administrative_studio_complex' => 'Studio Complex',
    'inc_al_hamra_arts_council_upgradation' => 'Al-Hamra Upgradation',
    'inc_civic_infrastructure_allied_works' => 'Civic Infrastructure',
    'inc_cm_film_city_program' => 'Film City',
    'inc_convention_centre_lahore' => 'Convention Centre',
    'inc_dgpr_complex_lahore' => 'DGPR Complex',
    'inc_film_city_cinema_feasibility' => 'Film City Feasibility',

    // Industries, Commerce & Investment (IND)
    'ind_asaan_export_finance_smes' => 'Asaan Export Finance',
    'ind_asaan_karobar_card' => 'Asaan Karobar Card',
    'ind_asaan_karobar_finance' => 'Asaan Karobar Finance',
    'ind_asaan_karobar_finance_center' => 'Karobar Finance Center',
    'ind_asaan_karobar_finance_north' => 'Karobar Finance North',
    'ind_asaan_karobar_finance_south' => 'Karobar Finance South',
    'ind_cm_punjab_asaan_karobar' => 'Asaan Karobar',
    'ind_garment_cities_punjab' => 'Garment Cities',

    // Livestock & Dairy Development (LDD)
    'ldd_dairy_supply_chain_support' => 'Dairy Supply Chain',
    'ldd_empowering_rural_women_livestock' => 'Rural Women Livestock',
    'ldd_foot_mouth_disease_control' => 'FMD Control',
    'ldd_herd_transformation_phase_1' => 'Herd Transformation Phase 1',
    'ldd_internship_vets_paravets' => 'Vets Internship',
    'ldd_livestock_asset_transfer_rural_women' => 'Livestock Asset Transfer',
    'ldd_livestock_card' => 'Livestock Card',
    'ldd_livestock_card_phase_2' => 'Livestock Card Phase 2',
    'ldd_livestock_farmers_training' => 'Farmers Training',
    'ldd_mobile_veterinary_dispensaries' => 'Mobile Vet Dispensaries',
    'ldd_model_veterinary_hospital' => 'Model Vet Hospital',
    'ldd_xray_surgical_facilities' => 'X-Ray and Surgical',

    // Local Government & Community Development (LGCD)
    'lgcd_lahore_development_mcl_3_zones' => 'Lahore MCL 3 Zones',
    'lgcd_lahore_development_mcl_6_zones' => 'Lahore MCL 6 Zones',
    'lgcd_lahore_development_program' => 'Lahore Development',
    'lgcd_lahr_program' => 'LAHR',
    'lgcd_lahr_programme_phase_2' => 'LAHR Phase 2',
    'lgcd_model_carts_bazaar' => 'Model Carts Bazaar',
    'lgcd_model_village_misali_gaon' => 'Misali Gaon',
    'lgcd_model_village_municipal_services' => 'Misali Gaon Municipal Services',
    'lgcd_murree_development_phase_2' => 'Murree Development 2',
    'lgcd_murree_development_program' => 'Murree Development',
    'lgcd_punjab_cities_beautification' => 'Cities Beautification',
    'lgcd_punjab_development_program' => 'Punjab Development',
    'lgcd_rural_sanitation_program' => 'Rural Sanitation',
    'lgcd_sustainable_development_goals' => 'SDGs',
    'lgcd_suthra_punjab' => 'Suthra Punjab',

    // Labour & Human Resource (LHR)
    'lhr_social_security_ration_card' => 'Workers Ration Card',

    // Mines & Minerals (MM)
    'mm_mines_minerals_ration_card' => 'Minerals Ration Card',

    // Price Control & Commodities Management (PCCM)
    'pccm_cm_sahulat_bazaars_punjab' => 'Sahulat Bazaars',

    // Punjab Information Technology Board (PITB)
    'pitb_accelerate_punjab' => 'Accelerate Punjab',
    'pitb_ai_emerging_technologies_governance' => 'AI for Governance',
    'pitb_cm_complaint_helpline' => 'Complaint Helpline',
    'pitb_cm_it_internship_program' => 'IT Internship',
    'pitb_cm_it_park' => 'IT Park',
    'pitb_global_it_certifications' => 'Global IT Certifications',
    'pitb_makerspace_punjab' => 'Makerspace',
    'pitb_punjab_dastak_program' => 'Dastak',
    'pitb_punjab_ecommerce_initiative' => 'E-Commerce',

    // Planning & Development (PND)
    'pnd_accelerate_punjab' => 'Accelerate Punjab',
    'pnd_ai_emerging_technologies_governance' => 'AI for Governance',
    'pnd_cm_complaint_helpline' => 'Complaint Helpline',
    'pnd_cm_equalization_south_punjab' => 'South Punjab Equalization',
    'pnd_cm_it_internship_phase_2' => 'IT Internship Phase 2',
    'pnd_cm_it_internship_program' => 'IT Internship',
    'pnd_cm_priority_program' => 'Priority Program',
    'pnd_global_it_certifications' => 'Global IT Certifications',
    'pnd_pser_door_to_door_survey_pc2' => 'PSER Survey PC-II',
    'pnd_punjab_dastak_programme' => 'Dastak',
    'pnd_punjab_ecommerce_initiative' => 'E-Commerce',
    'pnd_punjab_socio_economic_registry' => 'PSER',
    'pnd_uplifting_priority_areas' => 'Priority Areas',

    // Police (POL)
    'pol_cm_safe_cities_program' => 'Safe Cities',
    'pol_expansion_ipnv_sites_lahore' => 'IPNV Sites Lahore',
    'pol_safe_city_kacha_area' => 'Safe City Kacha',

    // Punjab Safe City Authority (PSCA)
    'psca_cm_maryam_nawaz_free_wifi' => 'Free Wifi',
    'psca_punjab_smart_safe_cities_program' => 'Smart Safe Cities',
    'psca_virtual_center_child_safety' => 'Child Safety Center',
    'psca_virtual_women_police_station' => 'Women Police Station',

    // Skills Development & Entrepreneurship (SDE)
    'sde_advance_technology_program' => 'Advance Technology',
    'sde_cm_skills_development_it_tevta' => 'TEVTA IT Courses',
    'sde_construction_hospitality_labs_tevta' => 'TEVTA Construction Labs',
    'sde_language_learning_improvements' => 'Language Learning',
    'sde_parwaaz_card_international_placement' => 'Parwaaz Card',
    'sde_punjab_industrial_clusters_pathways' => 'Industrial Clusters',
    'sde_rural_women_it_phase_2' => 'Rural Women IT 2',
    'sde_skill_development_transgenders' => 'Transgender Skills',
    'sde_skilled_punjab_program' => 'Skilled Punjab',
    'sde_skills_agriculture_green_environment' => 'SAGE',
    'sde_tevta_overseas_employment_promoter' => 'TEVTA Overseas Employment',
    'sde_umeed_skills_for_inclusion' => 'Umeed Skills',
    'sde_women_it_training_punjab' => 'Women IT Training',
    'sde_youth_employability_bpos' => 'Youth BPO Employability',

    // School Education (SED)
    'afternoon_school_program' => 'Afternoon School',
    'construction_additional_classrooms_peima' => 'Additional Classrooms',
    'maryam_nawaz_academic_leadership_center' => 'Academic Leadership Center',
    'missing_facilities_it_labs_peima' => 'Missing Facilities and IT Labs',
    'nawaz_sharif_centre_excellence_10_divisions' => 'NS Excellence 10 Divisions',
    'nawaz_sharif_centre_excellence_ece_phase_2' => 'NS Excellence ECE Phase 2',
    'nawaz_sharif_centre_excellence_phase_1' => 'NS Excellence Phase 1',
    'nawaz_sharif_school_eminence_jalalpur_pirwala' => 'Eminence Jalalpur Pirwala',
    'public_school_reorganization_phase_1' => 'PSRP Phase 1',
    'public_school_reorganization_phase_2' => 'PSRP Phase 2',
    'public_school_reorganization_phase_3' => 'PSRP Phase 3',
    'punjab_education_training_assessment_authority' => 'PECTAA',
    'punjab_schools_meal_program' => 'School Meal',
    'reconstruction_dangerous_buildings_peima' => 'Dangerous Buildings',
    'schools_of_eminence_existing_infrastructure' => 'Schools of Eminence',
    'spoken_english_initiative_pef' => 'Spoken English PEF',
    'steam_labs_punjab' => 'STEAM Labs',
    'water_filtration_plants_3_schools' => 'Water Filtration Plants',

    // Specialized Healthcare & Medical Education (SHCME)
    'shcme_cardiology_institute_sargodha' => 'Cardiology Sargodha',
    'shcme_children_heart_surgery_program' => 'Children Heart Surgery',
    'shcme_children_hospital_bahawalpur' => 'Children Hospital Bahawalpur',
    'shcme_cm_cancer_patients_program' => 'Cancer Patients',
    'shcme_cm_cardiac_surgery_program' => 'Cardiac Surgery',
    'shcme_cm_transplant_program' => 'Transplant',
    'shcme_home_delivery_free_medicine' => 'Free Medicine Home Delivery',
    'shcme_nawaz_sharif_cancer_institute' => 'Cancer Institute Lahore',
    'shcme_nawaz_sharif_medical_district_1' => 'Medical District 1',
    'shcme_nawaz_sharif_medical_district_2' => 'Medical District 2',
    'shcme_pimh_old_blocks_revamping' => 'PIMH Old Blocks',

    // Special Education (SPE)
    'spe_autism_school_lahore' => 'Autism School Lahore',
    'spe_autism_school_satellite_campuses' => 'Autism Satellite Campuses',
    'spe_cm_meal_program_special_students' => 'Special Students Meal',
    'spe_maryam_nawaz_khud_mukhtar_program' => 'Khud-Mukhtar',
    'spe_surveillance_special_schools_phase_2' => 'School Surveillance Phase 2',
    'spe_transport_special_education' => 'Special Education Transport',

    // Social Welfare (SW)
    'sw_assistive_devices_wheelchair_program' => 'Assistive Devices',
    'sw_cm_himmat_card_program' => 'Himmat Card',
    'sw_family_support_mediation_sukh_da_vehra' => 'Sukh Da Vehra',
    'sw_flood_relief_2025' => 'Flood Relief 2025',
    'sw_old_age_homes_aafiat' => 'Old Age Homes',
    'sw_punjab_dhee_rani_programme' => 'Dhee Rani',
    'sw_upgradation_sanatzars_punjab' => 'Sanatzars Upgradation',

    // Sports & Youth Affairs (SYA)
    'sya_cm_internship_program' => 'Internship',
    'sya_cm_khelta_punjab' => 'Khelta Punjab',
    'sya_cm_youth_engagement_programme' => 'Youth Engagement',
    'sya_maryam_nawaz_sports_city_lahore' => 'Sports City Lahore',
    'sya_rehabilitation_sports_facilities' => 'Sports Facilities Rehabilitation',
    'sya_revival_sports_punjab' => 'Revival of Sports',

    // Tourism, Archaeology & Museums (TAM)
    'tam_cm_tourism_internship' => 'Tourism Internship',
    'tam_derawar_fort_bahawalpur' => 'Derawar Fort',
    'tam_harrapa_sahiwal' => 'Harrapa',
    'tam_hiran_minar_sheikhupura' => 'Hiran Minar',
    'tam_javaid_manzil_iqbal_museum' => 'Javaid Manzil',
    'tam_lahore_heritage' => 'Lahore Heritage',
    'tam_lahore_museum_master_plan' => 'Lahore Museum',
    'tam_north_potohar_leisure_belt' => 'Potohar Leisure Belt',
    'tam_religious_trail_punjab' => 'Religious Trail',
    'tam_rohtas_fort_jhelum' => 'Rohtas Fort',
    'tam_st_andrews_church_lahore' => 'St Andrews Church',
    'tam_summit_minar_lahore' => 'Summit Minar',
    'tam_taxila_world_heritage_site' => 'Taxila',
    'tam_tdcp_changa_manga' => 'TDCP Changa Manga',
    'tam_tdcp_derawar_fort_cholistan' => 'TDCP Derawar Fort',
    'tam_tdcp_dharabi_dam' => 'TDCP Dharabi Dam',
    'tam_tdcp_indus_picnic_resort' => 'TDCP Indus Picnic Resort',
    'tam_tdcp_jallo_lahore' => 'TDCP Jallo',
    'tam_tdcp_kallar_kahar' => 'TDCP Kallar Kahar',
    'tam_tdcp_khabbeki_lake' => 'TDCP Khabbeki Lake',
    'tam_tdcp_patriata_chairlift' => 'TDCP Patriata Chairlift',
    'tam_tourism_internship_phase_2' => 'Tourism Internship Phase 2',
    'tam_trimmu_headworks_jhang' => 'Trimmu Headworks',

    // Transport & Mass Transit (TMT)
    'tmt_100000_electric_bikes_pcap' => '100,000 Electric Bikes',
    'tmt_630_eco_friendly_buses_depots' => '630 Buses and Depots',
    'tmt_air_punjab_feasibility' => 'Air Punjab Feasibility',
    'tmt_cm_bus_depots_program' => 'Bus Depots',
    'tmt_cm_induction_buses_program' => 'Bus Induction',
    'tmt_empower_her_bus_shelter' => 'Empower Her Bus Shelter',
    'tmt_faisalabad_transport_modelling' => 'Faisalabad Transport Modelling',
    'tmt_gujranwala_mass_transit_feasibility' => 'Gujranwala Transit Feasibility',
    'tmt_high_speed_rail_feasibility' => 'High Speed Rail Feasibility',
    'tmt_high_speed_rail_lahore_rawalpindi' => 'High Speed Rail',
    'tmt_interest_free_bikes_students' => 'Student Interest-Free Bikes',
    'tmt_interest_free_electric_taxi' => 'Interest-Free Electric Taxi',
    'tmt_lahore_electric_bus_depot_pcap' => 'Lahore Electric Bus Depot',
    'tmt_lahore_mass_transit_corridors' => 'Lahore Transit Corridors',
    'tmt_lahore_mass_transit_feasibility' => 'Lahore Transit Feasibility',
    'tmt_lahore_metro_green_line_stations' => 'Lahore Green Line Stations',
    'tmt_lahore_yellow_line_seed_money' => 'Lahore Yellow Line',
    'tmt_mass_transit_faisalabad' => 'Faisalabad Mass Transit',
    'tmt_mass_transit_gujranwala' => 'Gujranwala Mass Transit',
    'tmt_mass_transit_pmu' => 'Mass Transit PMU',
    'tmt_mass_transit_sargodha_feasibility' => 'Sargodha Transit Feasibility',
    'tmt_multan_electric_bus_depot' => 'Multan Electric Bus Depot',
    'tmt_multimodal_terminals_lahore' => 'Lahore Multimodal Terminals',
    'tmt_regional_railways_feasibility' => 'Regional Railways Feasibility',
    'tmt_road_safety_audit_feasibility' => 'Road Safety Audit',
    'tmt_tourist_glass_train_murree' => 'Murree Glass Train',
    'tmt_transport_tower_lahore' => 'Lahore Transport Tower',

    // Women Development (WDD)
    'wdd_day_care_centers_punjab' => 'Day Care Centers',
];
