<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('POST /api/auth/register', function () {
    test('user can register with valid data', function () {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'firstname', 'lastname', 'email'],
                'token',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);
    });

    test('registration requires firstname', function () {
        $response = $this->postJson('/api/auth/register', [
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname']);
    });

    test('registration requires lastname', function () {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lastname']);
    });

    test('registration requires valid email', function () {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires matching password confirmation', function () {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable();
    });

    test('registration requires gender', function () {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    });

    test('registration accepts valid gender values', function (string $gender) {
        $response = $this->postJson('/api/auth/register', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => $gender,
        ]);

        $response->assertCreated();
    })->with(['male', 'female']);
});

describe('POST /api/auth/login', function () {
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'user' => ['id', 'email'],
                'token',
                'message',
            ]);
    });

    test('user cannot login with invalid email', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    });

    test('user cannot login with invalid password', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable();
    });

    test('login requires email', function () {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login requires password', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('GET /api/auth/me', function () {
    test('authenticated user can get their profile', function () {
        $user = User::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/auth/me');

        $response->assertSuccessful()
            ->assertJson([
                'id' => $user->id,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ]);
    });

    test('unauthenticated user cannot access profile', function () {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    });
});

describe('POST /api/auth/logout', function () {
    test('authenticated user can logout', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertSuccessful()
            ->assertJson(['message' => 'Logged out successfully']);
    });

    test('unauthenticated user cannot logout', function () {
        $response = $this->postJson('/api/auth/logout');

        $response->assertUnauthorized();
    });
});
