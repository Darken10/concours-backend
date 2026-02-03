<?php

namespace App\Http\Controllers\Api;

use App\Data\Auth\CreateUserData;
use App\Data\Auth\LoginUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterUserRequest $request): JsonResponse
    {

        $data = $request->validated();

        $result = $this->authService->registerUser(CreateUserData::fromArray($data));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'message' => 'User registered successfully',
        ], 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->authService->loginUser(LoginUserData::fromArray($data));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'message' => 'Logged in successfully',
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // Check if it's not a transient token (used in testing)
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
