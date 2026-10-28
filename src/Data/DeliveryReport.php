<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use DateTimeImmutable;
use Webimpian\SmsNiaga\Support\Amount;
use Webimpian\SmsNiaga\Support\PhoneNumber;
use Webimpian\SmsNiaga\Support\Timestamp;

final class DeliveryReport
{
    public function __construct(
        public string $uuid,
        public string $body,
        public float $debit,
        public ?SenderId $senderId,
        public int $total,
        public int $delivered,
        public array $recipients,
        public ?DateTimeImmutable $createdAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $message = is_array($data['message'] ?? null) ? $data['message'] : [];
        $count = is_array($data['count'] ?? null) ? $data['count'] : [];
        $stats = is_array($data['stats'] ?? null) ? array_filter($data['stats'], 'is_array') : [];
        $recipients = array_values(array_map(static fn (array $row): RecipientStatus => RecipientStatus::fromArray($row), $stats));

        return new self(
            uuid: (string) ($message['uuid'] ?? ''),
            body: (string) ($message['body'] ?? ''),
            debit: Amount::fromUnits($message['debit'] ?? 0),
            senderId: is_array($data['sender_id'] ?? null) ? SenderId::fromArray($data['sender_id']) : null,
            total: (int) ($count['total'] ?? count($recipients)),
            delivered: (int) ($count['delivered'] ?? 0),
            recipients: $recipients,
            createdAt: Timestamp::parse($message['created_at'] ?? null),
        );
    }

    public function isFullyDelivered(): bool
    {
        return $this->recipients !== [] && $this->failed() === [] && $this->pending() === [];
    }

    public function isComplete(): bool
    {
        return $this->pending() === [];
    }

    public function deliveredRecipients(): array
    {
        return array_values(array_filter($this->recipients, static fn (RecipientStatus $r): bool => $r->isDelivered()));
    }

    public function pending(): array
    {
        return array_values(array_filter($this->recipients, static fn (RecipientStatus $r): bool => $r->isPending()));
    }

    public function failed(): array
    {
        return array_values(array_filter($this->recipients, static fn (RecipientStatus $r): bool => $r->isFailed()));
    }

    public function recipient(string $phone): ?RecipientStatus
    {
        $phone = PhoneNumber::normalize($phone);

        foreach ($this->recipients as $recipient) {
            if ($recipient->phone === $phone) {
                return $recipient;
            }
        }

        return null;
    }
}
