<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Mock socialite user
    $this->socialUser = \Mockery::mock(SocialiteUser::class);
    $this->socialUser->shouldReceive('getId')->andReturn('123456');
    $this->socialUser->shouldReceive('getEmail')->andReturn('social@example.com');
    $this->socialUser->shouldReceive('getName')->andReturn('John Doe');
    $this->socialUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    $this->socialUser->shouldReceive('getNickname')->andReturn(null);
});

describe('POST /api/auth/social/{provider}', function () {
    test('user can login with google provider', function () {
        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->postJson('/api/auth/social/google', [
            'access_token' => 'valid-token',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'social@example.com',
        ]);
    });

    test('user can login with facebook provider', function () {
        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->postJson('/api/auth/social/facebook', [
            'access_token' => 'valid-token',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    });

    test('user can login with github provider', function () {
        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->postJson('/api/auth/social/github', [
            'access_token' => 'valid-token',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    });

    test('user can login with twitter provider', function () {
        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->postJson('/api/auth/social/twitter', [
            'access_token' => 'valid-token',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    });

    test('returns existing user if already registered', function () {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'social@example.com',
            'provider' => 'google',
            'provider_id' => '123456',
        ]);

        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->postJson('/api/auth/social/google', [
            'access_token' => 'valid-token',
        ]);

        $response->assertSuccessful();

        // Verify only one user exists with this email
        expect(User::where('email', 'social@example.com')->count())->toBe(1);
    });

    test('requires access token', function () {
        $response = $this->postJson('/api/auth/social/google', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['access_token']);
    });

    test('returns error with invalid token', function () {
        Socialite::shouldReceive('driver->userFromToken')
            ->once()
            ->andThrow(new \Exception('Invalid token'));

        $response = $this->postJson('/api/auth/social/google', [
            'access_token' => 'invalid-token',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid token or provider']);
    });
});

describe('GET /api/auth/{provider}/redirect', function () {
    test('returns redirect url for google provider', function () {
        Socialite::shouldReceive('driver->stateless->redirect->getTargetUrl')
            ->once()
            ->andReturn('https://accounts.google.com/oauth');

        $response = $this->getJson('/api/auth/google/redirect');

        $response->assertSuccessful()
            ->assertJson(['url' => 'https://accounts.google.com/oauth']);
    });

    test('returns redirect url for facebook provider', function () {
        Socialite::shouldReceive('driver->stateless->redirect->getTargetUrl')
            ->once()
            ->andReturn('https://www.facebook.com/oauth');

        $response = $this->getJson('/api/auth/facebook/redirect');

        $response->assertSuccessful()
            ->assertJsonStructure(['url']);
    });

    test('returns redirect url for github provider', function () {
        Socialite::shouldReceive('driver->stateless->redirect->getTargetUrl')
            ->once()
            ->andReturn('https://github.com/oauth');

        $response = $this->getJson('/api/auth/github/redirect');

        $response->assertSuccessful()
            ->assertJsonStructure(['url']);
    });

    test('returns redirect url for twitter provider', function () {
        Socialite::shouldReceive('driver->stateless->redirect->getTargetUrl')
            ->once()
            ->andReturn('https://twitter.com/oauth');

        $response = $this->getJson('/api/auth/twitter/redirect');

        $response->assertSuccessful()
            ->assertJsonStructure(['url']);
    });

    test('returns 404 for invalid provider', function () {
        $response = $this->getJson('/api/auth/invalid-provider/redirect');

        $response->assertNotFound();
    });
});

describe('GET /api/auth/{provider}/callback', function () {
    test('handles google callback successfully', function () {
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'social@example.com',
            'provider' => 'google',
            'provider_id' => '123456',
        ]);
    });

    test('handles facebook callback successfully', function () {
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->getJson('/api/auth/facebook/callback');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'user',
                'token',
            ]);
    });

    test('returns existing user on callback if already registered', function () {
        $existingUser = User::factory()->create([
            'email' => 'social@example.com',
            'provider' => 'google',
            'provider_id' => '123456',
        ]);

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertSuccessful();

        // Verify only one user exists
        expect(User::where('provider_id', '123456')->count())->toBe(1);
    });

    test('creates new user with provider details', function () {
        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($this->socialUser);

        $response = $this->getJson('/api/auth/github/callback');

        $response->assertSuccessful();

        $user = User::where('email', 'social@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->provider)->toBe('github')
            ->and($user->provider_id)->toBe('123456')
            ->and($user->avatar)->toBe('https://example.com/avatar.jpg');
    });

    test('returns 404 for invalid provider in callback', function () {
        $response = $this->getJson('/api/auth/invalid-provider/callback');

        $response->assertNotFound();
    });
});
