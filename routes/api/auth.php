<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\OrganizationController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-with-organization', [AuthController::class, 'registerWithOrganization']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/social/{provider}', [SocialAuthController::class, 'handleProvider']);

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
