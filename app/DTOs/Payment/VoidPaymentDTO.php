<?php

namespace App\DTOs\Payment;

class VoidPaymentDTO {
    public function __construct(
        public readonly int $paymentId,
        public readonly string $voidReason,
        public readonly int $voidedBy,
    ) {}

    public static function fromArray(array $data, int $paymentId, int $userId): self {
        return new self(
            paymentId: $paymentId,
            voidReason: (string) $data['void_reason'],
            voidedBy: $userId,
        );
    }
}
