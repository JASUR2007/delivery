<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetItemController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DeliveryBatchController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PenaltyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/switch-demo-user', [AuthController::class, 'switchDemoUser']);
    Route::get('/demo-users', [AuthController::class, 'listDemoUsers']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'currentUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/contracts', [ContractController::class, 'index']);
    Route::post('/contracts', [ContractController::class, 'store']);
    Route::get('/contracts/{contract}', [ContractController::class, 'show']);
    Route::post('/contracts/{contract}/activate', [ContractController::class, 'activate']);

    Route::get('/contracts/{contract}/delivery-batches', [DeliveryBatchController::class, 'index']);
    Route::post('/contracts/{contract}/delivery-batches', [DeliveryBatchController::class, 'store']);

    Route::get('/contracts/{contract}/penalties/preview', [PenaltyController::class, 'preview']);
    Route::post('/contracts/{contract}/penalties', [PenaltyController::class, 'store']);
    Route::post('/penalties/{penalty}/review', [PenaltyController::class, 'review']);

    Route::get('/contracts/{contract}/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/{payment}/void', [PaymentController::class, 'voidPayment']);

    Route::get('/budget-items', [BudgetItemController::class, 'index']);
    Route::post('/budget-items', [BudgetItemController::class, 'store']);
});
