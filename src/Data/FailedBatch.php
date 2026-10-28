<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use Webimpian\SmsNiaga\Exceptions\SmsNiagaException;

final class FailedBatch
{
    public function __construct(
        public array $numbers,
        public SmsNiagaException $exception,
    ) {
    }
}
