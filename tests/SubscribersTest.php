<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

final class SubscribersTest extends TestCase
{
    public function test_it_finds_a_subscriber_with_their_groups(): void
    {
        $subscriber = $this->client($this->json($this->fixture('subscriber')))
            ->subscribers()
            ->find('602f1e80-e71c-47be-a579-f7f023841724');

        $this->assertSame('GET', $this->request()->getMethod());
        $this->assertSame('/api/subscribers/602f1e80-e71c-47be-a579-f7f023841724', $this->request()->getUri()->getPath());
        $this->assertSame('Aisyah', $subscriber->name);
        $this->assertSame('aisyah@example.com', $subscriber->email);
        $this->assertSame('60123456789', $subscriber->phone);
        $this->assertSame('Newsletter', $subscriber->groups[0]->name);
        $this->assertTrue($subscriber->inGroup('newsletter'));
        $this->assertTrue($subscriber->inGroup('4dc35488-1523-4581-8b43-8b7e24dc75fa'));
        $this->assertFalse($subscriber->inGroup('VIP Customers'));
    }

    public function test_it_creates_a_subscriber(): void
    {
        $subscriber = $this->client($this->json($this->fixture('subscriber')))
            ->subscribers()
            ->create('012-345 6789', 'Aisyah', groupUuid: '4dc35488-1523-4581-8b43-8b7e24dc75fa');

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame([
            'phone' => '60123456789',
            'name' => 'Aisyah',
            'group_uuid' => '4dc35488-1523-4581-8b43-8b7e24dc75fa',
        ], $this->requestBody());
        $this->assertSame('602f1e80-e71c-47be-a579-f7f023841724', $subscriber->uuid);
    }

    public function test_create_leaves_out_blank_fields(): void
    {
        $this->client($this->json($this->fixture('subscriber')))->subscribers()->create('0123456789', 'Aisyah', ' ');

        $this->assertSame(['phone' => '60123456789', 'name' => 'Aisyah'], $this->requestBody());
    }

    public function test_update_fills_missing_fields_from_the_current_record(): void
    {
        $sms = $this->client($this->json($this->fixture('subscriber')), $this->json($this->fixture('subscriber')));

        $sms->subscribers()->update('602f1e80-e71c-47be-a579-f7f023841724', name: 'Aisyah Rahman');

        $this->assertSame('GET', $this->request(0)->getMethod());
        $this->assertSame('PUT', $this->request(1)->getMethod());
        $this->assertSame('/api/subscribers/602f1e80-e71c-47be-a579-f7f023841724', $this->request(1)->getUri()->getPath());
        $this->assertSame([
            'name' => 'Aisyah Rahman',
            'email' => 'aisyah@example.com',
            'phone' => '60123456789',
        ], $this->requestBody(1));
    }

    public function test_update_leaves_out_blank_fields(): void
    {
        $fixture = $this->fixture('subscriber');
        $fixture['data']['email'] = null;
        $sms = $this->client($this->json($fixture), $this->json($fixture));

        $sms->subscribers()->update('602f1e80-e71c-47be-a579-f7f023841724', name: 'Aisyah Rahman');

        $this->assertSame(['name' => 'Aisyah Rahman', 'phone' => '60123456789'], $this->requestBody(1));
    }

    public function test_it_deletes_a_subscriber(): void
    {
        $subscriber = $this->client($this->json($this->fixture('subscriber')))
            ->subscribers()
            ->delete('602f1e80-e71c-47be-a579-f7f023841724');

        $this->assertSame('DELETE', $this->request()->getMethod());
        $this->assertSame('Aisyah', $subscriber->name);
    }
}
