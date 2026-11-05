<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'role' => (string) $request->query('role', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $users = User::query()
            ->with(['roles.department', 'permissions.initiative'])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['search'], '\\%_').'%';

                $query->where(fn ($match) => $match->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters['role'] !== '', fn ($query) => $query->whereHas('roles', fn ($role) => $role->where('name', $filters['role'])))
            ->when(in_array($filters['status'], ['active', 'disabled'], true), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::forSelection(),
            'topbar' => [
                'title' => 'Users',
                'description' => 'Add users, choose their department, and decide which initiatives each of them can open.',
                'stats' => [
                    ['label' => 'Total Users', 'value' => User::count()],
                    ['label' => 'Active Users', 'value' => User::where('is_active', true)->count()],
                    ['label' => 'Disabled Users', 'value' => User::where('is_active', false)->count()],
                    ['label' => 'Admins', 'value' => User::whereHas('roles', fn ($role) => $role->where('name', Role::ADMIN))->count()],
                ],
            ],
        ]);
    }

    public function create()
    {
        return view('users.create', $this->formData(new User));
    }

    public function store(UserRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'must_change_password' => true,
            ]);

            $this->saveAccess($user, $request);

            return $user;
        });

        $this->logChange('User created.', $user, $request);

        return redirect()
            ->route('users.index')
            ->with('userSaved', $user->name.' was added. They must choose a new password when they first log in.');
    }

    public function edit(User $user)
    {
        return view('users.edit', $this->formData($user));
    }

    public function update(UserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->only('name', 'email'));

            if ($request->filled('password')) {
                $user->password = $request->input('password');

                // An admin knows a password they set for someone else, but not one they set for themselves.
                $user->must_change_password = ! $user->is($request->user());
            }

            $user->save();

            $this->saveAccess($user, $request);
        });

        $this->logChange('User updated.', $user, $request);

        return redirect()
            ->route('users.index')
            ->with('userSaved', $user->name.' was updated.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $active = $request->boolean('active');

        if ($user->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot disable your own account.']);
        }

        $user->update(['is_active' => $active]);

        Log::info($active ? 'User enabled.' : 'User disabled.', [
            'user_id' => $user->id,
            'changed_by' => $request->user()->id,
        ]);

        return back()->with('userSaved', $user->name.' was '.($active ? 'enabled.' : 'disabled.'));
    }

    private function saveAccess(User $user, UserRequest $request): void
    {
        $user->syncRoles($request->selectedRole());
        $user->syncPermissions($request->selectedPermissions());
    }

    // Who gave which access is worth keeping. Passwords are never written here.
    private function logChange(string $message, User $user, UserRequest $request): void
    {
        Log::info($message, [
            'user_id' => $user->id,
            'role' => $request->selectedRole()->name,
            'permissions' => $request->selectedPermissions()->pluck('name')->all(),
            'password_changed' => $request->filled('password'),
            'changed_by' => $request->user()->id,
        ]);
    }

    private function formData(User $user): array
    {
        $isNew = ! $user->exists;

        return [
            'user' => $user,
            'isSelf' => $user->is(auth()->user()),
            'roles' => Role::forSelection()->load([
                'department.initiatives' => fn ($query) => $query->has('permission')->orderBy('short_name'),
                'department.initiatives.permission',
            ]),
            'selectedRole' => old('role', $user->roles->first()?->name),
            'savedRole' => $user->roles->first()?->name,
            'selectedPermissions' => array_map('intval', (array) old('permissions', $user->permissions->pluck('id')->all())),
            'topbar' => [
                'title' => $isNew ? 'Add User' : 'Edit User',
                'description' => 'Choose a department as the role, then the initiatives this user can open.',
            ],
        ];
    }
}
