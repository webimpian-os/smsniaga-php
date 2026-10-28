<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Webimpian\SmsNiaga\Exceptions\ApiException;
use Webimpian\SmsNiaga\Exceptions\ConnectionException;

final class HttpClient
{
    public const BASE_URL = 'https://manage.smsniaga.com';

    private ClientInterface $http;

    public function __construct(
        private string $token,
        ?ClientInterface $http = null,
        float $timeout = 30.0,
        float $connectTimeout = 10.0,
    ) {
        $this->http = $http ?? new Client([
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
        ]);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query === [] ? [] : ['query' => $query]);
    }

    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, ['json' => $body]);
    }

    public function put(string $path, array $body): array
    {
        return $this->request('PUT', $path, ['json' => $body]);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, array $options = []): array
    {
        $options['http_errors'] = false;
        $options['headers'] = [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'User-Agent' => 'smsniaga-php/' . SmsNiaga::VERSION . ' PHP/' . PHP_VERSION,
        ];

        try {
            $response = $this->http->request($method, self::BASE_URL . $path, $options);
        } catch (GuzzleException $e) {
            throw new ConnectionException('Could not reach SMS Niaga: ' . $e->getMessage(), 0, $e);
        }

        return $this->decode($response);
    }

    private function decode(ResponseInterface $response): array
    {
        $httpStatus = $response->getStatusCode();
        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            if ($httpStatus >= 400) {
                throw ApiException::fromResponse($httpStatus, []);
            }

            throw new ApiException('SMS Niaga returned a response that is not JSON.', $httpStatus);
        }

        // The API reports some failures (e.g. a bad token) as HTTP 200 with the real status in the body.
        $status = $httpStatus >= 400 ? $httpStatus : (int) ($data['status_code'] ?? $httpStatus);

        if ($status >= 400) {
            throw ApiException::fromResponse($status, $data);
        }

        return $data;
    }
}
