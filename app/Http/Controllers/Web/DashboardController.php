<?php

namespace App\Http\Controllers\Web;

use App\DTOs\Budget\CreateBudgetItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\DTOs\Payment\CreatePaymentDTO;
use App\DTOs\Payment\VoidPaymentDTO;
use App\DTOs\Penalty\ReviewPenaltyDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\CreateBudgetItemRequest;
use App\Http\Requests\Contract\CreateContractRequest;
use App\Http\Requests\Delivery\CreateDeliveryBatchRequest;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\VoidPaymentRequest;
use App\Http\Requests\Penalty\CalculatePenaltyRequest;
use App\Http\Requests\Penalty\ReviewPenaltyRequest;
use App\Http\Resources\BudgetItemResource;
use App\Http\Resources\ContractResource;
use App\Models\BudgetItem;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\User;
use App\Repositories\Budget\BudgetItemRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\Accounting\AccountingService;
use App\Services\Budget\BudgetService;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use App\Services\Penalty\PenaltyCalculatorService;
use App\Services\Penalty\PenaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected BudgetItemRepositoryInterface $budgetItemRepository,
        protected ContractService $contractService,
        protected DeliveryService $deliveryService,
        protected PenaltyService $penaltyService,
        protected PenaltyCalculatorService $penaltyCalculatorService,
        protected AccountingService $accountingService,
        protected BudgetService $budgetService,
    ) {}

    private function ensureAuthenticated(): User {
        return Auth::user();
    }

    public function index(Request $request): Response {
        $user = $this->ensureAuthenticated();
        $isSupervisorOrAccountant = $user->isSupervisor() || $user->isAccountant();
        $contracts = $this->contractRepository->getAllForUser($user->id, $isSupervisorOrAccountant);

        return Inertia::render('Contracts/Index', [
            'contracts' => ContractResource::collection($contracts)->resolve(),
        ]);
    }

    public function show(Request $request, Contract $contract): Response {
        $user = $this->ensureAuthenticated();
        Gate::authorize('view', $contract);

        $asOfDate = $request->query('calculation_date', now()->toDateString());
        $contractWithDetails = $this->contractRepository->findWithDetails($contract->id);
        $previewCalculation = $this->penaltyCalculatorService->calculate($contractWithDetails, $asOfDate);
        $budgetItems = $this->budgetItemRepository->getAll();

        return Inertia::render('Contracts/Show', [
            'contract' => (new ContractResource($contractWithDetails))->resolve(),
            'penalty_preview' => [
                'calculation_date' => $previewCalculation->calculationDate,
                'deadline_date' => $previewCalculation->deadlineDate,
                'delay_days' => $previewCalculation->delayDays,
                'calculated_amount' => $previewCalculation->calculatedAmount,
                'cap_amount' => $previewCalculation->capAmount,
                'breakdown' => $previewCalculation->breakdown,
            ],
            'budget_items' => BudgetItemResource::collection($budgetItems)->resolve(),
            'query_as_of_date' => $asOfDate,
        ]);
    }

    public function budget(): Response {
        $this->ensureAuthenticated();
        $budgetItems = $this->budgetService->getAll();

        return Inertia::render('Budget/Index', [
            'budget_items' => BudgetItemResource::collection($budgetItems)->resolve(),
        ]);
    }

    public function switchDemoUser(Request $request): RedirectResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        Auth::login($user);

        return redirect()->back()->with('success', sprintf('Foydalanuvchi almashtirildi: %s (%s)', $user->name, $user->role->label()));
    }

    public function storeContract(CreateContractRequest $request): RedirectResponse {
        $dto = CreateContractDTO::fromArray($request->validated(), $request->user()->id);
        $contract = $this->contractService->createContract($dto);

        return redirect()->route('contracts.show', $contract->id)->with('success', 'Shartnoma muvaffaqiyatli yaratildi.');
    }

    public function activateContract(Contract $contract): RedirectResponse {
        Gate::authorize('activate', $contract);
        $this->contractService->activateContract($contract);

        return redirect()->back()->with('success', 'Shartnoma faollashtirildi.');
    }

    public function storeBatch(CreateDeliveryBatchRequest $request, Contract $contract): RedirectResponse {
        Gate::authorize('recordDelivery', $contract);
        $dto = CreateDeliveryBatchDTO::fromArray($request->validated(), $contract->id, $request->user()->id);
        $this->deliveryService->recordBatch($dto);

        return redirect()->back()->with('success', 'Partiya muvaffaqiyatli qabul qilindi.');
    }

    public function storePenalty(CalculatePenaltyRequest $request, Contract $contract): RedirectResponse {
        Gate::authorize('view', $contract);
        $asOfDate = $request->validated('calculation_date');
        $this->penaltyService->calculateAndSave($contract, $asOfDate);

        return redirect()->back()->with('success', 'Jarima hisoblandi va boshliq tasdig‘iga yuborildi.');
    }

    public function reviewPenalty(ReviewPenaltyRequest $request, Penalty $penalty): RedirectResponse {
        Gate::authorize('review', Penalty::class);
        $dto = ReviewPenaltyDTO::fromArray($request->validated(), $penalty->id, $request->user()->id);
        $this->penaltyService->reviewPenalty($dto);

        return redirect()->back()->with('success', 'Jarima bo‘yicha qaror qabul qilindi.');
    }

    public function storePayment(CreatePaymentRequest $request): RedirectResponse {
        Gate::authorize('create', Payment::class);
        $dto = CreatePaymentDTO::fromArray($request->validated(), $request->user()->id);
        $this->accountingService->recordPayment($dto);

        return redirect()->back()->with('success', 'To‘lov muvaffaqiyatli kiritildi.');
    }

    public function voidPayment(VoidPaymentRequest $request, Payment $payment): RedirectResponse {
        Gate::authorize('void', Payment::class);
        $dto = VoidPaymentDTO::fromArray($request->validated(), $payment->id, $request->user()->id);
        $this->accountingService->voidPayment($dto);

        return redirect()->back()->with('success', 'To‘lov muvaffaqiyatli bekor qilindi.');
    }

    public function storeBudgetItem(CreateBudgetItemRequest $request): RedirectResponse {
        Gate::authorize('manage', BudgetItem::class);
        $dto = CreateBudgetItemDTO::fromArray($request->validated());
        $this->budgetService->createBudgetItem($dto);

        return redirect()->back()->with('success', 'Smeta qatori muvaffaqiyatli qo‘shildi.');
    }
}
