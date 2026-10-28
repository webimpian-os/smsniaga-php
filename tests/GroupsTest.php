<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

final class GroupsTest extends TestCase
{
    public function test_it_lists_and_finds_groups(): void
    {
        $sms = $this->client(
            $this->json($this->fixture('groups')),
            $this->json($this->fixture('group')),
            $this->json($this->fixture('groups')),
        );

        $groups = $sms->groups()->all();
        $this->assertSame('/api/groups', $this->request()->getUri()->getPath());
        $this->assertSame(['INSTANT-SMS', 'WEB-API-GROUP'], array_map(static fn ($g) => $g->name, $groups));

        $this->assertSame('INSTANT-SMS', $sms->groups()->find('a2476995-5028-4b43-9efd-5373dfb2ebf6')->name);
        $this->assertSame('/api/groups/a2476995-5028-4b43-9efd-5373dfb2ebf6', $this->request(1)->getUri()->getPath());

        $this->assertSame('22bb6a8f-dbb4-4e22-bb7c-8f6ce5fe51d6', $sms->groups()->findByName('web-api-group')?->uuid);
    }

    public function test_first_or_create_reuses_an_existing_group(): void
    {
        $sms = $this->client($this->json($this->fixture('groups')));

        $this->assertSame('a2476995-5028-4b43-9efd-5373dfb2ebf6', $sms->groups()->firstOrCreate('Instant-SMS')->uuid);
        $this->assertCount(1, $this->history);
    }

    public function test_first_or_create_creates_a_missing_group(): void
    {
        $sms = $this->client($this->json($this->fixture('groups')), $this->json($this->fixture('group')));

        $sms->groups()->firstOrCreate('Newsletter');

        $this->assertSame('POST', $this->request(1)->getMethod());
        $this->assertSame(['name' => 'Newsletter'], $this->requestBody(1));
    }

    public function test_it_updates_and_deletes_a_group(): void
    {
        $sms = $this->client($this->json($this->fixture('group')), $this->json($this->fixture('group')));

        $sms->groups()->update('a2476995-5028-4b43-9efd-5373dfb2ebf6', 'Renamed');
        $this->assertSame('PUT', $this->request(0)->getMethod());
        $this->assertSame(['name' => 'Renamed'], $this->requestBody(0));

        $sms->groups()->delete('a2476995-5028-4b43-9efd-5373dfb2ebf6');
        $this->assertSame('DELETE', $this->request(1)->getMethod());
    }
}
