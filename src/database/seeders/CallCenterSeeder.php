<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Initiative;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Call center campaigns: the call center's feedback calls, not SMU's field visits. They open from the Call
// Center menu and are never listed or counted on the Departments pages (config/call_center.php). Each still
// belongs to the department whose programme it reviews, because access to a page is given per department and
// per page (see AccessControlSeeder).
class CallCenterSeeder extends Seeder
{
    public function run(): void
    {
        $campaigns = [
            [
                'department' => 'HED',
                'name' => 'CM Punjab Honhaar Scholarship',
                'table_name' => 'hed_honhaar_scholarship',
            ],
        ];

        DB::transaction(function () use ($campaigns) {
            foreach ($campaigns as $campaign) {
                Initiative::updateOrCreate(
                    ['table_name' => $campaign['table_name']],
                    [
                        'department_id' => Department::where('code', $campaign['department'])->firstOrFail()->id,
                        'name' => $campaign['name'],
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
