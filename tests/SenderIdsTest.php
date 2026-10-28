<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

use Webimpian\SmsNiaga\Data\SenderId;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\Exceptions\ValidationException;

final class SenderIdsTest extends TestCase
{
    public function test_it_lists_sender_ids(): void
    {
        $senderIds = $this->client($this->json($this->fixture('sender-ids')))->senderIds()->all();

        $this->assertSame('/api/sender_identifiers', $this->request()->getUri()->getPath());
        $this->assertCount(2, $senderIds);
        $this->assertContainsOnlyInstancesOf(SenderId::class, $senderIds);
        $this->assertSame('HR', $senderIds[0]->name);
        $this->assertTrue($senderIds[0]->isDefault);
        $this->assertSame('2020-12-14T21:21:36+00:00', $senderIds[0]->createdAt?->format(DATE_ATOM));
    }

    public function test_it_finds_by_uuid_name_and_default(): void
    {
        $sms = $this->client(
            $this->json($this->fixture('sender-id')),
            $this->json($this->fixture('sender-ids')),
            $this->json($this->fixture('sender-ids')),
            $this->json($this->fixture('sender-ids')),
        );

        $this->assertSame('HR', $sms->senderIds()->find('7eee89eb-d812-47f5-ad51-c022bf9fadb4')->name);
        $this->assertSame('/api/sender_identifiers/7eee89eb-d812-47f5-ad51-c022bf9fadb4', $this->request()->getUri()->getPath());
        $this->assertSame('5cd4a787-6378-4d41-ab08-f4722d38a0c0', $sms->senderIds()->findByName('myshop')?->uuid);
        $this->assertNull($sms->senderIds()->findByName('NOPE'));
        $this->assertSame('HR', $sms->senderIds()->default()?->name);
    }

    public function test_it_creates_a_sender_id_with_the_name_as_default_description(): void
    {
        $this->client($this->json($this->fixture('sender-id')))->senderIds()->create('MYSHOP');

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame(['name' => 'MYSHOP', 'is_default' => false, 'description' => 'MYSHOP'], $this->requestBody());
    }

    public function test_update_fills_missing_fields_from_the_current_record(): void
    {
        $sms = $this->client($this->json($this->fixture('sender-id')), $this->json($this->fixture('sender-id')));

        $sms->senderIds()->update('7eee89eb-d812-47f5-ad51-c022bf9fadb4', description: 'Human resources');

        $this->assertSame('GET', $this->request(0)->getMethod());
        $this->assertSame('PUT', $this->request(1)->getMethod());
        $this->assertSame(['name' => 'HR', 'is_default' => true, 'description' => 'Human resources'], $this->requestBody(1));
    }

    public function test_update_with_every_field_skips_the_lookup(): void
    {
        $this->client($this->json($this->fixture('sender-id')))
            ->senderIds()
            ->update('7eee89eb-d812-47f5-ad51-c022bf9fadb4', 'HR', 'Human resources', false);

        $this->assertCount(1, $this->history);
        $this->assertSame('PUT', $this->request()->getMethod());
    }

    public function test_set_default(): void
    {
        $sms = $this->client($this->json($this->fixture('sender-id')), $this->json($this->fixture('sender-id')));

        $sms->senderIds()->setDefault('7eee89eb-d812-47f5-ad51-c022bf9fadb4');

        $this->assertTrue($this->requestBody(1)['is_default']);
    }

    public function test_delete_returns_the_removed_sender_id_and_surfaces_api_errors(): void
    {
        $sms = $this->client(
            $this->json($this->fixture('sender-id')),
            $this->json(['message' => 'Unable to remove sender ID is being used.', 'status_code' => 400], 400),
        );

        $this->assertSame('HR', $sms->senderIds()->delete('7eee89eb-d812-47f5-ad51-c022bf9fadb4')->name);
        $this->assertSame('DELETE', $this->request()->getMethod());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to remove sender ID is being used.');

        $sms->senderIds()->delete('7eee89eb-d812-47f5-ad51-c022bf9fadb4');
    }

    public function test_it_rejects_an_empty_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->senderIds()->find(' ');
    }
}
