<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use DateTimeImmutable;
use Webimpian\SmsNiaga\Support\Timestamp;

final class SenderId
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $description,
        public bool $isDefault,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: (string) ($data['uuid'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            isDefault: filter_var($data['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
            createdAt: Timestamp::parse($data['created_at'] ?? null),
            updatedAt: Timestamp::parse($data['updated_at'] ?? null),
        );
    }
}
