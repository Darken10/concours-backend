<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected AuthService $authService;

    protected array $providers = ['google', 'facebook', 'github', 'twitter'];

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

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

    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, $this->providers), 404);

        $response = Socialite::driver($provider)->stateless()->redirect();

        if ($request->expectsJson()) {
            return response()->json(['url' => $response->getTargetUrl()]);
        }

        return $response;
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, $this->providers), 404);

        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::create([
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(32)),

                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),

                'firstname' => $socialUser->getName()
                    ? explode(' ', $socialUser->getName())[0]
                    : null,

                'lastname' => $socialUser->getName()
                    ? explode(' ', $socialUser->getName(), 2)[1] ?? null
                    : null,
            ]);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('auth-token')->plainTextToken,
        ]);
    }
}
