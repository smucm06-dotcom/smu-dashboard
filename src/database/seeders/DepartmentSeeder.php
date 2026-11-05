<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $departments = [
            ['code' => 'AGR', 'name' => 'Agriculture'],
            ['code' => 'CW', 'name' => 'Communication & Works'],
            ['code' => 'ENE', 'name' => 'Energy'],
            ['code' => 'ECC', 'name' => 'Environment & Climate Change'],
            ['code' => 'FIN', 'name' => 'Finance'],
            ['code' => 'FWF', 'name' => 'Forest, Wildlife & Fisheries'],
            ['code' => 'HEP', 'name' => 'Health & Population'],
            ['code' => 'HED', 'name' => 'Higher Education'],
            ['code' => 'HOM', 'name' => 'Home'],
            ['code' => 'HUDPHE', 'name' => 'Housing Urban Development & Public Health Engineering'],
            ['code' => 'HRMA', 'name' => 'Human Rights & Minority Affairs'],
            ['code' => 'IC', 'name' => 'Implementation and Coordination'],
            ['code' => 'IND', 'name' => 'Industries, Commerce & Investment'],
            ['code' => 'ITG', 'name' => 'Information Technology & Governance'],
            ['code' => 'INC', 'name' => 'Information & Culture'],
            ['code' => 'LHR', 'name' => 'Labour & Human Resource'],
            ['code' => 'LDD', 'name' => 'Livestock & Dairy Development'],
            ['code' => 'LGCD', 'name' => 'Local Government & Community Development'],
            ['code' => 'MIN', 'name' => 'Minorities'],
            ['code' => 'MM', 'name' => 'Mines & Minerals'],
            ['code' => 'PND', 'name' => 'Planning & Development'],
            ['code' => 'POL', 'name' => 'Police'],
            ['code' => 'PCCM', 'name' => 'Price Control & Commodities Management'],
            ['code' => 'PITB', 'name' => 'Punjab Information Technology Board'],
            ['code' => 'PSCA', 'name' => 'Punjab Safe City Authority'],
            ['code' => 'SED', 'name' => 'School Education'],
            ['code' => 'SGA', 'name' => 'Service & General Administrative'],
            ['code' => 'SDE', 'name' => 'Skills Development & Entrepreneurship'],
            ['code' => 'SW', 'name' => 'Social Welfare'],
            ['code' => 'SP', 'name' => 'Social Protection'],
            ['code' => 'SPE', 'name' => 'Special Education'],
            ['code' => 'SHCME', 'name' => 'Specialized Healthcare & Medical Education'],
            ['code' => 'SYA', 'name' => 'Sports & Youth Affairs'],
            ['code' => 'TAM', 'name' => 'Tourism, Archaeology & Museums'],
            ['code' => 'TMT', 'name' => 'Transport & Mass Transit'],
            ['code' => 'ULD', 'name' => 'Urban & Local Development'],
            ['code' => 'WDD', 'name' => 'Women Development'],
            ['code' => 'WASA', 'name' => 'Water & Sanitation Agency'],
        ];

        $records = array_map(static fn (array $department): array => [
            ...$department,
            'created_at' => $now,
            'updated_at' => $now,
        ], $departments);

        DB::table('departments')->upsert(
            $records,
            ['code'],
            ['name', 'updated_at']
        );
    }
}
