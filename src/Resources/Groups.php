<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Resources;

use Webimpian\SmsNiaga\Data\Group;

final class Groups extends ApiResource
{
    public function all(): array
    {
        return array_map(
            static fn (array $row): Group => Group::fromArray($row),
            $this->rows($this->client->get('/api/groups')),
        );
    }

    public function find(string $uuid): Group
    {
        return Group::fromArray($this->data($this->client->get($this->path($uuid))));
    }

    public function findByName(string $name): ?Group
    {
        foreach ($this->all() as $group) {
            if (strcasecmp($group->name, trim($name)) === 0) {
                return $group;
            }
        }

        return null;
    }

    public function create(string $name): Group
    {
        return Group::fromArray($this->data($this->client->post('/api/groups', ['name' => $name])));
    }

    public function firstOrCreate(string $name): Group
    {
        return $this->findByName($name) ?? $this->create($name);
    }

    public function update(string $uuid, string $name): Group
    {
        return Group::fromArray($this->data($this->client->put($this->path($uuid), ['name' => $name])));
    }

    public function delete(string $uuid): Group
    {
        return Group::fromArray($this->data($this->client->delete($this->path($uuid))));
    }

    private function path(string $uuid): string
    {
        return '/api/groups/' . $this->segment($uuid);
    }
}
