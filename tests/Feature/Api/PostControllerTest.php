<?php

declare(strict_types=1);

use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    // Ensure edit posts permission exists
    if (! \Illuminate\Support\Facades\DB::table('permissions')->where('name', 'edit posts')->exists()) {
        \Illuminate\Support\Facades\DB::table('permissions')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'edit posts',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
});

describe('GET /api/posts', function () {
    test('returns paginated list of posts', function () {
        $response = $this->getJson('/api/posts');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'content'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    });

    test('returns correct number of posts per page', function () {
        $response = $this->getJson('/api/posts?per_page=5');

        $response->assertSuccessful();
        expect(count($response->json('data')))->toBeLessThanOrEqual(5);
    });

    test('pagination works correctly', function () {
        Post::factory(20)->create();

        $response = $this->getJson('/api/posts?per_page=10');

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(20);
        expect($response->json('meta.last_page'))->toBeGreaterThanOrEqual(2);
    });

    test('unauthenticated users can view posts', function () {
        $response = $this->getJson('/api/posts');

        $response->assertSuccessful();
    });
});

describe('GET /api/posts/{post}', function () {
    test('returns single post with details', function () {
        $post = Post::factory()->create();

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'id',
                'title',
                'content',
            ])
            ->assertJson([
                'id' => $post->id,
                'title' => $post->title,
            ]);
    });

    test('returns 404 for nonexistent post', function () {
        $response = $this->getJson('/api/posts/99999');

        $response->assertNotFound();
    });

    test('unauthenticated users can view single post', function () {
        $post = Post::factory()->create();

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertSuccessful();
    });
});

describe('POST /api/posts', function () {
    test('authenticated agent can create a post', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $user->givePermissionTo('edit posts');

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'My New Post',
                'content' => 'This is the content of my new post with enough characters',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'title',
                'content',
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'My New Post',
            'user_id' => $user->id,
        ]);
    });

    test('unauthenticated user cannot create post', function () {
        $response = $this->postJson('/api/posts', [
            'title' => 'New Post',
            'content' => 'Post content',
        ]);

        $response->assertUnauthorized();
    });

    test('authenticated non-privileged user cannot create post', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'New Post',
                'content' => 'Post content with enough characters',
            ]);

        $response->assertForbidden();
    });

    test('post requires title', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'content' => 'Post content with enough characters here',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    test('post requires content', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'New Post',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });

    test('post can include media', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $user->givePermissionTo('edit posts');

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'Post with Media',
                'content' => 'This post has media content attached to it',
                'media' => ['https://example.com/image.jpg'],
            ]);

        $response->assertCreated();
    });
});

describe('PUT /api/posts/{post}', function () {
    test('user can update their own post', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $post = Post::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content with enough characters here',
            ]);

        $response->assertSuccessful()
            ->assertJson([
                'title' => 'Updated Title',
            ]);

        expect($post->fresh()->title)->toBe('Updated Title');
    });

    test('user cannot update others post', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $otherUser = User::factory()->create();
        $otherUser->assignRole('agent');
        $post = Post::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    });

    test('unauthenticated user cannot update post', function () {
        $post = Post::factory()->create();

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertUnauthorized();
    });

    test('update requires title', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $post = Post::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    });

    test('update requires content', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $post = Post::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });
});

describe('DELETE /api/posts/{post}', function () {
    test('user can delete their own post', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $post = Post::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Post supprimé avec succès']);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    });

    test('user cannot delete others post', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        $otherUser = User::factory()->create();
        $otherUser->assignRole('agent');
        $post = Post::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    });

    test('unauthenticated user cannot delete post', function () {
        $post = Post::factory()->create();

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertUnauthorized();
    });
});

describe('GET /api/posts/user/posts', function () {
    test('authenticated user can get their posts', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        Post::factory(5)->for($user)->create();
        Post::factory(3)->create(); // Other users' posts

        $response = $this->actingAs($user)
            ->getJson('/api/posts/user/posts');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
    });

    test('unauthenticated user cannot get user posts', function () {
        $response = $this->getJson('/api/posts/user/posts');

        $response->assertUnauthorized();
    });

    test('user posts pagination works', function () {
        $user = User::factory()->create();
        $user->assignRole('agent');
        Post::factory(20)->for($user)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/posts/user/posts?per_page=10');

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBe(20);
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });
});

describe('POST /api/posts/{post}/like', function () {
    test('authenticated user can like a post', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/like");

        $response->assertCreated()
            ->assertJson(['message' => 'Post aimé']);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    });

    test('user can like a post multiple times without error', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Premier like
        $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/like")
            ->assertCreated();

        // Deuxième like ne devrait pas causer d'erreur
        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/like");

        $response->assertCreated();
    });

    test('unauthenticated user cannot like post', function () {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response->assertUnauthorized();
    });
});

describe('POST /api/posts/{post}/unlike', function () {
    test('authenticated user can unlike a post', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Like::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/unlike");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Like supprimé']);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    });

    test('unauthenticated user cannot unlike post', function () {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/unlike");

        $response->assertUnauthorized();
    });
});

describe('GET /api/posts/{post}/likes', function () {
    test('returns paginated list of post likes', function () {
        $post = Post::factory()->create();
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }

        $response = $this->getJson("/api/posts/{$post->id}/likes");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
    });

    test('unauthenticated users can view post likes', function () {
        $post = Post::factory()->create();

        $response = $this->getJson("/api/posts/{$post->id}/likes");

        $response->assertSuccessful();
    });

    test('post likes pagination works', function () {
        $post = Post::factory()->create();
        $users = User::factory(20)->create();

        foreach ($users as $user) {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
        }

        $response = $this->getJson("/api/posts/{$post->id}/likes?per_page=10");

        $response->assertSuccessful();
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });
});
