<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuditController;

Route::prefix('audits')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [AuditController::class, 'index']);
    Route::get('/stats', [AuditController::class, 'stats']);
    Route::get('/{audit}', [AuditController::class, 'show']);
    Route::get('/user/{userId}', [AuditController::class, 'userAudits']);
    Route::get('/model/{modelType}/{modelId}', [AuditController::class, 'modelAudits']);
});
