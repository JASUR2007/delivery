<?php

namespace Tests\Feature;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\DTOs\Delivery\DeliveryBatchItemDTO;
use App\DTOs\Payment\CreatePaymentDTO;
use App\Enums\MilestoneStatus;
use App\Enums\UserRole;
use App\Exceptions\PaymentValidationException;
use App\Models\BudgetItem;
use App\Models\Contract;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPaymentValidationTest extends TestCase {
    use RefreshDatabase;

    private User $executor;

    private User $accountant;

    private ContractService $contractService;

    private DeliveryService $deliveryService;

    private AccountingService $accountingService;

    private BudgetItem $marchBudget;

    private BudgetItem $aprilBudget;

    protected function setUp(): void {
        parent::setUp();

        $this->executor = User::factory()->create(['role' => UserRole::Executor]);
        $this->accountant = User::factory()->create(['role' => UserRole::Accountant]);

        $this->contractService = app(ContractService::class);
        $this->deliveryService = app(DeliveryService::class);
        $this->accountingService = app(AccountingService::class);

        $this->marchBudget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '3000.00',
            'notes' => 'March budget',
        ]);

        $this->aprilBudget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 4,
            'allocated_amount' => '2000.00',
            'notes' => 'April budget',
        ]);
    }

    private function createContract(bool $activate = true): Contract {
        $contract = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'TEST-ACCT-001',
            supplierName: 'State Med Supplier',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(
                    itemName: 'Dori vositalari',
                    paidQuantity: 1000,
                    bonusQuantity: 50,
                    unitPrice: '2.50'
                ),
            ],
            createdBy: $this->executor->id
        ));

        if ($activate) {
            $contract = $this->contractService->activateContract($contract);
        }

        return $contract;
    }

    public function test_example_a_payment_rejected_when_contract_is_draft_then_succeeds_when_active(): void {
        $draftContract = $this->createContract(activate: false);
        $advanceMilestone = $draftContract->paymentMilestones->firstWhere('step_order', 1);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Shartnoma faol yoki yakunlangan holatda bo‘lishi kerak.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $draftContract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-001',
            comment: 'Advance',
            createdBy: $this->accountant->id
        ));
    }

    public function test_example_a_active_contract_advance_payment_updates_march_budget_and_milestone_status(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $payment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-001',
            comment: 'Advance payment',
            createdBy: $this->accountant->id
        ));

        $this->assertNotNull($payment->id);
        $this->assertSame('2250.00', $this->marchBudget->fresh()->getRemainingBalance());
        $this->assertSame('750.00', $advanceMilestone->fresh()->getPaidAmount());
        $this->assertSame('0.00', $advanceMilestone->fresh()->getRemainingBalance());
        $this->assertSame(MilestoneStatus::Paid, $advanceMilestone->fresh()->getComputedStatus());
    }

    public function test_example_b_delivery_milestone_rejected_if_goods_not_fully_delivered(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);
        $deliveryMilestone = $contract->paymentMilestones->firstWhere('step_order', 2);

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-ADV',
            comment: 'Advance',
            createdBy: $this->accountant->id
        ));

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Bosqichning to‘lash sharti hali bajarilmagan.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $deliveryMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '1750.00',
            paymentDate: '2026-03-10',
            paymentNumber: 'PAY-DELIV',
            comment: 'Delivery payment before goods arrived',
            createdBy: $this->accountant->id
        ));
    }

    public function test_example_b_full_delivery_partial_payment_and_rejection_when_exceeding_remaining_balance(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);
        $deliveryMilestone = $contract->paymentMilestones->firstWhere('step_order', 2);
        $item = $contract->items->first();

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-ADV',
            comment: 'Advance',
            createdBy: $this->accountant->id
        ));

        $this->deliveryService->recordBatch(new CreateDeliveryBatchDTO(
            contractId: $contract->id,
            deliveryDate: '2026-03-10',
            notes: 'All items delivered',
            items: [
                new DeliveryBatchItemDTO(
                    contractItemId: $item->id,
                    deliveredPaidQuantity: 1000,
                    deliveredBonusQuantity: 50
                ),
            ],
            createdBy: $this->executor->id
        ));

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $deliveryMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '1000.00',
            paymentDate: '2026-03-11',
            paymentNumber: 'PAY-DELIV-1',
            comment: 'Partial delivery payment',
            createdBy: $this->accountant->id
        ));

        $this->assertSame(MilestoneStatus::PartiallyPaid, $deliveryMilestone->fresh()->getComputedStatus(Carbon::parse('2026-03-11')));
        $this->assertSame('750.00', $deliveryMilestone->fresh()->getRemainingBalance());
        $this->assertSame('1250.00', $this->marchBudget->fresh()->getRemainingBalance());

        try {
            $this->accountingService->recordPayment(new CreatePaymentDTO(
                contractId: $contract->id,
                milestoneId: $deliveryMilestone->id,
                budgetItemId: $this->marchBudget->id,
                amount: '800.00',
                paymentDate: '2026-03-12',
                paymentNumber: 'PAY-DELIV-EXCEED',
                comment: 'Exceeding remaining balance',
                createdBy: $this->accountant->id
            ));
            $this->fail('Expected PaymentValidationException was not thrown.');
        } catch (PaymentValidationException $exception) {
            $this->assertSame('To‘lov summasi bosqichning qolgan summasidan ko‘p bo‘lishi mumkin emas.', $exception->getMessage());
        }

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $deliveryMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-12',
            paymentNumber: 'PAY-DELIV-FINAL',
            comment: 'Final delivery payment',
            createdBy: $this->accountant->id
        ));

        $this->assertSame(MilestoneStatus::Paid, $deliveryMilestone->fresh()->getComputedStatus());
        $this->assertSame('0.00', $deliveryMilestone->fresh()->getRemainingBalance());
        $this->assertSame('500.00', $this->marchBudget->fresh()->getRemainingBalance());
    }

    public function test_payment_rejected_if_previous_milestone_is_not_fully_paid(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);
        $deliveryMilestone = $contract->paymentMilestones->firstWhere('step_order', 2);
        $item = $contract->items->first();

        $this->deliveryService->recordBatch(new CreateDeliveryBatchDTO(
            contractId: $contract->id,
            deliveryDate: '2026-03-10',
            notes: 'Goods delivered',
            items: [
                new DeliveryBatchItemDTO(
                    contractItemId: $item->id,
                    deliveredPaidQuantity: 1000,
                    deliveredBonusQuantity: 50
                ),
            ],
            createdBy: $this->executor->id
        ));

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '500.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-ADV-PARTIAL',
            comment: 'Partial advance',
            createdBy: $this->accountant->id
        ));

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Oldingi bosqich to‘liq to‘langan bo‘lishi kerak.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $deliveryMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '500.00',
            paymentDate: '2026-03-11',
            paymentNumber: 'PAY-DELIV',
            comment: 'Delivery milestone payment without full advance',
            createdBy: $this->accountant->id
        ));
    }

    public function test_payment_rejected_if_amount_is_zero_or_negative(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('To‘lov summasi noldan katta bo‘lishi kerak.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '0.00',
            paymentDate: '2026-03-03',
            paymentNumber: 'PAY-ZERO',
            comment: 'Zero payment',
            createdBy: $this->accountant->id
        ));
    }

    public function test_payment_rejected_if_date_does_not_match_budget_month(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('To‘lov sanasi tanlangan smeta qatori (yil va oy)ga mos emas.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->marchBudget->id,
            amount: '750.00',
            paymentDate: '2026-04-05',
            paymentNumber: 'PAY-WRONG-MONTH',
            comment: 'April date for March budget',
            createdBy: $this->accountant->id
        ));
    }

    public function test_payment_rejected_if_amount_exceeds_budget_balance(): void {
        $contract = $this->createContract(activate: true);
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $smallBudget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Maxsus modda',
            'month' => 3,
            'allocated_amount' => '500.00',
            'notes' => 'Limited budget',
        ]);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('To‘lov summasi ushbu oyning smeta qoldig‘idan ko‘p.');

        $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $smallBudget->id,
            amount: '750.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-OVER-BUDGET',
            comment: 'Over budget',
            createdBy: $this->accountant->id
        ));
    }
}
