<?php

namespace App\Services\Delivery;

use App\DTOs\Delivery\CreateDeliveryBatchDTO;
use App\Exceptions\DeliveryBatchValidationException;
use App\Models\DeliveryBatch;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Delivery\DeliveryBatchRepositoryInterface;
use App\Services\Contract\ContractService;
use Illuminate\Support\Facades\DB;

class DeliveryService {
    public function __construct(
        protected ContractRepositoryInterface $contractRepository,
        protected DeliveryBatchRepositoryInterface $deliveryBatchRepository,
        protected ContractService $contractService,
    ) {}

    public function recordBatch(CreateDeliveryBatchDTO $dto): DeliveryBatch {
        $contract = $this->contractRepository->findWithDetails($dto->contractId);

        if (! $contract) {
            throw new DeliveryBatchValidationException('Shartnoma topilmadi.');
        }

        if (! $contract->isActive()) {
            throw new DeliveryBatchValidationException('Faqat faol shartnoma bo‘yicha partiya qabul qilish mumkin.');
        }

        if (count($dto->items) === 0) {
            throw new DeliveryBatchValidationException('Partiyada kamida bitta tovar bo‘lishi kerak.');
        }

        return DB::transaction(function () use ($contract, $dto): DeliveryBatch {
            $contractItemsById = $contract->items->keyBy('id');
            $hasDeliveredAnyItem = false;

            foreach ($dto->items as $itemDto) {
                $contractItem = $contractItemsById->get($itemDto->contractItemId);

                if (! $contractItem) {
                    throw new DeliveryBatchValidationException('Ko‘rsatilgan tovar qatori shartnomaga tegishli emas.');
                }

                if ($itemDto->deliveredPaidQuantity < 0 || $itemDto->deliveredBonusQuantity < 0) {
                    throw new DeliveryBatchValidationException('Yetkazilgan tovar soni manfiy bo‘lishi mumkin emas.');
                }

                $remainingPaid = $contractItem->remaining_paid_quantity;
                $remainingBonus = $contractItem->remaining_bonus_quantity;

                if ($itemDto->deliveredPaidQuantity > $remainingPaid) {
                    throw new DeliveryBatchValidationException(
                        sprintf(
                            '"%s" uchun kiritilgan pullik miqdor (%d) qoldiqdan (%d) ko‘p.',
                            $contractItem->item_name,
                            $itemDto->deliveredPaidQuantity,
                            $remainingPaid
                        )
                    );
                }

                if ($itemDto->deliveredBonusQuantity > $remainingBonus) {
                    throw new DeliveryBatchValidationException(
                        sprintf(
                            '"%s" uchun kiritilgan bonus miqdor (%d) qoldiqdan (%d) ko‘p.',
                            $contractItem->item_name,
                            $itemDto->deliveredBonusQuantity,
                            $remainingBonus
                        )
                    );
                }

                if ($itemDto->deliveredPaidQuantity > 0 || $itemDto->deliveredBonusQuantity > 0) {
                    $hasDeliveredAnyItem = true;
                }
            }

            if (! $hasDeliveredAnyItem) {
                throw new DeliveryBatchValidationException('Partiyada kamida bitta tovar miqdori noldan katta bo‘lishi kerak.');
            }

            $batch = $this->deliveryBatchRepository->create([
                'contract_id' => $contract->id,
                'delivery_date' => $dto->deliveryDate,
                'notes' => $dto->notes,
                'created_by' => $dto->createdBy,
            ]);

            foreach ($dto->items as $itemDto) {
                if ($itemDto->deliveredPaidQuantity > 0 || $itemDto->deliveredBonusQuantity > 0) {
                    $batch->items()->create([
                        'contract_item_id' => $itemDto->contractItemId,
                        'delivered_paid_quantity' => $itemDto->deliveredPaidQuantity,
                        'delivered_bonus_quantity' => $itemDto->deliveredBonusQuantity,
                    ]);
                }
            }

            $refreshedContract = $this->contractRepository->findWithDetails($contract->id);
            $this->contractService->checkAndCompleteContract($refreshedContract);

            return $batch->fresh(['items.contractItem', 'creator']);
        });
    }
}
