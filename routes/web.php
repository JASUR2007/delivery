<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/quick-login', [AuthController::class, 'quickLogin'])->name('quick.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/demo-switch', [DashboardController::class, 'switchDemoUser'])->name('demo.switch');

    Route::get('/', [DashboardController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/{contract}', [DashboardController::class, 'show'])->name('contracts.show');
    Route::post('/contracts', [DashboardController::class, 'storeContract'])->name('contracts.store');
    Route::post('/contracts/{contract}/activate', [DashboardController::class, 'activateContract'])->name('contracts.activate');
    Route::post('/contracts/{contract}/batches', [DashboardController::class, 'storeBatch'])->name('contracts.batches.store');
    Route::post('/contracts/{contract}/penalties', [DashboardController::class, 'storePenalty'])->name('contracts.penalties.store');

    Route::post('/penalties/{penalty}/review', [DashboardController::class, 'reviewPenalty'])->name('penalties.review');

    Route::get('/budget', [DashboardController::class, 'budget'])->name('budget.index');
    Route::post('/budget', [DashboardController::class, 'storeBudgetItem'])->name('budget.store');

    Route::post('/payments', [DashboardController::class, 'storePayment'])->name('payments.store');
    Route::post('/payments/{payment}/void', [DashboardController::class, 'voidPayment'])->name('payments.void');
});

Route::get('/api/documentation', function () {
    return view('swagger');
})->name('swagger.ui');

Route::get('/swagger', function () {
    return view('swagger');
});
