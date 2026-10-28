<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use DateTimeImmutable;
use Webimpian\SmsNiaga\Support\Timestamp;

final class Group
{
    public const DEFAULT_API_GROUP = 'WEB-API-GROUP';

    public function __construct(
        public string $uuid,
        public string $name,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: (string) ($data['uuid'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            createdAt: Timestamp::parse($data['created_at'] ?? null),
            updatedAt: Timestamp::parse($data['updated_at'] ?? null),
        );
    }
}
