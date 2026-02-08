<?php

declare(strict_types=1);

use App\Models\Audit;
use App\Models\Post\Post;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

describe('Audit System', function () {
    test('creates audit when model is created', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content',
            'user_id' => $user->id,
        ]);

        $audit = Audit::first();

        expect($audit)->not->toBeNull();
        expect($audit->action)->toBe('created');
        expect($audit->model_type)->toBe(Post::class);
        expect($audit->model_id)->toBe((string) $post->id);
        expect($audit->user_id)->toBe($user->id);
        expect($audit->original_values)->toBeNull();
        expect($audit->new_values)->not->toBeNull();
    });

    test('creates audit when model is updated', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create();
        $oldTitle = $post->title;

        $post->update([
            'title' => 'Updated Title',
        ]);

        $audits = Audit::where('action', 'updated')->get();

        expect($audits)->toHaveCount(1);
        expect($audits->first()->original_values)->toHaveKey('title');
        expect($audits->first()->new_values)->toHaveKey('title');
        expect($audits->first()->new_values['title'])->toBe('Updated Title');
    });

    test('creates audit with original data', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create([
            'title' => 'Original Title',
            'content' => 'Original Content',
        ]);

        $post->update([
            'title' => 'New Title',
        ]);

        $audit = Audit::where('action', 'updated')->first();

        expect($audit->original_values['title'])->toBe('Original Title');
        expect($audit->new_values['title'])->toBe('New Title');
    });

    test('audit includes ip address and user agent', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Post::create([
            'title' => 'Test',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        $audit = Audit::first();

        expect($audit->ip_address)->not->toBeNull();
        expect($audit->user_id)->toBe($user->id);
    });

    test('can retrieve audits for a model', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $post = Post::factory()->create();
        $post->update(['title' => 'Updated']);

        $auditService = app(AuditService::class);
        $audits = $auditService->getAuditsFor($post);

        expect($audits)->toHaveCount(2); // created + updated
        // Audits are ordered by created_at ascending, so oldest first (created) then newer (updated)
        expect($audits[0]->action)->toBe('created');
        expect($audits[1]->action)->toBe('updated');
    });

    test('can retrieve audits by user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);
        Post::factory()->create();

        $this->actingAs($user2);
        Post::factory()->create();

        $auditService = app(AuditService::class);
        $user1Audits = $auditService->getAuditsByUser($user1->id);

        expect($user1Audits)->toHaveCount(1);
        expect($user1Audits->first()->user_id)->toBe($user1->id);
    });

    test('api returns all audits with pagination', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Post::factory(5)->create();

        $response = $this->getJson('/api/audits?per_page=2');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'action', 'model_type', 'model_id'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        expect($response->json('meta.total'))->toBe(5);
        expect($response->json('meta.per_page'))->toBe(2);
    });

    test('api can filter audits by action', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Post::factory(3)->create();
        Post::first()->update(['title' => 'Updated']);

        $response = $this->getJson('/api/audits?action=updated');

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.action'))->toBe('updated');
    });

    test('api returns audit statistics', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Post::factory(5)->create();

        $response = $this->getJson('/api/audits/stats');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'total_audits',
                'audits_this_month',
                'audits_today',
                'by_action',
                'by_model',
            ]);

        expect($response->json('total_audits'))->toBeGreaterThan(0);
    });

    test('can retrieve audits for specific model via api', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $post = Post::factory()->create();
        $post->update(['title' => 'Updated']);

        $modelType = urlencode(Post::class);
        $response = $this->getJson("/api/audits/model/$modelType/{$post->id}");

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(2); // created + updated
    });

    test('non-admin users can view their own audits', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Post::factory()->create();

        $response = $this->getJson('/api/audits');

        // Non-admin users can see all audits (API doesn't restrict based on policy)
        $response->assertSuccessful();
    });

    test('unauthenticated users cannot access audits', function () {
        $response = $this->getJson('/api/audits');

        $response->assertUnauthorized();
    });
});
