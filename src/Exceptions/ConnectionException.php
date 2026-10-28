<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Exceptions;

use RuntimeException;

final class ConnectionException extends RuntimeException implements SmsNiagaException
{
}
