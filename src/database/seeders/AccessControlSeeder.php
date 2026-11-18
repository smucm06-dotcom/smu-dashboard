<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Initiative;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

// Creates the admin role, one role per department and one permission per initiative. Run it again
// after adding departments or initiatives: it only adds or renames, and users keep their access.
class AccessControlSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        $shortNames = require database_path('data/initiative_short_names.php');

        DB::transaction(function () use ($shortNames) {
            Role::updateOrCreate(['name' => Role::ADMIN, 'guard_name' => self::GUARD]);

            foreach (Department::with('initiatives')->get() as $department) {
                $code = strtolower($department->code);

                Role::updateOrCreate(
                    ['department_id' => $department->id],
                    ['name' => $code, 'guard_name' => self::GUARD]
                );

                foreach ($department->initiatives as $initiative) {
                    $shortName = $shortNames[$initiative->table_name] ?? $this->shortNameFromTable($initiative, $code);

                    $initiative->update(['short_name' => $shortName]);

                    Permission::updateOrCreate(
                        ['initiative_id' => $initiative->id],
                        ['name' => $code.'.'.Str::slug($shortName, '_'), 'guard_name' => self::GUARD]
                    );
                }
            }

            $this->makeSureThereIsAnAdmin();
        });

        // Spatie keeps the list of roles and permissions in the cache.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function shortNameFromTable(Initiative $initiative, string $code): string
    {
        return Str::limit(Str::headline(Str::after($initiative->table_name, $code.'_')), 57);
    }

    // Only when nobody is an admin, so running this again never undoes an admin's own changes.
    private function makeSureThereIsAnAdmin(): void
    {
        if (! User::role(Role::ADMIN)->exists()) {
            User::where('email', AdminSeeder::EMAIL)->first()?->assignRole(Role::ADMIN);
        }
    }
}
