<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// A user is either an administrator, or a department user who belongs to one or more departments and
// can open the initiatives chosen for them from those departments.
class UserRequest extends FormRequest
{
    public const ADMIN = 'admin';

    public const DEPARTMENTS = 'departments';

    // The route already allows admins only.
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            // Editing a user keeps their password unless a new one is typed.
            'password' => [$user ? 'nullable' : 'required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'access' => ['required', Rule::in([self::ADMIN, self::DEPARTMENTS])],
            'departments' => ['exclude_unless:access,'.self::DEPARTMENTS, 'required', 'array'],
            'departments.*' => ['string', Rule::exists('roles', 'name')->whereNotNull('department_id')],
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Another user already has this email address.',
            'password.confirmed' => 'The two passwords do not match.',
            'access.required' => 'Choose Administrator or Department user.',
            'access.in' => 'Choose Administrator or Department user.',
            'departments.required' => 'Choose at least one department.',
            'departments.array' => 'Choose at least one department.',
            'departments.*.exists' => 'Choose departments from the list.',
            'departments.*.string' => 'Choose departments from the list.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['access', 'departments', 'departments.*'])) {
                return;
            }

            $user = $this->route('user');

            // An admin always keeps their own access, so there is always at least one admin.
            if ($user?->is($this->user()) && $user->getRoleNames()->sort()->values()->all() !== $this->selectedRoles()->pluck('name')->sort()->values()->all()) {
                $validator->errors()->add('access', 'You cannot change your own role.');
            }

            if ($this->selectedPermissions()->count() !== count($this->requestedPermissionIds())) {
                $validator->errors()->add('permissions', 'Choose only initiatives from the selected departments.');
            }
        }];
    }

    public function isAdmin(): bool
    {
        return $this->input('access') === self::ADMIN;
    }

    // The admin role, or the role of each department chosen.
    public function selectedRoles(): Collection
    {
        if ($this->isAdmin()) {
            return Role::where('name', Role::ADMIN)->get();
        }

        return Role::query()
            ->whereNotNull('department_id')
            ->whereIn('name', array_values(array_unique(array_map('strval', (array) $this->input('departments', [])))))
            ->orderBy('name')
            ->get();
    }

    // Only initiatives of the chosen departments count. An admin needs none, because they can open everything.
    public function selectedPermissions(): Collection
    {
        if ($this->isAdmin()) {
            return collect();
        }

        $departments = $this->selectedRoles()->pluck('department_id');

        return Permission::query()
            ->whereIn('id', $this->requestedPermissionIds())
            ->whereHas('initiative', fn ($query) => $query->whereIn('department_id', $departments))
            ->get();
    }

    private function requestedPermissionIds(): array
    {
        return $this->isAdmin()
            ? []
            : array_values(array_unique(array_map('intval', (array) $this->input('permissions', []))));
    }
}
