<?php

namespace Database\Seeders;

use App\DTOs\Contract\ContractItemDTO;
use App\DTOs\Contract\CreateContractDTO;
use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\DTOs\Delivery\DeliveryBatchItemDTO;
use App\DTOs\Payment\CreatePaymentDTO;
use App\Enums\UserRole;
use App\Models\BudgetItem;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $executor = User::create([
            'name' => 'Alisher Zokirov',
            'email' => 'ijrochi@davlat.uz',
            'password' => Hash::make('password'),
            'role' => UserRole::Executor,
        ]);

        $supervisor = User::create([
            'name' => 'Botir Qodirov',
            'email' => 'boshliq@davlat.uz',
            'password' => Hash::make('password'),
            'role' => UserRole::Supervisor,
        ]);

        $accountant = User::create([
            'name' => 'Dilshod Umarov',
            'email' => 'buxgalter@davlat.uz',
            'password' => Hash::make('password'),
            'role' => UserRole::Accountant,
        ]);

        $marchBudget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 3,
            'allocated_amount' => '3000.00',
            'notes' => 'Demo ma’lumot (seeder)',
        ]);

        $aprilBudget = BudgetItem::create([
            'financial_year' => 2026,
            'cost_item' => 'Dori vositalari',
            'month' => 4,
            'allocated_amount' => '2000.00',
            'notes' => 'Aprel oyi rejasi',
        ]);

        $contractService = app(ContractService::class);
        $deliveryService = app(DeliveryService::class);
        $accountingService = app(AccountingService::class);

        $contract1 = $contractService->createContract(new CreateContractDTO(
            contractNumber: 'SH-2026-001',
            supplierName: 'Farm Med Servis MCHJ',
            startDate: '2026-03-02',
            durationDays: 10,
            items: [
                new ContractItemDTO(
                    itemName: 'Dori vositalari to‘plami (Paratsetamol)',
                    paidQuantity: 1000,
                    bonusQuantity: 50,
                    unitPrice: '2.50'
                ),
            ],
            createdBy: $executor->id
        ));
        $contractService->activateContract($contract1);

        $contract2 = $contractService->createContract(new CreateContractDTO(
            contractNumber: 'SH-2026-002',
            supplierName: 'Global Medical Goods MCHJ',
            startDate: '2026-03-15',
            durationDays: 15,
            items: [
                new ContractItemDTO(
                    itemName: 'Tibbiy bint va steril paxta to‘plami',
                    paidQuantity: 500,
                    bonusQuantity: 20,
                    unitPrice: '1.50'
                ),
            ],
            createdBy: $executor->id
        ));

        $contract3 = $contractService->createContract(new CreateContractDTO(
            contractNumber: 'SH-2026-003',
            supplierName: 'BioTex Diagnostics MCHJ',
            startDate: '2026-03-01',
            durationDays: 5,
            items: [
                new ContractItemDTO(
                    itemName: 'Bir martalik shpritslar 5ml',
                    paidQuantity: 800,
                    bonusQuantity: 100,
                    unitPrice: '2.00'
                ),
            ],
            createdBy: $executor->id
        ));
        $contractService->activateContract($contract3);

        $contract3Item = $contract3->items->first();
        $deliveryService->recordBatch(new CreateDeliveryBatchDTO(
            contractId: $contract3->id,
            deliveryDate: '2026-03-05',
            notes: 'To‘liq partiya yetkazildi',
            items: [
                new DeliveryBatchItemDTO(
                    contractItemId: $contract3Item->id,
                    deliveredPaidQuantity: 800,
                    deliveredBonusQuantity: 100
                ),
            ],
            createdBy: $executor->id
        ));

        $advanceMilestone3 = $contract3->paymentMilestones->firstWhere('step_order', 1);
        $accountingService->recordPayment(new CreatePaymentDTO(
            contractId: $contract3->id,
            milestoneId: $advanceMilestone3->id,
            budgetItemId: $marchBudget->id,
            amount: '480.00',
            paymentDate: '2026-03-02',
            paymentNumber: 'TO-2026-0001',
            comment: 'Avans to‘lovi (30%)',
            createdBy: $accountant->id
        ));
    }
}
