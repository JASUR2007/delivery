<?php

namespace Tests\Feature;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Payment\CreatePaymentDTO;
use App\DTOs\Payment\VoidPaymentDTO;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\PaymentValidationException;
use App\Models\BudgetItem;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Contract\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRegistryAndVoidTest extends TestCase {
    use RefreshDatabase;

    private User $executor;

    private User $accountant;

    private ContractService $contractService;

    private AccountingService $accountingService;

    private BudgetItem $budget;

    protected function setUp(): void {
        parent::setUp();

        $this->executor = User::factory()->create(['role' => UserRole::Executor]);
        $this->accountant = User::factory()->create(['role' => UserRole::Accountant]);

        $this->contractService = app(ContractService::class);
        $this->accountingService = app(AccountingService::class);

        $this->budget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '3000.00',
            'notes' => 'March budget',
        ]);
    }

    private function createActiveContract(): Contract {
        $contract = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'TEST-VOID-001',
            supplierName: 'State Med Supplier',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(
                    itemName: 'Dori vositalari',
                    paidQuantity: 1000,
                    bonusQuantity: 0,
                    unitPrice: '2.50'
                ),
            ],
            createdBy: $this->executor->id
        ));

        return $this->contractService->activateContract($contract);
    }

    public function test_example_c_payment_voiding_recalculates_milestone_and_budget_balances(): void {
        $contract = $this->createActiveContract();
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $firstPayment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '750.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-ERR-001',
            comment: 'Erroneous payment',
            createdBy: $this->accountant->id
        ));

        $this->assertSame('2250.00', $this->budget->fresh()->getRemainingBalance());
        $this->assertSame('750.00', $advanceMilestone->fresh()->getPaidAmount());

        $voidedPayment = $this->accountingService->voidPayment(new VoidPaymentDTO(
            paymentId: $firstPayment->id,
            voidReason: 'Buxgalter 750 o‘rniga adashib kiritgan, qayta to‘g‘rilanadi',
            voidedBy: $this->accountant->id
        ));

        $this->assertDatabaseHas('payments', ['id' => $firstPayment->id]);
        $this->assertSame(PaymentStatus::Voided, $voidedPayment->status);
        $this->assertSame('Buxgalter 750 o‘rniga adashib kiritgan, qayta to‘g‘rilanadi', $voidedPayment->void_reason);
        $this->assertSame($this->accountant->id, $voidedPayment->voided_by);
        $this->assertNotNull($voidedPayment->voided_at);

        $this->assertSame('3000.00', $this->budget->fresh()->getRemainingBalance());
        $this->assertSame('0.00', $advanceMilestone->fresh()->getPaidAmount());
        $this->assertSame('750.00', $advanceMilestone->fresh()->getRemainingBalance());

        $secondPayment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '600.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-CORRECT-002',
            comment: 'Corrected payment',
            createdBy: $this->accountant->id
        ));

        $this->assertSame('2400.00', $this->budget->fresh()->getRemainingBalance());
        $this->assertSame('600.00', $advanceMilestone->fresh()->getPaidAmount());
        $this->assertSame('150.00', $advanceMilestone->fresh()->getRemainingBalance());
    }

    public function test_voiding_an_already_voided_payment_throws_exception(): void {
        $contract = $this->createActiveContract();
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $payment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '500.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-003',
            comment: 'Test payment',
            createdBy: $this->accountant->id
        ));

        $this->accountingService->voidPayment(new VoidPaymentDTO(
            paymentId: $payment->id,
            voidReason: 'Birinchi bekor qilish',
            voidedBy: $this->accountant->id
        ));

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('To‘lov allaqachon bekor qilingan.');

        $this->accountingService->voidPayment(new VoidPaymentDTO(
            paymentId: $payment->id,
            voidReason: 'Ikkinchi bekor qilishga urinish',
            voidedBy: $this->accountant->id
        ));
    }

    public function test_voiding_payment_requires_non_empty_reason(): void {
        $contract = $this->createActiveContract();
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $payment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '500.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-004',
            comment: 'Test payment',
            createdBy: $this->accountant->id
        ));

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('To‘lovni bekor qilish sababi ko‘rsatilishi shart.');

        $this->accountingService->voidPayment(new VoidPaymentDTO(
            paymentId: $payment->id,
            voidReason: '   ',
            voidedBy: $this->accountant->id
        ));
    }

    public function test_active_and_voided_payments_can_be_queried_distinctly(): void {
        $contract = $this->createActiveContract();
        $advanceMilestone = $contract->paymentMilestones->firstWhere('step_order', 1);

        $payment1 = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '200.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-1',
            comment: 'Payment 1',
            createdBy: $this->accountant->id
        ));

        $payment2 = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advanceMilestone->id,
            budgetItemId: $this->budget->id,
            amount: '300.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'PAY-2',
            comment: 'Payment 2',
            createdBy: $this->accountant->id
        ));

        $this->accountingService->voidPayment(new VoidPaymentDTO(
            paymentId: $payment1->id,
            voidReason: 'Adashgan to‘lov',
            voidedBy: $this->accountant->id
        ));

        $activePayments = Payment::where('contract_id', $contract->id)->where('status', PaymentStatus::Active->value)->get();
        $voidedPayments = Payment::where('contract_id', $contract->id)->where('status', PaymentStatus::Voided->value)->get();

        $this->assertCount(1, $activePayments);
        $this->assertSame('300.00', $activePayments->first()->amount);
        $this->assertCount(1, $voidedPayments);
        $this->assertSame('200.00', $voidedPayments->first()->amount);
    }
}
