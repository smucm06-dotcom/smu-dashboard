<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Department $school;

    private Department $energy;

    private Initiative $phaseOne;

    private Initiative $phaseTwo;

    private Initiative $solar;

    private User $admin;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->school = Department::create(['code' => 'SED', 'name' => 'School Education']);
        $this->energy = Department::create(['code' => 'ENE', 'name' => 'Energy']);

        $this->phaseOne = $this->initiative($this->school, 'public_school_reorganization_phase_1', 'Public Schools Re-Organization Program (PSRP) – Phase I');
        $this->phaseTwo = $this->initiative($this->school, 'public_school_reorganization_phase_2', 'Public Schools Re-Organization Program (PSRP) – Phase II');
        $this->solar = $this->initiative($this->energy, 'ene_punjab_free_solar_panel_scheme', "Chief Minister's Punjab Free Solar Panel Scheme");

        $this->seed(AccessControlSeeder::class);

        $this->admin = User::factory()->create()->assignRole(Role::ADMIN);

        // Belongs to School Education and can open Phase 1 only.
        $this->viewer = User::factory()->create()->assignRole('sed');
        $this->viewer->givePermissionTo('sed.psrp_phase_1');
    }

    private function initiative(Department $department, string $table, string $name): Initiative
    {
        return Initiative::create(['department_id' => $department->id, 'table_name' => $table, 'name' => $name, 'is_active' => true]);
    }

    private function storedFile(Initiative $initiative): InitiativeFile
    {
        return InitiativeFile::storeUpload($initiative, UploadedFile::fake()->create('report.pdf', 5, 'application/pdf'), InitiativeFile::TYPE_OUR_REPORT);
    }

    // Past the access check. The request itself may still be refused for its contents, such as an empty upload.
    private function assertAllowed($response): void
    {
        $this->assertNotSame(403, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    private function validUser(array $changes = []): array
    {
        return array_merge([
            'name' => 'Ali Khan',
            'email' => 'ali@example.test',
            'password' => 'Secret1234',
            'password_confirmation' => 'Secret1234',
            'access' => 'departments',
            'departments' => ['sed'],
            'permissions' => [$this->phaseOne->permission->id],
        ], $changes);
    }

    // Roles and permissions

    public function test_seeder_makes_one_role_per_department_and_one_permission_per_initiative(): void
    {
        $this->assertSame(['ene', 'sed'], Role::whereNotNull('department_id')->orderBy('name')->pluck('name')->all());
        $this->assertTrue(Role::where('name', Role::ADMIN)->whereNull('department_id')->exists());

        $this->assertSame('sed.psrp_phase_1', $this->phaseOne->permission->name);
        $this->assertSame('ene.free_solar_panel', $this->solar->permission->name);
        $this->assertSame('PSRP Phase 1', $this->phaseOne->fresh()->short_name);
    }

    public function test_department_roles_hold_no_permissions(): void
    {
        $this->assertSame(0, DB::table('role_has_permissions')->count());
    }

    public function test_running_the_seeder_again_keeps_users_access(): void
    {
        $this->seed(AccessControlSeeder::class);

        $this->assertSame(3, Permission::count());
        $this->assertTrue($this->viewer->fresh()->canOpenInitiative($this->phaseOne));
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_renaming_a_permission_keeps_users_access(): void
    {
        $permission = $this->phaseOne->permission;
        $permission->update(['name' => 'sed.old_name']);

        $this->seed(AccessControlSeeder::class);

        $this->assertSame('sed.psrp_phase_1', $permission->fresh()->name);
        $this->assertTrue($this->viewer->fresh()->canOpenInitiative($this->phaseOne));
    }

    public function test_an_initiative_missing_from_the_list_still_gets_a_permission(): void
    {
        $new = $this->initiative($this->energy, 'ene_new_wind_project', 'New Wind Project');

        $this->seed(AccessControlSeeder::class);

        $this->assertSame('ene.new_wind_project', $new->permission->name);
    }

    public function test_the_full_seed_covers_every_initiative_with_a_short_name(): void
    {
        $_SERVER['ADMIN_PASSWORD'] = 'Testing1234';

        $this->seed(DatabaseSeeder::class);

        // Since 16 Nov the seeders hold only the initiatives that are built; the rest wait, commented
        // out, in their seeders, while the list of short names keeps them all. So every seeded
        // initiative needs a permission and a listed short name, but not the other way round.
        $this->assertGreaterThan(0, Initiative::count());
        $this->assertSame(Initiative::count(), Permission::count());
        $this->assertSame(Department::count() + 1, Role::count());

        $listed = array_keys(require database_path('data/initiative_short_names.php'));

        $this->assertSame([], array_values(array_diff(Initiative::pluck('table_name')->all(), $listed)));
        $this->assertTrue(User::where('email', 'admin@smu.local')->first()->exists);
    }

    public function test_seeding_does_not_reset_the_admin_password(): void
    {
        $_SERVER['ADMIN_PASSWORD'] = 'Testing1234';

        $user = User::factory()->create(['email' => 'admin@smu.local', 'password' => 'Changed1234']);

        $this->seed(AdminSeeder::class);

        $this->assertTrue(Hash::check('Changed1234', $user->fresh()->password));
    }

    // Initiative routes

    public static function readRoutes(): array
    {
        return [
            'page' => ['get', fn ($self, $i) => route('initiatives.show', $i)],
            'phase 1 data' => ['get', fn ($self, $i) => route('initiatives.psrp-phase-1.data', $i)],
            'phase 1 export' => ['get', fn ($self, $i) => route('initiatives.psrp-phase-1.export', $i)],
            'phase 1 map download' => ['post', fn ($self, $i) => route('initiatives.psrp-phase-1.map-download', $i)],
            'file download' => ['get', fn ($self, $i) => route('initiatives.files.download', [$i, $self->storedFile($i)])],
        ];
    }

    public static function writeRoutes(): array
    {
        return [
            'upload department list' => ['post', fn ($self, $i) => route('initiatives.files.store', [$i, 'type' => InitiativeFile::TYPE_DEPARTMENT_LIST])],
            'upload department response' => ['post', fn ($self, $i) => route('initiatives.files.store', [$i, 'type' => InitiativeFile::TYPE_DEPARTMENT_RESPONSE])],
            'delete file' => ['delete', fn ($self, $i) => route('initiatives.files.destroy', [$i, $self->storedFile($i)])],
            'phase 1 import' => ['post', fn ($self, $i) => route('initiatives.psrp-phase-1.import', $i)],
            'phase 2 import' => ['post', fn ($self, $i) => route('initiatives.psrp-phase-2.import', $i)],
        ];
    }

    #[DataProvider('readRoutes')]
    public function test_a_user_with_the_permission_can_read(string $method, \Closure $url): void
    {
        $this->assertAllowed($this->actingAs($this->viewer)->{$method}($url($this, $this->phaseOne)));
    }

    #[DataProvider('readRoutes')]
    public function test_a_user_without_the_permission_cannot_read(string $method, \Closure $url): void
    {
        $this->actingAs($this->viewer)->{$method}($url($this, $this->phaseTwo))->assertForbidden();
    }

    #[DataProvider('readRoutes')]
    public function test_a_user_cannot_read_another_departments_initiative(string $method, \Closure $url): void
    {
        $this->actingAs($this->viewer)->{$method}($url($this, $this->solar))->assertForbidden();
    }

    #[DataProvider('writeRoutes')]
    public function test_a_department_user_cannot_upload_or_delete_even_with_the_permission(string $method, \Closure $url): void
    {
        $this->actingAs($this->viewer)->{$method}($url($this, $this->phaseOne))->assertForbidden();
    }

    #[DataProvider('writeRoutes')]
    public function test_an_admin_is_not_blocked_from_uploading_or_deleting(string $method, \Closure $url): void
    {
        $this->assertAllowed($this->actingAs($this->admin)->{$method}($url($this, $this->phaseOne)));
    }

    #[DataProvider('readRoutes')]
    public function test_an_admin_can_read_everything(string $method, \Closure $url): void
    {
        $this->assertAllowed($this->actingAs($this->admin)->{$method}($url($this, $this->phaseOne)));
    }

    public function test_an_admin_upload_still_saves_and_a_viewer_can_then_download_it(): void
    {
        $csv = "Timestamp,EMIS,Students Enrolled\n2025-02-01 09:00:00,5001,10\n2025-02-02 09:00:00,5002,20\n";

        $this->actingAs($this->admin)
            ->post(route('initiatives.psrp-phase-1.import', $this->phaseOne), ['file' => UploadedFile::fake()->createWithContent('visits.csv', $csv)])
            ->assertRedirect();

        $this->assertSame(2, DB::table('public_school_reorganization_phase_1')->count());

        $file = InitiativeFile::where('initiative_id', $this->phaseOne->id)->firstOrFail();

        $this->assertSame($this->admin->id, $file->uploaded_by);

        $this->actingAs($this->viewer)
            ->get(route('initiatives.files.download', [$this->phaseOne, $file]))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->delete(route('initiatives.files.destroy', [$this->phaseOne, $file]))
            ->assertForbidden();

        $this->assertTrue(Storage::disk('local')->exists($file->stored_path));
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('initiatives.show', $this->phaseOne))->assertRedirect(route('login'));
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_the_access_denied_page_uses_the_portal_layout(): void
    {
        $this->actingAs($this->viewer)->get(route('initiatives.show', $this->phaseTwo))
            ->assertForbidden()
            ->assertSee('You do not have access to this page')
            ->assertSee('app-sidebar', false);
    }

    public function test_a_department_user_can_upload_a_report_pdf_but_not_delete_it(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('field-report.pdf', "%PDF-1.4\n%test\n");

        $this->actingAs($this->viewer)
            ->post(route('initiatives.files.store', $this->phaseOne), ['type' => InitiativeFile::TYPE_OUR_REPORT, 'file' => $pdf])
            ->assertRedirect()
            ->assertSessionHas('initiativeFileSaved');

        $file = InitiativeFile::where('initiative_id', $this->phaseOne->id)->where('type', InitiativeFile::TYPE_OUR_REPORT)->firstOrFail();

        $this->assertSame($this->viewer->id, $file->uploaded_by);
        $this->assertTrue(Storage::disk('local')->exists($file->stored_path));

        $this->actingAs($this->viewer)
            ->get(route('initiatives.files.download', [$this->phaseOne, $file]))
            ->assertOk();

        $this->actingAs($this->viewer)
            ->delete(route('initiatives.files.destroy', [$this->phaseOne, $file]))
            ->assertForbidden();

        $this->assertModelExists($file);
        $this->assertTrue(Storage::disk('local')->exists($file->stored_path));
    }

    public function test_a_department_user_cannot_upload_a_report_where_they_have_no_access(): void
    {
        foreach ([$this->phaseTwo, $this->solar] as $initiative) {
            $this->actingAs($this->viewer)
                ->post(route('initiatives.files.store', $initiative), ['type' => InitiativeFile::TYPE_OUR_REPORT, 'file' => UploadedFile::fake()->createWithContent('r.pdf', "%PDF-1.4\n")])
                ->assertForbidden();
        }

        $this->assertSame(0, InitiativeFile::count());
    }

    public function test_a_department_user_cannot_upload_a_pdf_as_a_department_response(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('initiatives.files.store', $this->phaseOne), ['type' => InitiativeFile::TYPE_DEPARTMENT_RESPONSE, 'file' => UploadedFile::fake()->createWithContent('letter.pdf', "%PDF-1.4\n")])
            ->assertForbidden();

        $this->assertSame(0, InitiativeFile::count());
    }

    public function test_viewers_get_only_the_report_upload_and_no_delete_controls(): void
    {
        $this->storedFile($this->phaseOne);

        $page = $this->actingAs($this->viewer)->get(route('initiatives.show', $this->phaseOne))
            ->assertOk()
            ->assertDontSee('Upload CSV')
            ->assertSee('Upload report')
            ->assertDontSee('Delete report.pdf')
            ->assertSee('Visit files')
            ->assertSee('Department responses')
            ->assertSee('report.pdf');

        // One upload form, the report's; none for visits, the department list or responses.
        $this->assertSame(1, substr_count($page->getContent(), 'class="ifile-form"'));
        $this->assertStringContainsString('name="type" value="'.InitiativeFile::TYPE_OUR_REPORT.'"', $page->getContent());
        $this->assertStringNotContainsString('name="type" value="'.InitiativeFile::TYPE_DEPARTMENT_LIST.'"', $page->getContent());
        $this->assertStringNotContainsString('name="type" value="'.InitiativeFile::TYPE_DEPARTMENT_RESPONSE.'"', $page->getContent());

        $this->actingAs($this->admin)->get(route('initiatives.show', $this->phaseOne))
            ->assertOk()
            ->assertSee('Upload CSV')
            ->assertSee('Delete report.pdf')
            ->assertSee('class="ifile-form"', false);
    }

    // Department pages

    public function test_a_department_user_goes_straight_to_their_department(): void
    {
        $this->actingAs($this->viewer)->get(route('departments.index'))
            ->assertRedirect(route('departments.show', $this->school));
    }

    public function test_a_department_user_sees_only_their_initiatives_and_counts(): void
    {
        // A report on each School Education initiative. The viewer may open Phase 1 only.
        $this->storedFile($this->phaseOne);
        $this->storedFile($this->phaseTwo);

        // Cards: Total Initiatives, Field visits by SMU, Reports sent to department, Visits in the last 30 days.
        $this->actingAs($this->viewer)->get(route('departments.show', $this->school))
            ->assertOk()
            ->assertSee($this->phaseOne->name)
            ->assertDontSee($this->phaseTwo->name)
            ->assertDontSee('Back to Departments')
            ->assertViewHas('topbar', fn ($topbar) => $topbar['stats'][0]['value'] === 1 && $topbar['stats'][2]['value'] === 1);

        $this->actingAs($this->admin)->get(route('departments.show', $this->school))
            ->assertOk()
            ->assertViewHas('topbar', fn ($topbar) => $topbar['stats'][0]['value'] === 2 && $topbar['stats'][2]['value'] === 2);
    }

    public function test_a_department_user_cannot_open_another_department(): void
    {
        $this->actingAs($this->viewer)->get(route('departments.show', $this->energy))->assertForbidden();
    }

    public function test_a_user_with_no_initiatives_is_told_so(): void
    {
        $user = User::factory()->create()->assignRole('sed');

        $this->actingAs($user)->get(route('departments.show', $this->school))
            ->assertOk()
            ->assertSee('No initiatives have been given to your account yet');
    }

    public function test_an_admin_sees_every_department_and_initiative(): void
    {
        $this->actingAs($this->admin)->get(route('departments.index'))->assertOk()->assertSee('Energy')->assertSee('School Education');

        $this->actingAs($this->admin)->get(route('departments.show', $this->school))
            ->assertOk()
            ->assertSee($this->phaseOne->name)
            ->assertSee($this->phaseTwo->name)
            ->assertSee('Back to Departments');
    }

    // Users tab

    public function test_only_admins_see_the_users_tab(): void
    {
        $this->actingAs($this->admin)->get(route('departments.index'))->assertSee(route('users.index'), false);

        $this->actingAs($this->viewer)->get(route('departments.show', $this->school))
            ->assertDontSee(route('users.index'), false)
            ->assertSee(route('settings.index'), false);
    }

    public function test_department_users_cannot_reach_any_users_page(): void
    {
        $this->actingAs($this->viewer);

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $this->admin))->assertForbidden();
        $this->post(route('users.store'), $this->validUser())->assertForbidden();
        $this->put(route('users.update', $this->admin), $this->validUser())->assertForbidden();
        $this->patch(route('users.status', $this->admin), ['active' => 0])->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_active);
        $this->assertFalse(User::where('email', 'ali@example.test')->exists());
    }

    public function test_an_admin_can_add_a_department_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['email' => 'ALI@Example.test', 'permissions' => [$this->phaseOne->permission->id, $this->phaseTwo->permission->id]]))
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('Secret1234', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->is_active);
        $this->assertSame(['sed'], $user->getRoleNames()->all());
        $this->assertSame(['sed.psrp_phase_1', 'sed.psrp_phase_2'], $user->getPermissionNames()->sort()->values()->all());
    }

    public function test_an_initiative_from_another_department_is_refused_and_nothing_is_saved(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['permissions' => [$this->phaseOne->permission->id, $this->solar->permission->id]]))
            ->assertSessionHasErrors('permissions');

        $this->assertFalse(User::where('email', 'ali@example.test')->exists());
    }

    public function test_a_made_up_permission_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['permissions' => [999999]]))
            ->assertSessionHasErrors('permissions');
    }

    public function test_an_admin_user_gets_no_initiative_permissions(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['access' => 'admin', 'permissions' => [$this->solar->permission->id]]))
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();

        $this->assertTrue($user->isAdmin());
        $this->assertSame(0, $user->permissions()->count());
    }

    public function test_add_user_validation(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('users.store'), $this->validUser(['email' => $this->viewer->email]))->assertSessionHasErrors('email');
        $this->post(route('users.store'), $this->validUser(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post(route('users.store'), $this->validUser(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post(route('users.store'), $this->validUser(['password' => 'onlyletters', 'password_confirmation' => 'onlyletters']))->assertSessionHasErrors('password');
        $this->post(route('users.store'), $this->validUser(['password_confirmation' => 'Different1234']))->assertSessionHasErrors('password');
        $this->post(route('users.store'), $this->validUser(['departments' => ['nope']]))->assertSessionHasErrors('departments.0');
        $this->post(route('users.store'), $this->validUser(['departments' => [Role::ADMIN]]))->assertSessionHasErrors('departments.0');
        $this->post(route('users.store'), $this->validUser(['departments' => []]))->assertSessionHasErrors('departments');
        $this->post(route('users.store'), $this->validUser(['access' => '']))->assertSessionHasErrors('access');
        $this->post(route('users.store'), $this->validUser(['access' => 'nope']))->assertSessionHasErrors('access');
        $this->post(route('users.store'), $this->validUser(['name' => '  ']))->assertSessionHasErrors('name');

        $this->assertFalse(User::where('email', 'ali@example.test')->exists());
    }

    public function test_the_list_filters_work(): void
    {
        $other = User::factory()->create()->assignRole('ene');

        $this->actingAs($this->admin);

        $this->get(route('users.index', ['search' => $this->viewer->email]))->assertSee($this->viewer->email)->assertDontSee($other->email);
        $this->get(route('users.index', ['role' => 'sed']))->assertSee($this->viewer->email)->assertDontSee($other->email);
        $this->get(route('users.index', ['role' => 'does-not-exist']))->assertOk()->assertSee('No users match these filters');
        $this->get(route('users.index', ['status' => 'disabled']))->assertOk()->assertSee('No users match these filters');
        $this->get(route('users.index', ['search' => '100%_\\']))->assertOk()->assertSee('No users match these filters');
    }

    public function test_the_form_lists_the_departments_initiatives_by_short_name(): void
    {
        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('PSRP Phase 1')
            ->assertSee('Free Solar Panel')
            ->assertSee($this->phaseOne->name);
    }

    public function test_editing_can_move_a_user_to_another_department(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => '', 'password_confirmation' => '', 'departments' => ['ene'], 'permissions' => [$this->solar->permission->id]]))
            ->assertRedirect(route('users.index'));

        $user = $this->viewer->fresh();

        $this->assertSame(['ene'], $user->getRoleNames()->all());
        $this->assertSame(['ene.free_solar_panel'], $user->getPermissionNames()->all());
        $this->assertFalse($user->canOpenInitiative($this->phaseOne));
    }

    public function test_access_changes_apply_straight_away(): void
    {
        $this->actingAs($this->viewer)->get(route('initiatives.show', $this->phaseOne))->assertOk();

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => '', 'password_confirmation' => '', 'permissions' => []]));

        $this->actingAs($this->viewer->fresh())->get(route('initiatives.show', $this->phaseOne))->assertForbidden();
    }

    public function test_an_empty_password_keeps_the_current_one(): void
    {
        $before = $this->viewer->password;

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => '', 'password_confirmation' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame($before, $this->viewer->fresh()->password);
        $this->assertFalse($this->viewer->fresh()->must_change_password);
    }

    public function test_a_password_reset_by_an_admin_must_be_changed(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => 'NewPass1234', 'password_confirmation' => 'NewPass1234']))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPass1234', $this->viewer->fresh()->password));
        $this->assertTrue($this->viewer->fresh()->must_change_password);
    }

    public function test_an_admin_cannot_change_their_own_role_or_disable_themselves(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('users.update', $this->admin), $this->validUser(['email' => $this->admin->email, 'password' => '', 'password_confirmation' => '']))
            ->assertSessionHasErrors('access');
        $this->assertTrue($this->admin->fresh()->isAdmin());

        $this->patch(route('users.status', $this->admin), ['active' => 0])->assertSessionHasErrors('status');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_an_admin_can_save_their_own_details(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->admin), $this->validUser(['email' => $this->admin->email, 'name' => 'New Name', 'password' => 'NewPass1234', 'password_confirmation' => 'NewPass1234', 'access' => 'admin', 'permissions' => []]))
            ->assertRedirect(route('users.index'));

        $this->assertSame('New Name', $this->admin->fresh()->name);
        $this->assertTrue($this->admin->fresh()->isAdmin());
        $this->assertFalse($this->admin->fresh()->must_change_password);
    }

    // Disabled accounts

    public function test_disabling_a_user_ends_their_session(): void
    {
        $this->actingAs($this->admin)->patch(route('users.status', $this->viewer), ['active' => 0]);

        $this->assertFalse($this->viewer->fresh()->is_active);

        $this->actingAs($this->viewer->fresh())->get(route('departments.show', $this->school))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => User::DISABLED_MESSAGE]);

        $this->assertGuest();

        // They land on the login page with the reason, not in a loop of redirects.
        $this->get(route('login'))->assertOk()->assertSee(User::DISABLED_MESSAGE);
    }

    public function test_a_disabled_user_is_blocked_from_every_kind_of_page(): void
    {
        $this->viewer->update(['is_active' => false]);

        foreach ([route('departments.index'), route('initiatives.show', $this->phaseOne), route('settings.index')] as $url) {
            $this->actingAs($this->viewer->fresh())->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_a_disabled_user_can_be_enabled_again(): void
    {
        $this->viewer->update(['is_active' => false]);

        $this->actingAs($this->admin)->patch(route('users.status', $this->viewer), ['active' => 1]);

        $this->actingAs($this->viewer->fresh())->get(route('departments.show', $this->school))->assertOk();
    }

    // Logging in

    private function login(string $email, string $password)
    {
        return $this->withSession(['captcha_answer' => 7])
            ->post(route('login.submit'), ['email' => $email, 'password' => $password, 'captcha' => 7]);
    }

    public function test_login_works_for_an_active_user(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234'])->assignRole('sed');

        $this->login($user->email, 'Secret1234')->assertRedirect(route('departments.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_disabled_user_cannot_log_in_and_is_told_why(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234', 'is_active' => false]);

        $this->login($user->email, 'Secret1234')->assertSessionHasErrors(['email' => User::DISABLED_MESSAGE]);
        $this->assertGuest();
    }

    public function test_the_wrong_password_does_not_reveal_that_an_account_is_disabled(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234', 'is_active' => false]);

        $this->login($user->email, 'Wrong1234')->assertSessionHasErrors(['email' => 'Invalid email or password.']);
    }

    // Changing a password

    public function test_a_user_who_must_change_their_password_cannot_go_anywhere_else(): void
    {
        $this->viewer->update(['must_change_password' => true]);
        $this->actingAs($this->viewer->fresh());

        foreach ([route('departments.index'), route('departments.show', $this->school), route('initiatives.show', $this->phaseOne)] as $url) {
            $this->get($url)->assertRedirect(route('settings.index'));
        }

        $this->get(route('settings.index'))->assertOk()->assertSee('Please choose a new password');
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_changing_the_password_lets_the_user_continue(): void
    {
        $this->viewer->update(['password' => 'Temporary1234', 'must_change_password' => true]);

        $this->actingAs($this->viewer->fresh())
            ->put(route('settings.password.update'), ['current_password' => 'Temporary1234', 'password' => 'MyOwnPass1234', 'password_confirmation' => 'MyOwnPass1234'])
            ->assertRedirect(route('departments.index'));

        $this->assertTrue(Hash::check('MyOwnPass1234', $this->viewer->fresh()->password));
        $this->assertFalse($this->viewer->fresh()->must_change_password);
    }

    public function test_changing_a_password_is_validated(): void
    {
        $this->viewer->update(['password' => 'Current1234']);
        $this->actingAs($this->viewer->fresh());

        $url = route('settings.password.update');

        $this->put($url, ['current_password' => 'Wrong1234', 'password' => 'MyOwnPass1234', 'password_confirmation' => 'MyOwnPass1234'])->assertSessionHasErrors('current_password');
        $this->put($url, ['current_password' => 'Current1234', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->put($url, ['current_password' => 'Current1234', 'password' => 'MyOwnPass1234', 'password_confirmation' => 'Other1234'])->assertSessionHasErrors('password');
        $this->put($url, ['current_password' => 'Current1234', 'password' => 'Current1234', 'password_confirmation' => 'Current1234'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Current1234', $this->viewer->fresh()->password));
    }

    // Users in several departments

    public function test_an_admin_can_give_a_user_several_departments_and_their_initiatives(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser([
                'departments' => ['sed', 'ene'],
                'permissions' => [$this->phaseOne->permission->id, $this->solar->permission->id],
            ]))
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();

        $this->assertSame(['ene', 'sed'], $user->getRoleNames()->sort()->values()->all());
        $this->assertSame(['ene.free_solar_panel', 'sed.psrp_phase_1'], $user->getPermissionNames()->sort()->values()->all());
        $this->assertSame(['Energy', 'School Education'], $user->departments()->pluck('name')->all());

        $this->actingAs($this->admin)->get(route('users.index'))->assertSee('Energy')->assertSee('School Education');
    }

    public function test_an_initiative_of_a_department_not_ticked_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['departments' => ['sed'], 'permissions' => [$this->solar->permission->id]]))
            ->assertSessionHasErrors('permissions');

        $this->assertFalse(User::where('email', 'ali@example.test')->exists());
    }

    public function test_an_administrator_gets_no_departments(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['access' => 'admin', 'departments' => ['sed', 'ene']]))
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'ali@example.test')->firstOrFail();

        $this->assertSame([Role::ADMIN], $user->getRoleNames()->all());
        $this->assertTrue($user->departments()->isEmpty());
    }

    public function test_two_users_can_share_an_initiative(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validUser(['email' => 'second@example.test']))
            ->assertRedirect(route('users.index'));

        $second = User::where('email', 'second@example.test')->firstOrFail();

        $this->assertTrue($this->viewer->canOpenInitiative($this->phaseOne));
        $this->assertTrue($second->canOpenInitiative($this->phaseOne));
    }

    public function test_a_user_with_several_departments_sees_only_theirs(): void
    {
        $health = Department::create(['code' => 'HPD', 'name' => 'Health']);
        $this->seed(AccessControlSeeder::class);

        $user = User::factory()->create()->assignRole(['sed', 'ene']);
        $user->givePermissionTo(['sed.psrp_phase_1', 'ene.free_solar_panel']);
        $this->storedFile($this->phaseOne);
        $this->storedFile($this->phaseTwo);

        // The Departments page lists their two departments, and its cards count only what they can open.
        $this->actingAs($user)->get(route('departments.index'))
            ->assertOk()
            ->assertSee('School Education')
            ->assertSee('Energy')
            ->assertDontSee($health->name)
            ->assertViewHas('topbar', fn ($topbar) => $topbar['stats'][0]['value'] === 2
                && $topbar['stats'][1]['value'] === 2
                && $topbar['stats'][3]['value'] === 1);

        $this->actingAs($user)->get(route('departments.show', $this->school))
            ->assertOk()
            ->assertSee($this->phaseOne->name)
            ->assertDontSee($this->phaseTwo->name)
            ->assertSee('Back to Departments');
        $this->actingAs($user)->get(route('departments.show', $this->energy))->assertOk()->assertSee($this->solar->name);
        $this->actingAs($user)->get(route('departments.show', $health))->assertForbidden();

        // Solar has no page built in these tests, so only the access check is looked at there.
        $this->actingAs($user)->get(route('initiatives.show', $this->phaseOne))->assertOk();
        $this->assertAllowed($this->actingAs($user)->get(route('initiatives.show', $this->solar)));
        $this->actingAs($user)->get(route('initiatives.show', $this->phaseTwo))->assertForbidden();
    }

    public function test_a_user_with_several_departments_is_told_when_one_has_nothing_for_them(): void
    {
        $user = User::factory()->create()->assignRole(['sed', 'ene']);
        $user->givePermissionTo('sed.psrp_phase_1');

        $this->actingAs($user)->get(route('departments.show', $this->energy))
            ->assertOk()
            ->assertSee('No initiatives have been given to your account yet')
            ->assertDontSee('No active initiatives have been registered')
            ->assertSee('Back to Departments');
    }

    public function test_editing_can_add_and_remove_departments(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => '', 'password_confirmation' => '', 'departments' => ['sed', 'ene'], 'permissions' => [$this->phaseOne->permission->id, $this->solar->permission->id]]))
            ->assertRedirect(route('users.index'));

        $this->assertSame(['ene', 'sed'], $this->viewer->fresh()->getRoleNames()->sort()->values()->all());

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->viewer), $this->validUser(['email' => $this->viewer->email, 'password' => '', 'password_confirmation' => '', 'departments' => ['ene'], 'permissions' => [$this->solar->permission->id]]))
            ->assertRedirect(route('users.index'));

        $user = $this->viewer->fresh();

        $this->assertSame(['ene'], $user->getRoleNames()->all());
        $this->assertSame(['ene.free_solar_panel'], $user->getPermissionNames()->all());
        $this->actingAs($user)->get(route('departments.index'))->assertRedirect(route('departments.show', $this->energy));
    }

    public function test_the_form_offers_every_department_and_marks_the_users_own(): void
    {
        $this->viewer->assignRole('ene');

        $page = $this->actingAs($this->admin)->get(route('users.edit', $this->viewer))->assertOk()->getContent();

        $this->assertStringContainsString('name="departments[]" value="sed" data-label="School Education" checked', $page);
        $this->assertStringContainsString('name="departments[]" value="ene" data-label="Energy" checked', $page);
        $this->assertStringContainsString('name="access" value="departments" checked', $page);
    }

    public function test_each_password_box_in_settings_has_a_show_button(): void
    {
        $page = $this->actingAs($this->viewer)->get(route('settings.index'))->assertOk()->getContent();

        foreach (['umCurrentPassword', 'umNewPassword', 'umConfirmPassword'] as $box) {
            $this->assertStringContainsString('data-show-password="'.$box.'"', $page);
        }
    }
}
