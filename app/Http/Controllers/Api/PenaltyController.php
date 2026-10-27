<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Penalty\ReviewPenaltyDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Penalty\CalculatePenaltyRequest;
use App\Http\Requests\Penalty\ReviewPenaltyRequest;
use App\Http\Resources\PenaltyResource;
use App\Models\Contract;
use App\Models\Penalty;
use App\Services\Penalty\PenaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PenaltyController extends Controller {
    public function __construct(
        protected PenaltyService $penaltyService,
    ) {}

    public function preview(CalculatePenaltyRequest $request, Contract $contract): JsonResponse {
        Gate::authorize('view', $contract);

        $asOfDate = $request->validated('calculation_date');
        $calculation = $this->penaltyService->calculatePreview($contract, $asOfDate);

        return response()->json([
            'preview' => [
                'contract_id' => $calculation->contractId,
                'calculation_date' => $calculation->calculationDate,
                'deadline_date' => $calculation->deadlineDate,
                'delay_days' => $calculation->delayDays,
                'calculated_amount' => $calculation->calculatedAmount,
                'cap_amount' => $calculation->capAmount,
                'breakdown' => $calculation->breakdown,
            ],
        ]);
    }

    public function store(CalculatePenaltyRequest $request, Contract $contract): JsonResponse {
        Gate::authorize('view', $contract);

        $asOfDate = $request->validated('calculation_date');
        $penalty = $this->penaltyService->calculateAndSave($contract, $asOfDate);

        return response()->json([
            'message' => 'Jarima hisoblandi va boshliq tasdig‘iga yuborildi.',
            'penalty' => new PenaltyResource($penalty->load(['contract', 'reviewer'])),
        ], 201);
    }

    public function review(ReviewPenaltyRequest $request, Penalty $penalty): JsonResponse {
        Gate::authorize('review', Penalty::class);

        $dto = ReviewPenaltyDTO::fromArray($request->validated(), $penalty->id, $request->user()->id);
        $reviewedPenalty = $this->penaltyService->reviewPenalty($dto);

        return response()->json([
            'message' => 'Jarima holati yangilandi.',
            'penalty' => new PenaltyResource($reviewedPenalty),
        ]);
    }
}
