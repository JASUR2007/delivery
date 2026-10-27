<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Payment\CreatePaymentDTO;
use App\DTOs\Payment\VoidPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\VoidPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Contract;
use App\Models\Payment;
use App\Repositories\Accounting\PaymentRepositoryInterface;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller {
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected AccountingService $accountingService,
    ) {}

    public function index(Contract $contract): JsonResponse {
        Gate::authorize('view', $contract);

        $payments = $this->paymentRepository->getByContractId($contract->id);

        return response()->json([
            'payments' => PaymentResource::collection($payments),
        ]);
    }

    public function store(CreatePaymentRequest $request): JsonResponse {
        Gate::authorize('create', Payment::class);

        $dto = CreatePaymentDTO::fromArray($request->validated(), $request->user()->id);
        $payment = $this->accountingService->recordPayment($dto);

        return response()->json([
            'message' => 'To‘lov muvaffaqiyatli kiritildi.',
            'payment' => new PaymentResource($this->paymentRepository->findByIdWithDetails($payment->id)),
        ], 201);
    }

    public function voidPayment(VoidPaymentRequest $request, Payment $payment): JsonResponse {
        Gate::authorize('void', Payment::class);

        $dto = VoidPaymentDTO::fromArray($request->validated(), $payment->id, $request->user()->id);
        $voidedPayment = $this->accountingService->voidPayment($dto);

        return response()->json([
            'message' => 'To‘lov muvaffaqiyatli bekor qilindi.',
            'payment' => new PaymentResource($voidedPayment),
        ]);
    }
}
