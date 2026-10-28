<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\SmsNiaga;

final class SmsNiagaTest extends TestCase
{
    public function test_it_requires_a_token(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SmsNiaga('   ');
    }

    public function test_every_request_is_authenticated_json_to_the_live_api(): void
    {
        $this->client($this->json($this->fixture('balance')))->balance();

        $request = $this->request();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://manage.smsniaga.com/api/balance', (string) $request->getUri());
        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertStringStartsWith('smsniaga-php/' . SmsNiaga::VERSION, $request->getHeaderLine('User-Agent'));
    }

    public function test_it_reads_the_balance_in_ringgit(): void
    {
        $balance = $this->client($this->json($this->fixture('balance')))->balance();

        $this->assertSame(36.8, $balance->amount);
        $this->assertSame('RM36.80', $balance->formatted);
        $this->assertSame('MYR', $balance->currency);
        $this->assertSame('RM36.80', (string) $balance);
        $this->assertTrue($balance->isBelow(50));
        $this->assertFalse($balance->isBelow(10));
    }

    public function test_send_shortcut_uses_the_messages_resource(): void
    {
        $result = $this->client($this->json($this->fixture('send')))->send('012-345 6789', 'Hello');

        $this->assertSame('/api/send', $this->request()->getUri()->getPath());
        $this->assertSame(['60123456789'], $this->requestBody()['phones']);
        $this->assertSame('3c12b1d1-d255-4f2f-88d4-a18ee29852ca', $result->uuid);
    }

    public function test_resources_are_reused(): void
    {
        $sms = new SmsNiaga('test-token');

        $this->assertSame($sms->messages(), $sms->messages());
        $this->assertSame($sms->senderIds(), $sms->senderIds());
        $this->assertSame($sms->subscribers(), $sms->subscribers());
        $this->assertSame($sms->groups(), $sms->groups());
    }
}
