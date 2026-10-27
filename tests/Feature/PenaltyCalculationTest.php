<?php

namespace Tests\Feature;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\DTOs\Delivery\DeliveryBatchItemDTO;
use App\DTOs\Penalty\ReviewPenaltyDTO;
use App\Enums\PenaltyStatus;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use App\Services\Penalty\PenaltyCalculatorService;
use App\Services\Penalty\PenaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenaltyCalculationTest extends TestCase {
    use RefreshDatabase;

    private User $executor;

    private User $supervisor;

    private ContractService $contractService;

    private DeliveryService $deliveryService;

    private PenaltyCalculatorService $calculatorService;

    private PenaltyService $penaltyService;

    protected function setUp(): void {
        parent::setUp();

        $this->executor = User::factory()->create(['role' => UserRole::Executor]);
        $this->supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $this->contractService = app(ContractService::class);
        $this->deliveryService = app(DeliveryService::class);
        $this->calculatorService = app(PenaltyCalculatorService::class);
        $this->penaltyService = app(PenaltyService::class);
    }

    private function createStandardContract(): Contract {
        $contract = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'TEST-SH-001',
            supplierName: 'Test Supplier LLC',
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

        return $this->contractService->activateContract($contract);
    }

    public function test_penalty_calculation_example_one_no_delivery_with_three_days_delay(): void {
        $contract = $this->createStandardContract();

        $result = $this->calculatorService->calculate($contract, '2026-03-15');

        $this->assertSame(3, $result->delayDays);
        $this->assertSame('37.50', $result->calculatedAmount);
        $this->assertSame('1250.00', $result->capAmount);
        $this->assertCount(3, $result->breakdown);
        $this->assertSame('12.50', $result->breakdown[0]['daily_penalty']);
        $this->assertSame('12.50', $result->breakdown[1]['daily_penalty']);
        $this->assertSame('12.50', $result->breakdown[2]['daily_penalty']);
    }

    public function test_penalty_calculation_example_two_with_partial_batch_on_fourteenth_march(): void {
        $contract = $this->createStandardContract();
        $item = $contract->items->first();

        $this->deliveryService->recordBatch(new CreateDeliveryBatchDTO(
            contractId: $contract->id,
            deliveryDate: '2026-03-14',
            notes: '600 items delivered',
            items: [
                new DeliveryBatchItemDTO(
                    contractItemId: $item->id,
                    deliveredPaidQuantity: 600,
                    deliveredBonusQuantity: 0
                ),
            ],
            createdBy: $this->executor->id
        ));

        $result = $this->calculatorService->calculate($contract->fresh(), '2026-03-16');

        $this->assertSame(4, $result->delayDays);
        $this->assertSame('35.00', $result->calculatedAmount);
        $this->assertCount(4, $result->breakdown);
        $this->assertSame('2026-03-13', $result->breakdown[0]['date']);
        $this->assertSame('12.50', $result->breakdown[0]['daily_penalty']);
        $this->assertSame('2026-03-14', $result->breakdown[1]['date']);
        $this->assertSame('12.50', $result->breakdown[1]['daily_penalty']);
        $this->assertSame('2026-03-15', $result->breakdown[2]['date']);
        $this->assertSame('5.00', $result->breakdown[2]['daily_penalty']);
        $this->assertSame('2026-03-16', $result->breakdown[3]['date']);
        $this->assertSame('5.00', $result->breakdown[3]['daily_penalty']);
    }

    public function test_penalty_capped_at_fifty_percent_of_undelivered_amount_at_deadline(): void {
        $contract = $this->createStandardContract();

        $result = $this->calculatorService->calculate($contract, '2026-08-01');

        $this->assertSame('1250.00', $result->capAmount);
        $this->assertSame('1250.00', $result->calculatedAmount);
    }

    public function test_penalty_is_zero_when_calculation_date_is_on_or_before_deadline(): void {
        $contract = $this->createStandardContract();

        $onDeadline = $this->calculatorService->calculate($contract, '2026-03-12');
        $beforeDeadline = $this->calculatorService->calculate($contract, '2026-03-08');

        $this->assertSame(0, $onDeadline->delayDays);
        $this->assertSame('0.00', $onDeadline->calculatedAmount);
        $this->assertSame(0, $beforeDeadline->delayDays);
        $this->assertSame('0.00', $beforeDeadline->calculatedAmount);
    }

    public function test_supervisor_can_approve_penalty_with_calculated_amount(): void {
        $contract = $this->createStandardContract();
        $penalty = $this->penaltyService->calculateAndSave($contract, '2026-03-15');

        $reviewed = $this->penaltyService->reviewPenalty(new ReviewPenaltyDTO(
            penaltyId: $penalty->id,
            status: PenaltyStatus::Approved,
            approvedAmount: null,
            notes: 'Hisoblangan summa to‘g‘ri',
            reviewedBy: $this->supervisor->id
        ));

        $this->assertSame(PenaltyStatus::Approved, $reviewed->status);
        $this->assertSame('37.50', $reviewed->getEffectiveAmount());
        $this->assertSame('37.50', $contract->fresh()->getLatestApprovedPenaltyAmount());
    }

    public function test_supervisor_can_approve_penalty_with_custom_amount_example_d(): void {
        $contract = $this->createStandardContract();
        $penalty = $this->penaltyService->calculateAndSave($contract, '2026-03-15');

        $reviewed = $this->penaltyService->reviewPenalty(new ReviewPenaltyDTO(
            penaltyId: $penalty->id,
            status: PenaltyStatus::Approved,
            approvedAmount: '30.00',
            notes: 'Boshliq buyrug‘i bilan 30.00 etib tasdiqlandi',
            reviewedBy: $this->supervisor->id
        ));

        $this->assertSame(PenaltyStatus::Approved, $reviewed->status);
        $this->assertSame('30.00', $reviewed->getEffectiveAmount());
        $this->assertSame('30.00', $contract->fresh()->getLatestApprovedPenaltyAmount());
    }

    public function test_accounting_sees_zero_penalty_when_penalty_is_pending_or_rejected(): void {
        $contract = $this->createStandardContract();
        $penalty = $this->penaltyService->calculateAndSave($contract, '2026-03-15');

        $this->assertSame('0.00', $contract->fresh()->getLatestApprovedPenaltyAmount());

        $this->penaltyService->reviewPenalty(new ReviewPenaltyDTO(
            penaltyId: $penalty->id,
            status: PenaltyStatus::Rejected,
            approvedAmount: null,
            notes: 'Yengillashtiruvchi holat sababli rad etildi',
            reviewedBy: $this->supervisor->id
        ));

        $this->assertSame('0.00', $contract->fresh()->getLatestApprovedPenaltyAmount());
    }
}
