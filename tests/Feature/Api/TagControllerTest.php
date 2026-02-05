<?php

declare(strict_types=1);

use App\Models\Post\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('GET /api/tags', function () {
    test('returns paginated list of tags', function () {
        Tag::factory(10)->create();

        $response = $this->getJson('/api/tags');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    });

    test('returns single tag details', function () {
        $tag = Tag::factory()->create();

        $response = $this->getJson("/api/tags/{$tag->id}");

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $tag->id, 'name' => $tag->name]);
    });
});

describe('POST /api/tags', function () {
    test('authenticated user can create a tag', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/api/tags', [
                'name' => 'Test Tag',
                'slug' => 'test-tag',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'name', 'slug']);

        $this->assertDatabaseHas('tags', ['slug' => 'test-tag']);
    });

    test('unauthenticated user cannot create tag', function () {
        $response = $this->postJson('/api/tags', [
            'name' => 'No Auth',
            'slug' => 'no-auth',
        ]);

        $response->assertUnauthorized();
    });

    test('authenticated non-privileged user cannot create a tag', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tags', [
                'name' => 'Forbidden',
                'slug' => 'forbidden',
            ]);

        $response->assertForbidden();
    });

    test('validation errors returned for missing fields', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/api/tags', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    });
});

describe('PUT /api/tags/{tag}', function () {
    test('authenticated user can update a tag', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->actingAs($user)
            ->putJson("/api/tags/{$tag->id}", [
                'name' => 'New',
                'slug' => 'new',
            ]);

        $response->assertSuccessful()
            ->assertJsonFragment(['name' => 'New', 'slug' => 'new']);

        expect($tag->fresh()->name)->toBe('New');
    });

    test('authenticated non-privileged user cannot update a tag', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->actingAs($user)
            ->putJson("/api/tags/{$tag->id}", [
                'name' => 'New',
                'slug' => 'new',
            ]);

        $response->assertForbidden();
    });
});

describe('DELETE /api/tags/{tag}', function () {
    test('authenticated user can delete a tag', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Tag supprimé avec succès']);

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });

    test('authenticated non-privileged user cannot delete a tag', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/tags/{$tag->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    });
});
