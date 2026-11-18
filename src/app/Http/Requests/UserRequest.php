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

class UserRequest extends FormRequest
{
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
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Another user already has this email address.',
            'password.confirmed' => 'The two passwords do not match.',
            'role.required' => 'Choose a role.',
            'role.exists' => 'Choose a role from the list.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('role')) {
                return;
            }

            $user = $this->route('user');

            // An admin always keeps their own access, so there is always at least one admin.
            if ($user?->is($this->user()) && ! $user->hasRole($this->selectedRole())) {
                $validator->errors()->add('role', 'You cannot change your own role.');
            }

            if ($this->selectedPermissions()->count() !== count($this->requestedPermissionIds())) {
                $validator->errors()->add('permissions', 'Choose only initiatives from the selected department.');
            }
        }];
    }

    public function selectedRole(): Role
    {
        return Role::where('name', $this->input('role'))->firstOrFail();
    }

    // Only initiatives of the chosen department count. An admin needs none, because they can open everything.
    public function selectedPermissions(): Collection
    {
        $role = $this->selectedRole();

        if ($role->isAdmin()) {
            return collect();
        }

        return Permission::query()
            ->whereIn('id', $this->requestedPermissionIds())
            ->whereHas('initiative', fn ($query) => $query->where('department_id', $role->department_id))
            ->get();
    }

    private function requestedPermissionIds(): array
    {
        return $this->selectedRole()->isAdmin()
            ? []
            : array_values(array_unique(array_map('intval', (array) $this->input('permissions', []))));
    }
}
