<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;
use Webimpian\SmsNiaga\SmsNiaga;

abstract class TestCase extends BaseTestCase
{
    protected array $history = [];

    protected function client(Response|\Throwable ...$responses): SmsNiaga
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new SmsNiaga('test-token', httpClient: new Client(['handler' => $stack]));
    }

    protected function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($body));
    }

    protected function request(int $index = 0): RequestInterface
    {
        return $this->history[$index]['request'];
    }

    protected function requestBody(int $index = 0): array
    {
        return json_decode((string) $this->request($index)->getBody(), true) ?? [];
    }

    protected function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/Fixtures/{$name}.json"), true);
    }
}
