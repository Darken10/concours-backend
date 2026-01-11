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

test('user can create a post', function () {
    $user = User::factory()->create();
    $user->assignRole('agent');

    $response = $this->actingAs($user)
        ->postJson('/api/posts', [
            'title' => 'My First Post',
            'content' => 'This is my first blog post with enough content',
        ]);

    $response->assertCreated();
    expect($response->json('id'))->not->toBeNull();
    expect(Post::where('title', 'My First Post')->exists())->toBeTrue();
});

test('user can view all posts', function () {
    $response = $this->getJson('/api/posts');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(10); // seeder creates 10 posts
});

test('user can view a single post', function () {
    $post = Post::factory()->create();

    $response = $this->getJson("/api/posts/{$post->id}");

    $response->assertSuccessful();
    expect($response->json('id'))->toBe($post->id);
});

test('user can update their own post', function () {
    $user = User::factory()->create();
    $user->assignRole('agent');
    $post = Post::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content with enough characters here',
        ]);

    $response->assertSuccessful();
    expect($post->fresh()->title)->toBe('Updated Title');
});

test('user cannot update others posts', function () {
    $user = User::factory()->create();
    $user->assignRole('agent');
    $otherUser = User::factory()->create();
    $otherUser->assignRole('agent');
    $post = Post::factory()->for($otherUser)->create();

    $response = $this->actingAs($user)
        ->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content with enough characters here',
        ]);

    $response->assertForbidden();
});

test('user can delete their own post', function () {
    $user = User::factory()->create();
    $user->assignRole('agent');
    $post = Post::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertSuccessful();
    expect(Post::find($post->id))->toBeNull();
});

test('user can like a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/posts/{$post->id}/like");

    $response->assertCreated();
    expect(Like::where('user_id', $user->id)->where('post_id', $post->id)->exists())->toBeTrue();
});

test('user can unlike a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Like::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/posts/{$post->id}/unlike");

    $response->assertSuccessful();
    expect(Like::where('user_id', $user->id)->where('post_id', $post->id)->exists())->toBeFalse();
});

test('user can comment on a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Great post!',
        ]);

    $response->assertCreated();
    expect(Comment::where('post_id', $post->id)->exists())->toBeTrue();
});

test('user can like a comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/comments/{$comment->id}/like");

    $response->assertCreated();
    expect(Like::where('user_id', $user->id)->where('comment_id', $comment->id)->exists())->toBeTrue();
});

test('user can reply to a comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $response = $this->actingAs($user)
        ->postJson("/api/posts/{$comment->post_id}/comments", [
            'content' => 'Reply to comment',
            'parent_id' => $comment->id,
        ]);

    $response->assertCreated();
    expect(Comment::where('parent_id', $comment->id)->exists())->toBeTrue();
});

test('user can view post comments', function () {
    $post = Post::factory()->create();
    Comment::factory(3)->for($post)->create();

    $response = $this->getJson("/api/posts/{$post->id}/comments");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});
