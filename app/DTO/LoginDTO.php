<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Immutable data transfer object for API-based user login.
 */
final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * Validate an API login payload and return a normalized DTO.
     *
     * @param array<string, mixed> $input
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
            'password' => ['required', 'string'],
        ])->validate();

        return new self(
            email: $validated['email'],
            password: $validated['password'],
        );
    }

    private static function normalizeEmail(mixed $email): mixed
    {
        return is_string($email) ? Str::lower(trim($email)) : $email;
    }
}