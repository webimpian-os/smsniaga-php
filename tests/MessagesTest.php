<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Webimpian\SmsNiaga\Exceptions\AuthenticationException;
use Webimpian\SmsNiaga\Exceptions\ConnectionException;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\Exceptions\ValidationException;

final class MessagesTest extends TestCase
{
    public function test_it_sends_an_sms(): void
    {
        $result = $this->client($this->json($this->fixture('send')))
            ->messages()
            ->send(['0123456789', '+60 19-876 5432'], 'Your order has shipped.', 'MYSHOP');

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('application/json', $this->request()->getHeaderLine('Content-Type'));
        $this->assertSame([
            'body' => 'Your order has shipped.',
            'phones' => ['60123456789', '60198765432'],
            'preview' => 0,
            'sender_id' => 'MYSHOP',
        ], $this->requestBody());

        $this->assertSame('3c12b1d1-d255-4f2f-88d4-a18ee29852ca', $result->uuid);
        $this->assertSame(['60137654321', '60145551234', '60111234567'], $result->numbers);
        $this->assertSame(3, $result->totalNumbers);
        $this->assertSame(0.3, $result->totalCharge);
        $this->assertSame(1, $result->messagePages);
        $this->assertSame(38, $result->charCount);
        $this->assertSame('RM0 MYSHOP: Your message located here', $result->message);
        $this->assertSame('MYSHOP', $result->senderId?->name);
        $this->assertSame(36.8, $result->balanceAfter);
        $this->assertFalse($result->preview);
    }

    public function test_it_leaves_out_the_sender_id_to_use_the_account_default(): void
    {
        $this->client($this->json($this->fixture('send')))->messages()->send('0123456789', 'Hi', '  ');

        $this->assertArrayNotHasKey('sender_id', $this->requestBody());
    }

    public function test_it_accepts_a_separated_string_and_drops_duplicates(): void
    {
        $this->client($this->json($this->fixture('send')))
            ->messages()
            ->send("0123456789, 60123456789;\n0198765432", 'Hi');

        $this->assertSame(['60123456789', '60198765432'], $this->requestBody()['phones']);
    }

    public function test_preview_does_not_send(): void
    {
        $result = $this->client($this->json($this->fixture('send')))->messages()->preview('0123456789', 'Hi');

        $this->assertSame(1, $this->requestBody()['preview']);
        $this->assertTrue($result->preview);
    }

    public function test_it_rejects_an_empty_message(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->messages()->send('0123456789', '   ');
    }

    public function test_it_rejects_no_recipients(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->messages()->send([], 'Hi');
    }

    public function test_it_rejects_more_than_fifty_numbers_per_request(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Use sendBulk()');

        $this->client()->messages()->send($this->numbers(51), 'Hi');
    }

    public function test_send_bulk_splits_into_batches_of_fifty(): void
    {
        $sms = $this->client(
            $this->json($this->fixture('send')),
            $this->json($this->fixture('send')),
            $this->json($this->fixture('send')),
        );

        $result = $sms->messages()->sendBulk($this->numbers(120), 'Promo');

        $this->assertCount(3, $this->history);
        $this->assertCount(50, $this->requestBody(0)['phones']);
        $this->assertCount(50, $this->requestBody(1)['phones']);
        $this->assertCount(20, $this->requestBody(2)['phones']);
        $this->assertCount(3, $result->results);
        $this->assertFalse($result->hasFailures());
        $this->assertSame(9, $result->totalNumbers());
        $this->assertSame(0.9, $result->totalCharge());
        $this->assertCount(3, $result->uuids());
    }

    public function test_send_bulk_collects_failures_after_the_first_batch_succeeds(): void
    {
        $sms = $this->client(
            $this->json($this->fixture('send')),
            $this->json(['message' => 'Insufficient balance.', 'status_code' => 400]),
            new ConnectException('Timed out', new Request('POST', '/api/send')),
        );

        $result = $sms->messages()->sendBulk($this->numbers(120), 'Promo');

        $this->assertCount(1, $result->results);
        $this->assertTrue($result->hasFailures());
        $this->assertCount(2, $result->failures);
        $this->assertInstanceOf(ValidationException::class, $result->failures[0]->exception);
        $this->assertInstanceOf(ConnectionException::class, $result->failures[1]->exception);
        $this->assertSame(array_slice($this->numbers(120), 50), $result->failedNumbers());
    }

    public function test_send_bulk_throws_when_the_first_batch_fails(): void
    {
        $sms = $this->client($this->json(['message' => 'Unauthenticated', 'status_code' => 403]));

        $this->expectException(AuthenticationException::class);

        $sms->messages()->sendBulk($this->numbers(120), 'Promo');
    }

    public function test_it_fetches_a_delivery_report(): void
    {
        $report = $this->client($this->json($this->fixture('delivery-report')))
            ->messages()
            ->report('15f6e6f4-45e1-4478-94f5-f48ec6457397');

        $request = $this->request();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/message', $request->getUri()->getPath());
        $this->assertSame('uuid=15f6e6f4-45e1-4478-94f5-f48ec6457397', $request->getUri()->getQuery());
        $this->assertSame('', (string) $request->getBody());

        $this->assertSame('15f6e6f4-45e1-4478-94f5-f48ec6457397', $report->uuid);
        $this->assertSame('RM0 MYSHOP: Test 123', $report->body);
        $this->assertSame(0.1, $report->debit);
        $this->assertSame('MYSHOP', $report->senderId?->name);
        $this->assertSame(1, $report->total);
        $this->assertSame(1, $report->delivered);
        $this->assertTrue($report->isFullyDelivered());
        $this->assertTrue($report->isComplete());
        $this->assertSame('2020-12-27T04:36:46+00:00', $report->createdAt?->format(DATE_ATOM));

        $recipient = $report->recipient('012-345 6789');
        $this->assertNotNull($recipient);
        $this->assertTrue($recipient->isDelivered());
        $this->assertSame('000', $recipient->code);
        $this->assertSame('OK. No error encountered.', $recipient->description());
        $this->assertSame('2020-12-27T12:36:53+08:00', $recipient->receivedAt?->format(DATE_ATOM));
    }

    public function test_delivery_report_groups_recipients_by_outcome(): void
    {
        $fixture = $this->fixture('delivery-report');
        $fixture['stats'] = [
            ['phone' => '60111111111', 'delivery_report_code' => '000'],
            ['phone' => '60122222222', 'delivery_report_code' => '111'],
            ['phone' => '60133333333', 'delivery_report_code' => null],
            ['phone' => '60144444444', 'delivery_report_code' => '001'],
        ];

        $report = $this->client($this->json($fixture))->messages()->report('abc');

        $this->assertCount(1, $report->deliveredRecipients());
        $this->assertCount(2, $report->pending());
        $this->assertCount(1, $report->failed());
        $this->assertSame('Absent subscriber.', $report->failed()[0]->description());
        $this->assertFalse($report->isFullyDelivered());
        $this->assertFalse($report->isComplete());
    }

    private function numbers(int $count): array
    {
        return array_map(static fn (int $i): string => sprintf('6012%07d', $i), range(1, $count));
    }
}
