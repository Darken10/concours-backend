<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('POST /api/organizations', function () {
    test('authenticated user can create an organization', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/organizations', [
                'name' => 'Test Organization',
                'description' => 'A test organization',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'description',
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
            'description' => 'A test organization',
        ]);
    });

    test('unauthenticated user cannot create organization', function () {
        $response = $this->postJson('/api/organizations', [
            'name' => 'Test Organization',
            'description' => 'A test organization',
        ]);

        $response->assertUnauthorized();
    });

    test('organization requires name', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/organizations', [
                'description' => 'A test organization',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('organization requires description', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/organizations', [
                'name' => 'Test Organization',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    });

    test('organization name must be unique', function () {
        Organization::factory()->create(['name' => 'Existing Org']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/organizations', [
                'name' => 'Existing Org',
                'description' => 'Another organization',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

describe('GET /api/organizations/{organization}', function () {
    test('authenticated user can view organization', function () {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Test Org',
            'description' => 'Test Description',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}");

        $response->assertSuccessful()
            ->assertJson([
                'id' => $organization->id,
                'name' => 'Test Org',
                'description' => 'Test Description',
            ]);
    });

    test('organization details include users', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'id',
                'name',
                'users',
            ]);
    });

    test('unauthenticated user cannot view organization', function () {
        $organization = Organization::factory()->create();

        $response = $this->getJson("/api/organizations/{$organization->id}");

        $response->assertUnauthorized();
    });

    test('returns 404 for nonexistent organization', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/organizations/99999');

        $response->assertNotFound();
    });
});

describe('POST /api/organizations/{organization}/agents', function () {
test('authenticated user can create an agent', function () {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('admin');

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'firstname' => 'Agent',
                'lastname' => 'User',
                'email' => 'agent@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'male',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'agent' => ['id', 'email', 'firstname', 'lastname'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'agent@example.com',
            'firstname' => 'Agent',
        ]);
    });

    test('unauthenticated user cannot create agent', function () {
        $organization = Organization::factory()->create();

        $response = $this->postJson("/api/organizations/{$organization->id}/agents", [
            'firstname' => 'Agent',
            'lastname' => 'User',
            'email' => 'agent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
        ]);

        $response->assertUnauthorized();
    });

    test('agent creation requires firstname', function () {
        $owner = User::factory()->create();
        $owner->assignRole('admin');
        $organization = Organization::factory()->create();

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'lastname' => 'User',
                'email' => 'agent@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'male',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['firstname']);
    });

    test('agent creation requires unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('admin');

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'firstname' => 'Agent',
                'lastname' => 'User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'male',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/organizations/{organization}/admins', function () {
test('admin can assign another admin', function () {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('admin');

        $userToPromote = User::factory()->create();

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/admins", [
                'user_id' => $userToPromote->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'admin' => ['id', 'email'],
            ]);
    });

    test('unauthenticated user cannot assign admin', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $response = $this->postJson("/api/organizations/{$organization->id}/admins", [
            'user_id' => $user->id,
        ]);

        $response->assertUnauthorized();
    });

    test('admin assignment requires user_id or user data', function () {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('admin');

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/admins", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    });

    test('admin assignment requires valid user_id', function () {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('admin');

        $response = $this->actingAs($owner)
            ->postJson("/api/organizations/{$organization->id}/admins", [
                'user_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    });
});
