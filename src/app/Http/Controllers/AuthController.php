<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Display login page with a new math CAPTCHA.
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('departments.index');
        }

        $first = random_int(1, 9);
        $second = random_int(1, 9);

        $request->session()->put(
            'captcha_answer',
            $first + $second
        );

        return view('auth.login', [
            'first' => $first,
            'second' => $second,
        ]);
    }

    // Validate CAPTCHA and login credentials.
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'integer'],
        ]);

        $expectedAnswer = $request->session()
            ->pull('captcha_answer');

        if (
            $expectedAnswer === null ||
            (int) $request->input('captcha') !==
                (int) $expectedAnswer
        ) {
            throw ValidationException::withMessages([
                'captcha' => 'Incorrect CAPTCHA answer. Please try again.',
            ]);
        }

        $credentials = $request->only('email', 'password');

        // Only someone with the right password is told that the account is disabled.
        $isDisabled = false;

        if (Auth::attemptWhen(
            $credentials,
            function (User $user) use (&$isDisabled) {
                $isDisabled = ! $user->is_active;

                return $user->is_active;
            },
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();

            return redirect()->intended(
                route('departments.index')
            );
        }

        throw ValidationException::withMessages([
            'email' => $isDisabled ? User::DISABLED_MESSAGE : 'Invalid email or password.',
        ]);
    }

    // Logout securely.
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
