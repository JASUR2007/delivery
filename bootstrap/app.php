<?php

use App\Exceptions\ContractValidationException;
use App\Exceptions\DeliveryBatchValidationException;
use App\Exceptions\PaymentValidationException;
use App\Exceptions\PenaltyValidationException;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            function (Request $request): bool {
                return $request->is('api/*') || $request->expectsJson();
            }
        );

        $exceptions->render(function (ContractValidationException $exception): JsonResponse {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });

        $exceptions->render(function (DeliveryBatchValidationException $exception): JsonResponse {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });

        $exceptions->render(function (PenaltyValidationException $exception): JsonResponse {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });

        $exceptions->render(function (PaymentValidationException $exception): JsonResponse {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        });
    })->create();
