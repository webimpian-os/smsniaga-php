<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Exceptions;

final class ValidationException extends ApiException
{
    public function errors(): array
    {
        $errors = $this->getResponseData()['errors'] ?? [];

        return is_array($errors) ? $errors : [];
    }
}
