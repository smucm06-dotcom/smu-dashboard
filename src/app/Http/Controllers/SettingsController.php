<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('settings.index', [
            'mustChange' => $request->user()->must_change_password,
            'topbar' => [
                'title' => 'Settings',
                'description' => 'Manage your account settings.',
            ],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is not correct.',
            'password.confirmed' => 'The two new passwords do not match.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $user = $request->user();
        $wasRequired = $user->must_change_password;

        $user->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ]);

        $request->session()->regenerate();

        return $wasRequired
            ? redirect()->route('departments.index')
            : redirect()->route('settings.index')->with('passwordChanged', 'Your password was changed.');
    }
}
