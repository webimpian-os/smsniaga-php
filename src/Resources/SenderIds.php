<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Resources;

use Webimpian\SmsNiaga\Data\SenderId;

final class SenderIds extends ApiResource
{
    public function all(): array
    {
        return array_map(
            static fn (array $row): SenderId => SenderId::fromArray($row),
            $this->rows($this->client->get('/api/sender_identifiers')),
        );
    }

    public function find(string $uuid): SenderId
    {
        return SenderId::fromArray($this->data($this->client->get($this->path($uuid))));
    }

    public function findByName(string $name): ?SenderId
    {
        foreach ($this->all() as $senderId) {
            if (strcasecmp($senderId->name, trim($name)) === 0) {
                return $senderId;
            }
        }

        return null;
    }

    public function default(): ?SenderId
    {
        foreach ($this->all() as $senderId) {
            if ($senderId->isDefault) {
                return $senderId;
            }
        }

        return null;
    }

    public function create(string $name, ?string $description = null, bool $isDefault = false): SenderId
    {
        return SenderId::fromArray($this->data($this->client->post('/api/sender_identifiers', [
            'name' => $name,
            'is_default' => $isDefault,
            'description' => $description ?? $name,
        ])));
    }

    public function update(string $uuid, ?string $name = null, ?string $description = null, ?bool $isDefault = null): SenderId
    {
        if ($name === null || $description === null || $isDefault === null) {
            $current = $this->find($uuid);
            $name ??= $current->name;
            $description ??= $current->description;
            $isDefault ??= $current->isDefault;
        }

        return SenderId::fromArray($this->data($this->client->put($this->path($uuid), [
            'name' => $name,
            'is_default' => $isDefault,
            'description' => $description,
        ])));
    }

    public function setDefault(string $uuid): SenderId
    {
        return $this->update($uuid, isDefault: true);
    }

    public function delete(string $uuid): SenderId
    {
        return SenderId::fromArray($this->data($this->client->delete($this->path($uuid))));
    }

    private function path(string $uuid): string
    {
        return '/api/sender_identifiers/' . $this->segment($uuid);
    }
}
