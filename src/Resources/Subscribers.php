<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Resources;

use Webimpian\SmsNiaga\Data\Subscriber;
use Webimpian\SmsNiaga\Support\PhoneNumber;

final class Subscribers extends ApiResource
{
    // No all(): GET /api/subscribers returns every subscriber unpaginated and times out on real accounts.
    public function find(string $uuid): Subscriber
    {
        return Subscriber::fromArray($this->data($this->client->get($this->path($uuid))));
    }

    public function create(string $phone, ?string $name = null, ?string $email = null, ?string $groupUuid = null): Subscriber
    {
        $payload = $this->withoutBlanks([
            'phone' => PhoneNumber::normalize($phone),
            'name' => $name,
            'email' => $email,
            'group_uuid' => $groupUuid,
        ]);

        return Subscriber::fromArray($this->data($this->client->post('/api/subscribers', $payload)));
    }

    public function update(string $uuid, ?string $phone = null, ?string $name = null, ?string $email = null): Subscriber
    {
        if ($phone === null || $name === null || $email === null) {
            $current = $this->find($uuid);
            $phone ??= $current->phone;
            $name ??= $current->name;
            $email ??= $current->email;
        }

        return Subscriber::fromArray($this->data($this->client->put($this->path($uuid), $this->withoutBlanks([
            'name' => $name,
            'email' => $email,
            'phone' => PhoneNumber::normalize($phone),
        ]))));
    }

    public function delete(string $uuid): Subscriber
    {
        return Subscriber::fromArray($this->data($this->client->delete($this->path($uuid))));
    }

    private function path(string $uuid): string
    {
        return '/api/subscribers/' . $this->segment($uuid);
    }

    private function withoutBlanks(array $payload): array
    {
        return array_filter($payload, static fn (?string $value): bool => $value !== null && trim($value) !== '');
    }
}
