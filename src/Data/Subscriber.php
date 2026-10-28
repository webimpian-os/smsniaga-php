<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use DateTimeImmutable;
use Webimpian\SmsNiaga\Support\Timestamp;

final class Subscriber
{
    public function __construct(
        public string $uuid,
        public string $phone,
        public ?string $name = null,
        public ?string $email = null,
        public array $groups = [],
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $groups = is_array($data['group'] ?? null) ? $data['group'] : [];

        return new self(
            uuid: (string) ($data['uuid'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            groups: array_values(array_map(static fn (array $group): Group => Group::fromArray($group), array_filter($groups, 'is_array'))),
            createdAt: Timestamp::parse($data['created_at'] ?? null),
            updatedAt: Timestamp::parse($data['updated_at'] ?? null),
        );
    }

    public function inGroup(string $uuidOrName): bool
    {
        foreach ($this->groups as $group) {
            if ($group->uuid === $uuidOrName || strcasecmp($group->name, $uuidOrName) === 0) {
                return true;
            }
        }

        return false;
    }
}
