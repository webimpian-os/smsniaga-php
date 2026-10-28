<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Webimpian\SmsNiaga\Exceptions\ApiException;
use Webimpian\SmsNiaga\Exceptions\AuthenticationException;
use Webimpian\SmsNiaga\Exceptions\AuthorizationException;
use Webimpian\SmsNiaga\Exceptions\ConnectionException;
use Webimpian\SmsNiaga\Exceptions\NotFoundException;
use Webimpian\SmsNiaga\Exceptions\RateLimitException;
use Webimpian\SmsNiaga\Exceptions\ServerException;
use Webimpian\SmsNiaga\Exceptions\SmsNiagaException;
use Webimpian\SmsNiaga\Exceptions\ValidationException;

final class ErrorHandlingTest extends TestCase
{
    public function test_a_bad_token_reported_inside_an_http_200_is_an_authentication_error(): void
    {
        $sms = $this->client($this->json(['message' => 'Unauthenticated', 'status_code' => 403]));

        try {
            $sms->balance();
            $this->fail('Expected an exception.');
        } catch (AuthenticationException $e) {
            $this->assertSame('Unauthenticated', $e->getMessage());
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(['message' => 'Unauthenticated', 'status_code' => 403], $e->getResponseData());
        }
    }

    /**
     * @dataProvider statuses
     */
    public function test_it_maps_statuses_to_exceptions(int $status, string $message, string $exception): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->client($this->json(['message' => $message, 'status_code' => $status], $status))->groups()->all();
    }

    public function statuses(): array
    {
        return [
            'unauthenticated' => [401, 'Unauthenticated.', AuthenticationException::class],
            'forbidden' => [403, 'Unauthorized action', AuthorizationException::class],
            'not found' => [404, 'SubscriberGroup not found', NotFoundException::class],
            'not found reported as 400' => [400, 'Subscriber list not found.', NotFoundException::class],
            'bad request' => [400, 'Sender ID with this name is already exists for current user.', ValidationException::class],
            'unprocessable' => [422, 'The given data was invalid.', ValidationException::class],
            'too many requests' => [429, 'Too Many Attempts.', RateLimitException::class],
            'server error' => [500, 'Server Error', ServerException::class],
            'other' => [409, 'Conflict', ApiException::class],
        ];
    }

    public function test_validation_errors_are_exposed(): void
    {
        $sms = $this->client($this->json([
            'message' => 'The given data was invalid.',
            'errors' => ['name' => ['The name field is required.']],
        ], 422));

        try {
            $sms->groups()->create('');
            $this->fail('Expected an exception.');
        } catch (ValidationException $e) {
            $this->assertSame(['name' => ['The name field is required.']], $e->errors());
        }
    }

    public function test_a_non_json_error_page_still_maps_to_a_typed_exception(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('status 502');

        $this->client(new Response(502, [], '<html>Bad Gateway</html>'))->balance();
    }

    public function test_a_non_json_success_response_is_rejected(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('not JSON');

        $this->client(new Response(200, [], '<html>Maintenance</html>'))->balance();
    }

    public function test_network_failures_become_connection_exceptions(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not reach SMS Niaga');

        $this->client(new ConnectException('cURL error 28', new Request('GET', '/api/balance')))->balance();
    }

    public function test_every_sdk_exception_shares_one_interface(): void
    {
        try {
            $this->client($this->json(['message' => 'Unauthenticated', 'status_code' => 403]))->balance();
        } catch (SmsNiagaException $e) {
            $this->assertInstanceOf(AuthenticationException::class, $e);

            return;
        }

        $this->fail('Expected an exception.');
    }
}
