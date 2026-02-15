<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-with-organization', [AuthController::class, 'registerWithOrganization']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social/{provider}', [SocialAuthController::class, 'handleProvider']);

    // Email verification routes
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);

    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('organizations')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [OrganizationController::class, 'store']);
        Route::get('/{organization}', [OrganizationController::class, 'show']);
        Route::post('/{organization}/agents', [OrganizationController::class, 'createAgent']);
        Route::post('/{organization}/admins', [OrganizationController::class, 'assignAdmin']);
    });
});
