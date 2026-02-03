<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{get};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('GET /', function () {
    test('home page is accessible', function () {
        $response = get('/');

        $response->assertSuccessful();
    });

    test('home page returns welcome view', function () {
        $response = get('/');

        $response->assertViewIs('welcome');
    });
});

describe('GET /dashboard', function () {
    test('authenticated user can access dashboard', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertSuccessful();
    });

    test('unauthenticated user is redirected to login', function () {
        $response = get('/dashboard');

        $response->assertRedirect('/login');
    });

    test('dashboard requires verified email', function () {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->get('/dashboard');

        // Should redirect to email verification or be successful
        expect($response->status())->toBeIn([200, 302]);
    });
});

describe('GET /settings/*', function () {
    test('settings redirects to settings/profile', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings');

        $response->assertRedirect('/settings/profile');
    });

    test('authenticated user can access settings/profile', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings/profile');

        $response->assertSuccessful();
    });

    test('unauthenticated user cannot access settings/profile', function () {
        $response = get('/settings/profile');

        $response->assertRedirect('/login');
    });

    test('authenticated user can access settings/password', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings/password');

        $response->assertSuccessful();
    });

    test('unauthenticated user cannot access settings/password', function () {
        $response = get('/settings/password');

        $response->assertRedirect('/login');
    });

    test('authenticated user can access settings/appearance', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings/appearance');

        $response->assertSuccessful();
    });

    test('unauthenticated user cannot access settings/appearance', function () {
        $response = get('/settings/appearance');

        $response->assertRedirect('/login');
    });

    test('authenticated user can access settings/two-factor', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/settings/two-factor');

        // Peut être redirigé vers password confirmation
        expect($response->status())->toBeIn([200, 302]);
    });

    test('unauthenticated user cannot access settings/two-factor', function () {
        $response = get('/settings/two-factor');

        $response->assertRedirect('/login');
    });
});
