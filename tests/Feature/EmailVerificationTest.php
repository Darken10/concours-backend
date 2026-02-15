<?php

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    // Seed roles and permissions before each test
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('user receives verification code on registration', function () {
    $response = $this->postJson('/api/auth/register', [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john@example.com',
        'gender' => 'male',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSuccessful();

    $user = User::where('email', 'john@example.com')->first();

    expect($user)->not->toBeNull();

    // Refresh the user to get the latest data from the database
    $user->refresh();

    expect($user->email_verification_code)->not->toBeNull()
        ->and($user->email_verification_code)->toHaveLength(6)
        ->and($user->email_verification_code_expires_at)->not->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, EmailVerificationCode::class);
});

test('user can verify email with valid code', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(15),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/email/verify', [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Email vérifié avec succès',
        ]);

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verification_code)->toBeNull()
        ->and($user->email_verification_code_expires_at)->toBeNull();
});

test('user cannot verify email with invalid code', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(15),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/email/verify', [
        'email' => $user->email,
        'code' => '999999',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Code de vérification invalide',
        ]);

    $user->refresh();

    expect($user->email_verified_at)->toBeNull();
});

test('user cannot verify email with expired code', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->subMinutes(1),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/email/verify', [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Code de vérification expiré',
        ]);

    $user->refresh();

    expect($user->email_verified_at)->toBeNull();
});

test('user cannot verify already verified email', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(15),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/email/verify', [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Email déjà vérifié',
        ]);
});

test('user can resend verification code', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->subMinutes(1),
        'email_verified_at' => null,
    ]);

    $oldCode = $user->email_verification_code;

    $response = $this->postJson('/api/auth/email/resend', [
        'email' => $user->email,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'message' => 'Code de vérification renvoyé avec succès',
        ]);

    $user->refresh();

    expect($user->email_verification_code)->not->toBe($oldCode)
        ->and($user->email_verification_code)->toHaveLength(6)
        ->and($user->email_verification_code_expires_at)->toBeGreaterThan(now());

    Notification::assertSentTo($user, EmailVerificationCode::class);
});

test('user cannot resend verification code if already verified', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/auth/email/resend', [
        'email' => $user->email,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'message' => 'Email déjà vérifié',
        ]);
});

test('verification requires valid email', function () {
    $response = $this->postJson('/api/auth/email/verify', [
        'email' => 'nonexistent@example.com',
        'code' => '123456',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('verification requires code with 6 digits', function () {
    $user = User::factory()->create([
        'email_verification_code' => '123456',
        'email_verification_code_expires_at' => now()->addMinutes(15),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/email/verify', [
        'email' => $user->email,
        'code' => '12345',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('user cannot login without email verification', function () {
    $user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'unverified@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
