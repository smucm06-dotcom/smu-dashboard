<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Departments come first, because every initiative seeder looks up its department.
        $this->call([
            AdminSeeder::class,
            DepartmentSeeder::class,

            Initiatives\AgricultureSeeder::class,
            Initiatives\CommunicationWorksSeeder::class,
            Initiatives\EducationSeeder::class,
            Initiatives\EnergySeeder::class,
            Initiatives\EnvironmentClimateChangeSeeder::class,
            Initiatives\FinanceSeeder::class,
            Initiatives\ForestWildlifeFisheriesSeeder::class,
            Initiatives\HealthPopulationSeeder::class,
            Initiatives\HigherEducationSeeder::class,
            Initiatives\HomeSeeder::class,
            Initiatives\HousingUrbanDevelopmentSeeder::class,
            Initiatives\HumanRightsMinorityAffairsSeeder::class,
            Initiatives\IndustriesCommerceInvestmentSeeder::class,
            Initiatives\InformationCultureSeeder::class,
            Initiatives\LabourHumanResourceSeeder::class,
            Initiatives\LivestockDairyDevelopmentSeeder::class,
            Initiatives\LocalGovernmentSeeder::class,
            Initiatives\MinesMineralsSeeder::class,
            Initiatives\PlanningDevelopmentSeeder::class,
            Initiatives\PoliceSeeder::class,
            Initiatives\PriceControlCommoditiesSeeder::class,
            Initiatives\PunjabInformationTechnologyBoardSeeder::class,
            Initiatives\PunjabSafeCitiesAuthoritySeeder::class,
            Initiatives\SkillsDevelopmentSeeder::class,
            Initiatives\SocialWelfareSeeder::class,
            Initiatives\SpecialEducationSeeder::class,
            Initiatives\SpecializedHealthcareSeeder::class,
            Initiatives\SportsYouthAffairsSeeder::class,
            Initiatives\TourismArchaeologyMuseumsSeeder::class,
            Initiatives\TransportMassTransitSeeder::class,
            Initiatives\WomenDevelopmentSeeder::class,
        ]);
    }
}
