<?php

declare(strict_types=1);

use App\Models\Post\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('GET /api/categories', function () {
    test('returns paginated list of categories', function () {
        Category::factory(10)->create();

        $response = $this->getJson('/api/categories');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    });

    test('returns single category details', function () {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $category->id, 'name' => $category->name]);
    });
});

describe('POST /api/categories', function () {
    test('authenticated user can create a category', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/categories', [
                'name' => 'Test Category',
                'slug' => 'test-category',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'name', 'slug']);

        $this->assertDatabaseHas('categories', ['slug' => 'test-category']);
    });

    test('unauthenticated user cannot create category', function () {
        $response = $this->postJson('/api/categories', [
            'name' => 'No Auth',
            'slug' => 'no-auth',
        ]);

        $response->assertUnauthorized();
    });

    test('validation errors returned for missing fields', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/categories', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    });
});

describe('PUT /api/categories/{category}', function () {
    test('authenticated user can update a category', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old']);

        $response = $this->actingAs($user)
            ->putJson("/api/categories/{$category->id}", [
                'name' => 'New',
                'slug' => 'new',
            ]);

        $response->assertSuccessful()
            ->assertJsonFragment(['name' => 'New', 'slug' => 'new']);

        expect($category->fresh()->name)->toBe('New');
    });
});

describe('DELETE /api/categories/{category}', function () {
    test('authenticated user can delete a category', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Catégorie supprimée avec succès']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });
});
