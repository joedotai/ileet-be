<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Immutable data transfer object for API-based user registration.
 *
 * Expected request payload:
 *
 * {
 *     "email": "john@example.com",
 *     "password": "MySecurePassword123!"
 * }
 *
 * Example API controller usage:
 *
 * $dto = UserDTO::fromRequest($request->all());
 *
 * $user = User::create([
 *     'email' => $dto->email,
 *     'password' => Hash::make($dto->password),
 * ]);
 *
 * Example validation error response JSON:
 *
 * {
 *     "message": "The given data was invalid.",
 *     "errors": {
 *         "email": [
 *             "The email field is required."
 *         ],
 *         "password": [
 *             "The password field must be at least 12 characters."
 *         ]
 *     }
 * }
 */
final readonly class UserDTO
{
    /**
     * Create a user registration DTO.
     *
     * The email address is also the authentication username.
     */
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }

    /**
     * Validate an API registration payload and return a normalized DTO.
     *
     * @param array<string, mixed> $input
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(array $input): self
    {
        $payload = [
            'email' => self::normalizeEmail($input['email'] ?? null),
            'password' => $input['password'] ?? null,
        ];

        $validated = Validator::make($payload, [
            'email' => ['required', 'string', 'email'],
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ])->validate();

        return new self(
            email: $validated['email'],
            password: $validated['password'],
        );
    }

    /**
     * Normalize email addresses while preserving invalid types for validation.
     */
    private static function normalizeEmail(mixed $email): mixed
    {
        if (! is_string($email)) {
            return $email;
        }

        return Str::lower(trim($email));
    }
}
