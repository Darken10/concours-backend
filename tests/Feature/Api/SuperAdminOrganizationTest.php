<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('Super Admin Organization Management', function () {
    test('super-admin can create an organization', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/organizations', [
                'name' => 'Super Admin Org',
                'description' => 'Created by super admin',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'description',
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Super Admin Org',
            'description' => 'Created by super admin',
        ]);
    });

    test('super-admin can create agent in any organization', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $organization = Organization::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'firstname' => 'New',
                'lastname' => 'Agent',
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
            'organization_id' => $organization->id,
        ]);

        $agent = User::where('email', 'agent@example.com')->first();
        expect($agent->hasRole('agent'))->toBeTrue();
    });

    test('super-admin can assign admin to any organization', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $organization = Organization::factory()->create();
        $userToPromote = User::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->postJson("/api/organizations/{$organization->id}/admins", [
                'user_id' => $userToPromote->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'admin' => ['id', 'email'],
            ]);

        $userToPromote->refresh();
        expect($userToPromote->hasRole('admin'))->toBeTrue();
        expect($userToPromote->organization_id)->toBe($organization->id);
    });

    test('organization admin can create agent in their organization', function () {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'firstname' => 'Org',
                'lastname' => 'Agent',
                'email' => 'orgagent@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'female',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'orgagent@example.com',
            'organization_id' => $organization->id,
        ]);

        $agent = User::where('email', 'orgagent@example.com')->first();
        expect($agent->hasRole('agent'))->toBeTrue();
    });

    test('organization admin cannot create agent in different organization', function () {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        
        $admin = User::factory()->create(['organization_id' => $organization1->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson("/api/organizations/{$organization2->id}/agents", [
                'firstname' => 'Unauthorized',
                'lastname' => 'Agent',
                'email' => 'unauthorized@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'male',
            ]);

        $response->assertStatus(403);
    });

    test('regular user cannot create agents in any organization', function () {
        $user = User::factory()->create();
        $user->assignRole('user');

        $organization = Organization::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/organizations/{$organization->id}/agents", [
                'firstname' => 'Unauthorized',
                'lastname' => 'Agent',
                'email' => 'unauthorized@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'gender' => 'male',
            ]);

        $response->assertStatus(403);
    });
});
