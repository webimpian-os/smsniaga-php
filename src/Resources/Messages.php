<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Resources;

use Webimpian\SmsNiaga\Data\BulkSendResult;
use Webimpian\SmsNiaga\Data\DeliveryReport;
use Webimpian\SmsNiaga\Data\FailedBatch;
use Webimpian\SmsNiaga\Data\SendResult;
use Webimpian\SmsNiaga\Exceptions\ApiException;
use Webimpian\SmsNiaga\Exceptions\ConnectionException;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\Support\PhoneNumber;

final class Messages extends ApiResource
{
    public const MAX_NUMBERS_PER_REQUEST = 50;

    public function send(string|array $to, string $message, ?string $senderId = null): SendResult
    {
        return $this->dispatch(PhoneNumber::normalizeMany($to), $message, $senderId, false);
    }

    public function preview(string|array $to, string $message, ?string $senderId = null): SendResult
    {
        return $this->dispatch(PhoneNumber::normalizeMany($to), $message, $senderId, true);
    }

    public function sendBulk(string|array $to, string $message, ?string $senderId = null): BulkSendResult
    {
        $numbers = PhoneNumber::normalizeMany($to);

        if ($numbers === []) {
            throw new InvalidArgumentException('At least one phone number is required.');
        }

        $results = [];
        $failures = [];

        foreach (array_chunk($numbers, self::MAX_NUMBERS_PER_REQUEST) as $batch) {
            try {
                $results[] = $this->dispatch($batch, $message, $senderId, false);
            } catch (ApiException|ConnectionException $e) {
                // Nothing has gone out yet, so fail loudly; after that, keep going and report what was missed.
                if ($results === []) {
                    throw $e;
                }

                $failures[] = new FailedBatch($batch, $e);
            }
        }

        return new BulkSendResult($results, $failures);
    }

    public function report(string $uuid): DeliveryReport
    {
        $uuid = trim($uuid);

        if ($uuid === '') {
            throw new InvalidArgumentException('A message UUID is required.');
        }

        // The docs send the UUID as a GET body, which proxies may drop; the API reads the query string just as well.
        $response = $this->client->get('/api/message', ['uuid' => $uuid]);

        return DeliveryReport::fromArray(is_array($response['data'] ?? null) ? $response['data'] : $response);
    }

    private function dispatch(array $numbers, string $message, ?string $senderId, bool $preview): SendResult
    {
        if (trim($message) === '') {
            throw new InvalidArgumentException('The message cannot be empty.');
        }

        if ($numbers === []) {
            throw new InvalidArgumentException('At least one phone number is required.');
        }

        if (count($numbers) > self::MAX_NUMBERS_PER_REQUEST) {
            throw new InvalidArgumentException(sprintf(
                'SMS Niaga accepts up to %d numbers per request, %d given. Use sendBulk() for larger lists.',
                self::MAX_NUMBERS_PER_REQUEST,
                count($numbers),
            ));
        }

        $payload = [
            'body' => $message,
            'phones' => $numbers,
            'preview' => $preview ? 1 : 0,
        ];

        if ($senderId !== null && trim($senderId) !== '') {
            $payload['sender_id'] = trim($senderId);
        }

        return SendResult::fromArray($this->data($this->client->post('/api/send', $payload)), $preview);
    }
}
