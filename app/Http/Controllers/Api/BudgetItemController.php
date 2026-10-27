<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Budget\CreateBudgetItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\CreateBudgetItemRequest;
use App\Http\Resources\BudgetItemResource;
use App\Models\BudgetItem;
use App\Services\Budget\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetItemController extends Controller {
    public function __construct(
        protected BudgetService $budgetService,
    ) {}

    public function index(Request $request): JsonResponse {
        $year = $request->query('year');
        $items = $year !== null ? $this->budgetService->getByYear((int) $year) : $this->budgetService->getAll();

        return response()->json([
            'budget_items' => BudgetItemResource::collection($items),
        ]);
    }

    public function store(CreateBudgetItemRequest $request): JsonResponse {
        Gate::authorize('manage', BudgetItem::class);

        $dto = CreateBudgetItemDTO::fromArray($request->validated());
        $budgetItem = $this->budgetService->createBudgetItem($dto);

        return response()->json([
            'message' => 'Smeta qatori muvaffaqiyatli yaratildi.',
            'budget_item' => new BudgetItemResource($budgetItem),
        ], 201);
    }
}
