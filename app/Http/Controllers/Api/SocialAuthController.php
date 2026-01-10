<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\UserResource;

class SocialAuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle social login via access token from mobile app
     */
    public function handleProvider(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Get user from provider using the token
            $socialUser = Socialite::driver($provider)->userFromToken($request->access_token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token or provider'], 401);
        }

        $result = $this->authService->findOrCreateSocialUser($provider, $socialUser);

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }
}
