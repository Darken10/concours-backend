<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('POST /api/auth/register-with-organization', function () {
    test('user can register without organization', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'firstname', 'lastname', 'email'],
                'organization',
                'token',
                'message',
            ])
            ->assertJson([
                'organization' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        expect($user->hasRole('user'))->toBeTrue();
        expect($user->organization_id)->toBeNull();
    });

    test('user can register with organization', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'female',
            'is_organization' => true,
            'organization_name' => 'My Organization',
            'organization_description' => 'A great organization',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => ['id', 'firstname', 'lastname', 'email'],
                'organization' => ['id', 'name', 'description'],
                'token',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'firstname' => 'Jane',
            'lastname' => 'Smith',
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'My Organization',
            'description' => 'A great organization',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        expect($user->hasRole('admin'))->toBeTrue();
        expect($user->organization_id)->not()->toBeNull();

        $organization = Organization::where('name', 'My Organization')->first();
        expect($user->organization_id)->toBe($organization->id);
    });

    test('registration requires organization name when is_organization is true', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'female',
            'is_organization' => true,
            'organization_description' => 'A great organization',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_name']);
    });

    test('registration requires organization description when is_organization is true', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'female',
            'is_organization' => true,
            'organization_name' => 'My Organization',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_description']);
    });

    test('organization name must be unique', function () {
        Organization::factory()->create(['name' => 'Existing Org']);

        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'female',
            'is_organization' => true,
            'organization_name' => 'Existing Org',
            'organization_description' => 'Another organization',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_name']);
    });

    test('registration requires is_organization field', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['is_organization']);
    });

    test('registration requires valid email', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration requires firstname', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname']);
    });

    test('registration requires lastname', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lastname']);
    });

    test('registration requires password confirmation', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'gender' => 'male',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable();
    });

    test('registration requires gender', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_organization' => false,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    });

    test('user data is included in response with organization relationship', function () {
        $response = $this->postJson('/api/auth/register-with-organization', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'female',
            'is_organization' => true,
            'organization_name' => 'Tech Corp',
            'organization_description' => 'A tech company',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                ],
                'organization' => [
                    'id',
                    'name',
                    'description',
                ],
                'token',
                'message',
            ]);
    });
});
