<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
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

Route::prefix('posts')->group(function () {
    // Routes publiques
    Route::get('/', [PostController::class, 'index']);
    Route::get('/{post}', [PostController::class, 'show']);
    Route::get('/{post}/likes', [PostController::class, 'likes']);
    Route::get('/{post}/comments', [CommentController::class, 'postComments']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);

        Route::get('/user/posts', [PostController::class, 'userPosts']);

        // Likes
        Route::post('/{post}/like', [PostController::class, 'like']);
        Route::post('/{post}/unlike', [PostController::class, 'unlike']);

        // Comments
        Route::post('/{post}/comments', [CommentController::class, 'store']);
    });
});

Route::prefix('comments')->group(function () {
    // Routes publiques
    Route::get('/{comment}/replies', [CommentController::class, 'commentReplies']);
    Route::get('/{comment}/likes', [CommentController::class, 'likes']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);

        // Likes
        Route::post('/{comment}/like', [CommentController::class, 'like']);
        Route::post('/{comment}/unlike', [CommentController::class, 'unlike']);
    });
});

Route::prefix('categories')->group(function () {
    // Routes publiques
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });
});

Route::prefix('tags')->group(function () {
    // Routes publiques
    Route::get('/', [TagController::class, 'index']);
    Route::get('/{tag}', [TagController::class, 'show']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [TagController::class, 'store']);
        Route::put('/{tag}', [TagController::class, 'update']);
        Route::delete('/{tag}', [TagController::class, 'destroy']);
    });
});
