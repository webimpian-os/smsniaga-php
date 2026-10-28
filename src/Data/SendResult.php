<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use Webimpian\SmsNiaga\Support\Amount;

final class SendResult
{
    public function __construct(
        public ?string $uuid,
        public array $numbers,
        public int $totalNumbers,
        public float $totalCharge,
        public int $messagePages,
        public int $charCount,
        public string $message,
        public ?SenderId $senderId,
        public ?float $balanceAfter,
        public array $groups = [],
        public bool $preview = false,
    ) {
    }

    public static function fromArray(array $data, bool $preview = false): self
    {
        $numbers = array_values(array_map('strval', is_array($data['numbers'] ?? null) ? $data['numbers'] : []));

        return new self(
            uuid: isset($data['uuid']) ? (string) $data['uuid'] : null,
            numbers: $numbers,
            totalNumbers: (int) ($data['total_numbers'] ?? count($numbers)),
            totalCharge: Amount::fromUnits($data['total_charge'] ?? 0),
            messagePages: (int) ($data['message_page'] ?? 0),
            charCount: (int) ($data['char_counts'] ?? 0),
            message: (string) ($data['message'] ?? ''),
            senderId: is_array($data['sender_id'] ?? null) ? SenderId::fromArray($data['sender_id']) : null,
            balanceAfter: Amount::fromRinggit($data['credit_balance_after'] ?? null),
            groups: is_array($data['groups'] ?? null) ? $data['groups'] : [],
            preview: $preview,
        );
    }
}
