<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\DTO\UserDTO;
use App\Http\Requests\UserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegisterUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Handle the incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Pass the raw payload to the DTO. It handles normalization and validation internally.
        $userDTO = UserDTO::fromRequest($request->all());

        // 2. Delegate the validated DTO to your business logic layer.
        $user = $this->userService->createUser($userDTO);

        // 3. Return the response.
        return response()->json($user);