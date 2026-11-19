<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

// Admins pass every check before reaching these methods (see AppServiceProvider).
class DepartmentPolicy
{
    public function view(User $user, Department $department): bool
    {
        return $user->belongsToDepartment($department);
    }
}
