<?php

namespace App\Enums;

enum MilestoneCondition: string {
    case ContractActivated = 'contract_activated';
    case GoodsFullyDelivered = 'goods_fully_delivered';

    public function label(): string {
        return match ($this) {
            self::ContractActivated => 'Shartnoma faollashgandan keyin',
            self::GoodsFullyDelivered => 'Pullik tovar to‘liq kelgandan keyin',
        };
    }
}
