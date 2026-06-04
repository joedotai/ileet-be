<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\DTO\LoginDTO;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class UserLoginController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Handle the incoming login request.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Validate and encapsulate incoming credentials
        $loginDTO = LoginDTO::fromRequest($request->all());

        // 2. Delegate authentication to the service layer
        // (Assuming your UserService throws a ValidationException or returns false on failure)
        $authData = $this->userService->authenticate($loginDTO);

        if (! $authData) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // 3. Return the token/user payload response
        return response()->json($authData);
    }
}