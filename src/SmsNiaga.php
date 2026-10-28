<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga;

use GuzzleHttp\ClientInterface;
use Webimpian\SmsNiaga\Data\Balance;
use Webimpian\SmsNiaga\Data\SendResult;
use Webimpian\SmsNiaga\Exceptions\ApiException;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\Resources\Groups;
use Webimpian\SmsNiaga\Resources\Messages;
use Webimpian\SmsNiaga\Resources\SenderIds;
use Webimpian\SmsNiaga\Resources\Subscribers;

final class SmsNiaga
{
    public const VERSION = '1.0.0';

    private HttpClient $client;
    private ?Messages $messages = null;
    private ?SenderIds $senderIds = null;
    private ?Subscribers $subscribers = null;
    private ?Groups $groups = null;

    public function __construct(
        string $token,
        float $timeout = 30.0,
        float $connectTimeout = 10.0,
        ?ClientInterface $httpClient = null,
    ) {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException('An SMS Niaga API token is required. Find it in the dashboard under Profile > API Token.');
        }

        $this->client = new HttpClient($token, $httpClient, $timeout, $connectTimeout);
    }

    public function send(string|array $to, string $message, ?string $senderId = null): SendResult
    {
        return $this->messages()->send($to, $message, $senderId);
    }

    public function balance(): Balance
    {
        $response = $this->client->get('/api/balance');
        $balance = $response['balance'] ?? $response['data']['balance'] ?? null;

        if (! is_array($balance)) {
            throw new ApiException('SMS Niaga returned a balance response without a "balance" field.', 200, $response);
        }

        return Balance::fromArray($balance);
    }

    public function messages(): Messages
    {
        return $this->messages ??= new Messages($this->client);
    }

    public function senderIds(): SenderIds
    {
        return $this->senderIds ??= new SenderIds($this->client);
    }

    public function subscribers(): Subscribers
    {
        return $this->subscribers ??= new Subscribers($this->client);
    }

    public function groups(): Groups
    {
        return $this->groups ??= new Groups($this->client);
    }
}
