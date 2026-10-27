<?php

namespace Tests\Feature;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Payment\CreatePaymentDTO;
use App\Enums\UserRole;
use App\Exceptions\PaymentValidationException;
use App\Models\BudgetItem;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Contract\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrentPaymentLockingTest extends TestCase {
    use RefreshDatabase;

    private User $executor;

    private User $accountant1;

    private User $accountant2;

    private ContractService $contractService;

    private AccountingService $accountingService;

    protected function setUp(): void {
        parent::setUp();

        $this->executor = User::factory()->create(['role' => UserRole::Executor]);
        $this->accountant1 = User::factory()->create(['role' => UserRole::Accountant]);
        $this->accountant2 = User::factory()->create(['role' => UserRole::Accountant]);

        $this->contractService = app(ContractService::class);
        $this->accountingService = app(AccountingService::class);
    }

    public function test_concurrent_payment_on_same_month_budget_limit_allows_only_first_and_rejects_second(): void {
        $budget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '1000.00',
            'notes' => 'Limited budget 1000.00',
        ]);

        $contract1 = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'LOCK-001',
            supplierName: 'Supplier 1',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(itemName: 'Dori 1', paidQuantity: 1000, bonusQuantity: 0, unitPrice: '2.50'),
            ],
            createdBy: $this->executor->id
        ));
        $this->contractService->activateContract($contract1);
        $advance1 = $contract1->paymentMilestones->firstWhere('step_order', 1);

        $contract2 = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'LOCK-002',
            supplierName: 'Supplier 2',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(itemName: 'Dori 2', paidQuantity: 1000, bonusQuantity: 0, unitPrice: '2.50'),
            ],
            createdBy: $this->executor->id
        ));
        $this->contractService->activateContract($contract2);
        $advance2 = $contract2->paymentMilestones->firstWhere('step_order', 1);

        $successfulPayment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract1->id,
            milestoneId: $advance1->id,
            budgetItemId: $budget->id,
            amount: '750.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'CONCUR-PAY-1',
            comment: 'First payment of 750',
            createdBy: $this->accountant1->id
        ));

        $this->assertNotNull($successfulPayment->id);
        $this->assertSame('250.00', $budget->fresh()->getRemainingBalance());

        try {
            $this->accountingService->recordPayment(new CreatePaymentDTO(
                contractId: $contract2->id,
                milestoneId: $advance2->id,
                budgetItemId: $budget->id,
                amount: '750.00',
                paymentDate: '2026-03-05',
                paymentNumber: 'CONCUR-PAY-2',
                comment: 'Second payment attempting to use 750 from remaining 250',
                createdBy: $this->accountant2->id
            ));
            $this->fail('Second concurrent payment should have failed due to budget exhaustion.');
        } catch (PaymentValidationException $exception) {
            $this->assertSame('To‘lov summasi ushbu oyning smeta qoldig‘idan ko‘p.', $exception->getMessage());
        }

        $this->assertSame('250.00', $budget->fresh()->getRemainingBalance());
    }

    public function test_concurrent_payments_on_same_milestone_prevents_overpayment(): void {
        $budget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '5000.00',
            'notes' => 'Ample budget',
        ]);

        $contract = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'LOCK-003',
            supplierName: 'Supplier 3',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(itemName: 'Dori 3', paidQuantity: 1000, bonusQuantity: 0, unitPrice: '2.50'),
            ],
            createdBy: $this->executor->id
        ));
        $this->contractService->activateContract($contract);
        $advance = $contract->paymentMilestones->firstWhere('step_order', 1);

        $firstPayment = $this->accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract->id,
            milestoneId: $advance->id,
            budgetItemId: $budget->id,
            amount: '500.00',
            paymentDate: '2026-03-05',
            paymentNumber: 'MILESTONE-PAY-1',
            comment: 'First payment of 500',
            createdBy: $this->accountant1->id
        ));

        $this->assertNotNull($firstPayment->id);
        $this->assertSame('250.00', $advance->fresh()->getRemainingBalance());

        try {
            $this->accountingService->recordPayment(new CreatePaymentDTO(
                contractId: $contract->id,
                milestoneId: $advance->id,
                budgetItemId: $budget->id,
                amount: '500.00',
                paymentDate: '2026-03-05',
                paymentNumber: 'MILESTONE-PAY-2',
                comment: 'Second payment of 500 when only 250 remains',
                createdBy: $this->accountant2->id
            ));
            $this->fail('Second payment exceeding milestone remaining balance should have failed.');
        } catch (PaymentValidationException $exception) {
            $this->assertSame('To‘lov summasi bosqichning qolgan summasidan ko‘p bo‘lishi mumkin emas.', $exception->getMessage());
        }

        $this->assertSame('250.00', $advance->fresh()->getRemainingBalance());
    }
}
