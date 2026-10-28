<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Resources;

use Webimpian\SmsNiaga\Exceptions\ApiException;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\HttpClient;

abstract class ApiResource
{
    public function __construct(protected HttpClient $client)
    {
    }

    protected function data(array $response): array
    {
        if (! is_array($response['data'] ?? null)) {
            throw new ApiException('SMS Niaga returned a response without a "data" field.', 200, $response);
        }

        return $response['data'];
    }

    protected function rows(array $response): array
    {
        return array_values(array_filter($this->data($response), 'is_array'));
    }

    protected function segment(string $uuid): string
    {
        $uuid = trim($uuid);

        if ($uuid === '') {
            throw new InvalidArgumentException('A UUID is required.');
        }

        return rawurlencode($uuid);
    }
}
