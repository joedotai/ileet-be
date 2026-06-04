<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Attempt to authenticate a user using password.
     *
     * @param array $credentials
     * @return bool
     * @throws ValidationException
     */
    public function login(array $credentials): bool
    {
        // 1. Find the user by email
        $user = User::where('email', $credentials['email'])->first();

        // 2. Verify user exists and password matches
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // 3. Log the user in (Session-based auth)
        // If you are using API tokens (Sanctum), you would use: $user->createToken('token-name')->plainTextToken;
        Auth::login($user, $credentials['remember'] ?? false);

        // 4. Regenerate session to prevent session fixation attacks
        request()->session()->regenerate();

        return true;
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void
    {
        Auth::logout();

        // Invalidate the user's session
        request()->session()->invalidate();

        // Regenerate the CSRF token
        request()->session()->regenerateToken();
    }
}