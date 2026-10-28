<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

final class Timestamp
{
    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            // Zoneless values ("2020-12-24 19:12:05") are Malaysia time; ISO values ("...Z") carry their own zone.
            return new DateTimeImmutable($value, new DateTimeZone('Asia/Kuala_Lumpur'));
        } catch (Exception) {
            return null;
        }
    }
}
