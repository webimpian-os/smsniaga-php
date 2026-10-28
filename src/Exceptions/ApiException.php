<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Exceptions;

use RuntimeException;
use Throwable;

class ApiException extends RuntimeException implements SmsNiagaException
{
    public function __construct(
        string $message,
        private int $statusCode,
        private array $responseData = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(int $status, array $data): self
    {
        $message = is_string($data['message'] ?? null) && $data['message'] !== ''
            ? $data['message']
            : "SMS Niaga request failed with status {$status}.";

        // A bad token comes back as 403 "Unauthenticated"; a real 403 means the token can't touch that record.
        // Missing subscribers and sender IDs come back as 400 "... not found", groups and messages as 404.
        return match (true) {
            $status === 401, stripos($message, 'unauthenticated') === 0 => new AuthenticationException($message, $status, $data),
            $status === 403 => new AuthorizationException($message, $status, $data),
            $status === 404, $status === 400 && stripos($message, 'not found') !== false => new NotFoundException($message, $status, $data),
            $status === 400, $status === 422 => new ValidationException($message, $status, $data),
            $status === 429 => new RateLimitException($message, $status, $data),
            $status >= 500 => new ServerException($message, $status, $data),
            default => new self($message, $status, $data),
        };
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
