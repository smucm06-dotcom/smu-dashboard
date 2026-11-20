<?php

namespace App\Policies;

use App\Models\Initiative;
use App\Models\User;

// Admins pass every check before reaching these methods (see AppServiceProvider).
class InitiativePolicy
{
    // Opening the page, its data, and downloading its files.
    public function view(User $user, Initiative $initiative): bool
    {
        return $user->canOpenInitiative($initiative);
    }

    // Uploading and deleting files, which only admins can do.
    public function manage(User $user, Initiative $initiative): bool
    {
        return false;
    }

    // Uploading our report PDFs: anyone who can open the initiative. Deleting them stays with admins.
    public function uploadReport(User $user, Initiative $initiative): bool
    {
        return $user->canOpenInitiative($initiative);
    }
}
