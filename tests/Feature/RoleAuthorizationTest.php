<?php

namespace Tests\Feature;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\Enums\PenaltyStatus;
use App\Enums\UserRole;
use App\Models\BudgetItem;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contract\ContractService;
use App\Services\Penalty\PenaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase {
    use RefreshDatabase;

    private User $executor1;

    private User $executor2;

    private User $supervisor;

    private User $accountant;

    private ContractService $contractService;

    private PenaltyService $penaltyService;

    private Contract $contract1;

    protected function setUp(): void {
        parent::setUp();

        $this->executor1 = User::factory()->create(['role' => UserRole::Executor]);
        $this->executor2 = User::factory()->create(['role' => UserRole::Executor]);
        $this->supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $this->accountant = User::factory()->create(['role' => UserRole::Accountant]);

        $this->contractService = app(ContractService::class);
        $this->penaltyService = app(PenaltyService::class);

        $this->contract1 = $this->contractService->createContract(new CreateContractDTO(
            contractNumber: 'AUTH-TEST-001',
            supplierName: 'Supplier 1',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(
                    itemName: 'Tovar 1',
                    paidQuantity: 100,
                    bonusQuantity: 0,
                    unitPrice: '10.00'
                ),
            ],
            createdBy: $this->executor1->id
        ));
    }

    public function test_executor_cannot_view_another_executors_contract_via_api(): void {
        Sanctum::actingAs($this->executor2);

        $response = $this->getJson('/api/contracts/'.$this->contract1->id);

        $response->assertStatus(403);
    }

    public function test_supervisor_and_accountant_can_view_any_contract(): void {
        Sanctum::actingAs($this->supervisor);
        $this->getJson('/api/contracts/'.$this->contract1->id)->assertOk();

        Sanctum::actingAs($this->accountant);
        $this->getJson('/api/contracts/'.$this->contract1->id)->assertOk();
    }

    public function test_executor_cannot_approve_or_review_penalty(): void {
        $this->contractService->activateContract($this->contract1);
        $penalty = $this->penaltyService->calculateAndSave($this->contract1, '2026-03-15');

        Sanctum::actingAs($this->executor1);

        $response = $this->postJson('/api/penalties/'.$penalty->id.'/review', [
            'status' => PenaltyStatus::Approved->value,
            'approved_amount' => '30.00',
        ]);

        $response->assertStatus(403);
    }

    public function test_executor_cannot_record_payment(): void {
        $this->contractService->activateContract($this->contract1);
        $milestone = $this->contract1->paymentMilestones->first();

        $budget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '5000.00',
        ]);

        Sanctum::actingAs($this->executor1);

        $response = $this->postJson('/api/payments', [
            'contract_id' => $this->contract1->id,
            'milestone_id' => $milestone->id,
            'budget_item_id' => $budget->id,
            'amount' => '100.00',
            'payment_date' => '2026-03-05',
            'payment_number' => 'PAY-ILLEGAL',
        ]);

        $response->assertStatus(403);
    }

    public function test_accountant_cannot_activate_contract_or_record_delivery(): void {
        Sanctum::actingAs($this->accountant);

        $activateResponse = $this->postJson('/api/contracts/'.$this->contract1->id.'/activate');
        $activateResponse->assertStatus(403);

        $deliveryResponse = $this->postJson('/api/contracts/'.$this->contract1->id.'/delivery-batches', [
            'delivery_date' => '2026-03-05',
            'items' => [
                [
                    'contract_item_id' => $this->contract1->items->first()->id,
                    'delivered_paid_quantity' => 10,
                ],
            ],
        ]);
        $deliveryResponse->assertStatus(403);
    }

    public function test_only_accountant_can_void_payment(): void {
        $this->contractService->activateContract($this->contract1);
        $milestone = $this->contract1->paymentMilestones->first();

        $budget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '5000.00',
        ]);

        Sanctum::actingAs($this->accountant);
        $createResponse = $this->postJson('/api/payments', [
            'contract_id' => $this->contract1->id,
            'milestone_id' => $milestone->id,
            'budget_item_id' => $budget->id,
            'amount' => '100.00',
            'payment_date' => '2026-03-05',
            'payment_number' => 'PAY-TEST-VOID',
        ]);
        $createResponse->assertCreated();
        $paymentId = $createResponse->json('payment.id');

        Sanctum::actingAs($this->supervisor);
        $this->postJson('/api/payments/'.$paymentId.'/void', [
            'void_reason' => 'Supervisor void attempt',
        ])->assertStatus(403);

        Sanctum::actingAs($this->executor1);
        $this->postJson('/api/payments/'.$paymentId.'/void', [
            'void_reason' => 'Executor void attempt',
        ])->assertStatus(403);

        Sanctum::actingAs($this->accountant);
        $this->postJson('/api/payments/'.$paymentId.'/void', [
            'void_reason' => 'Valid accountant voiding',
        ])->assertOk();
    }
}
