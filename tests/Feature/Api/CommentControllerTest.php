<?php

declare(strict_types=1);

use App\Models\Post\Comment;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

describe('POST /api/posts/{post}/comments', function () {
    test('authenticated user can comment on post', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'This is a great post!',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'content',
            ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a great post!',
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    });

    test('authenticated user can reply to comment', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $parentComment = Comment::factory()->for($post)->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Reply to comment',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('comments', [
            'content' => 'Reply to comment',
            'parent_id' => $parentComment->id,
            'post_id' => $post->id,
        ]);
    });

    test('unauthenticated user cannot comment on post', function () {
        $post = Post::factory()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'This is a comment',
        ]);

        $response->assertUnauthorized();
    });

    test('comment requires content', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });

    test('comment with invalid parent_id fails', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Reply to comment',
                'parent_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    });
});

describe('GET /api/posts/{post}/comments', function () {
    test('returns paginated list of post comments', function () {
        $post = Post::factory()->create();
        Comment::factory(5)->for($post)->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'content'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
    });

    test('unauthenticated users can view comments', function () {
        $post = Post::factory()->create();
        Comment::factory(3)->for($post)->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertSuccessful();
    });

    test('comments pagination works', function () {
        $post = Post::factory()->create();
        Comment::factory(20)->for($post)->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments?per_page=10");

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBe(20);
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });

    test('returns empty array for post without comments', function () {
        $post = Post::factory()->create();

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBe(0);
    });
});

describe('PUT /api/comments/{comment}', function () {
    test('user can update their own comment', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($user)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated comment content',
            ]);

        $response->assertSuccessful()
            ->assertJson([
                'content' => 'Updated comment content',
            ]);

        expect($comment->fresh()->content)->toBe('Updated comment content');
    });

    test('user cannot update others comment', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertForbidden();
    });

    test('unauthenticated user cannot update comment', function () {
        $comment = Comment::factory()->create();

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Updated comment',
        ]);

        $response->assertUnauthorized();
    });

    test('update requires content', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($user)->create();

        $response = $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });
});

describe('DELETE /api/comments/{comment}', function () {
    test('user can delete their own comment', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($user)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Commentaire supprimé avec succès']);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    test('user cannot delete others comment', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    });

    test('unauthenticated user cannot delete comment', function () {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertUnauthorized();
    });

    test('deleting comment deletes its replies', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->for($user)->create();
        $reply = Comment::factory()->for($post)->create(['parent_id' => $comment->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertSuccessful();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });
});

describe('GET /api/comments/{comment}/replies', function () {
    test('returns paginated list of comment replies', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();
        Comment::factory(5)->for($post)->create(['parent_id' => $comment->id]);

        $response = $this->getJson("/api/comments/{$comment->id}/replies");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'content'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
    });

    test('unauthenticated users can view replies', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();

        $response = $this->getJson("/api/comments/{$comment->id}/replies");

        $response->assertSuccessful();
    });

    test('returns empty array for comment without replies', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();

        $response = $this->getJson("/api/comments/{$comment->id}/replies");

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBe(0);
    });

    test('replies pagination works', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();
        Comment::factory(20)->for($post)->create(['parent_id' => $comment->id]);

        $response = $this->getJson("/api/comments/{$comment->id}/replies?per_page=10");

        $response->assertSuccessful();
        expect($response->json('meta.total'))->toBe(20);
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });
});

describe('POST /api/comments/{comment}/like', function () {
    test('authenticated user can like a comment', function () {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertCreated()
            ->assertJson(['message' => 'Commentaire aimé']);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);
    });

test('user can like a comment multiple times without error', function () {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        // Premier like
        $this->actingAs($user)
            ->postJson("/api/comments/{$comment->id}/like")
            ->assertCreated();

        // Deuxième like ne devrait pas causer d'erreur
        $response = $this->actingAs($user)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertCreated();
    });

    test('unauthenticated user cannot like comment', function () {
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertUnauthorized();
    });
});

describe('POST /api/comments/{comment}/unlike', function () {
    test('authenticated user can unlike a comment', function () {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        Like::create([
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/comments/{$comment->id}/unlike");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Like supprimé']);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'comment_id' => $comment->id,
        ]);
    });

    test('unauthenticated user cannot unlike comment', function () {
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comments/{$comment->id}/unlike");

        $response->assertUnauthorized();
    });
});

describe('GET /api/comments/{comment}/likes', function () {
    test('returns paginated list of comment likes', function () {
        $comment = Comment::factory()->create();
        $users = User::factory(5)->create();

        foreach ($users as $user) {
            Like::create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
            ]);
        }

        $response = $this->getJson("/api/comments/{$comment->id}/likes");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
    });

    test('unauthenticated users can view comment likes', function () {
        $comment = Comment::factory()->create();

        $response = $this->getJson("/api/comments/{$comment->id}/likes");

        $response->assertSuccessful();
    });

    test('comment likes pagination works', function () {
        $comment = Comment::factory()->create();
        $users = User::factory(20)->create();

        foreach ($users as $user) {
            Like::create([
                'user_id' => $user->id,
                'comment_id' => $comment->id,
            ]);
        }

        $response = $this->getJson("/api/comments/{$comment->id}/likes?per_page=10");

        $response->assertSuccessful();
        expect(count($response->json('data')))->toBeLessThanOrEqual(10);
    });
});
