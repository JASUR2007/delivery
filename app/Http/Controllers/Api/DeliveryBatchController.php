<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\CreateDeliveryBatchRequest;
use App\Http\Resources\DeliveryBatchResource;
use App\Models\Contract;
use App\Repositories\Delivery\DeliveryBatchRepositoryInterface;
use App\Services\Delivery\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DeliveryBatchController extends Controller {
    public function __construct(
        protected DeliveryBatchRepositoryInterface $deliveryBatchRepository,
        protected DeliveryService $deliveryService,
    ) {}

    public function index(Contract $contract): JsonResponse {
        Gate::authorize('view', $contract);

        $batches = $this->deliveryBatchRepository->getByContractId($contract->id);

        return response()->json([
            'batches' => DeliveryBatchResource::collection($batches),
        ]);
    }

    public function store(CreateDeliveryBatchRequest $request, Contract $contract): JsonResponse {
        Gate::authorize('recordDelivery', $contract);

        $dto = CreateDeliveryBatchDTO::fromArray($request->validated(), $contract->id, $request->user()->id);
        $batch = $this->deliveryService->recordBatch($dto);

        return response()->json([
            'message' => 'Partiya muvaffaqiyatli qabul qilindi.',
            'batch' => new DeliveryBatchResource($batch),
        ], 201);
    }
}
