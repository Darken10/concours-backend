<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Data\Auth\LoginUserData;
use App\Data\Auth\CreateUserData;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\AutUserRessource;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;

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
        ], 201);
    }


    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->authService->loginUser(LoginUserData::fromArray($data));

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }


    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }


    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
