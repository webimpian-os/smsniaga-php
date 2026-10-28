<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use DateTimeImmutable;
use Webimpian\SmsNiaga\Support\DeliveryReportCode;
use Webimpian\SmsNiaga\Support\Timestamp;

final class RecipientStatus
{
    public function __construct(
        public string $phone,
        public ?string $code,
        public ?DateTimeImmutable $receivedAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $code = $data['delivery_report_code'] ?? null;

        return new self(
            phone: (string) ($data['phone'] ?? ''),
            code: DeliveryReportCode::normalize(is_string($code) || is_int($code) ? $code : null),
            receivedAt: Timestamp::parse($data['delivery_report_received'] ?? null),
        );
    }

    public function isDelivered(): bool
    {
        return DeliveryReportCode::isDelivered($this->code);
    }

    public function isPending(): bool
    {
        return DeliveryReportCode::isPending($this->code);
    }

    public function isFailed(): bool
    {
        return DeliveryReportCode::isFailed($this->code);
    }

    public function description(): string
    {
        return DeliveryReportCode::describe($this->code);
    }
}
