<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Contract\CreateContractDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\CreateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\Contract\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected ContractService $contractService,
    ) {}

    public function index(Request $request): JsonResponse {
        $user = $request->user();
        $isSupervisorOrAccountant = $user->isSupervisor() || $user->isAccountant();
        $contracts = $this->contractRepository->getAllForUser($user->id, $isSupervisorOrAccountant);

        return response()->json([
            'contracts' => ContractResource::collection($contracts),
        ]);
    }

    public function store(CreateContractRequest $request): JsonResponse {
        $dto = CreateContractDTO::fromArray($request->validated(), $request->user()->id);
        $contract = $this->contractService->createContract($dto);

        return response()->json([
            'message' => 'Shartnoma muvaffaqiyatli yaratildi.',
            'contract' => new ContractResource($contract->load(['creator', 'items', 'paymentMilestones'])),
        ], 201);
    }

    public function show(Contract $contract): JsonResponse {
        Gate::authorize('view', $contract);

        $contractWithDetails = $this->contractRepository->findWithDetails($contract->id);

        return response()->json([
            'contract' => new ContractResource($contractWithDetails),
        ]);
    }

    public function activate(Contract $contract): JsonResponse {
        Gate::authorize('activate', $contract);

        $activated = $this->contractService->activateContract($contract);

        return response()->json([
            'message' => 'Shartnoma muvaffaqiyatli faollashtirildi.',
            'contract' => new ContractResource($this->contractRepository->findWithDetails($activated->id)),
        ]);
    }
}
