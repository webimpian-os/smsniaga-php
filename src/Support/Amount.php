<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Support;

final class Amount
{
    // The docs call this "cents x1000", but the examples are 1/10,000 MYR: 368000 = RM36.80, 1000 = one RM0.10 SMS.
    private const UNITS_PER_RINGGIT = 10000;

    public static function fromUnits(mixed $units): float
    {
        return round((float) $units / self::UNITS_PER_RINGGIT, 4);
    }

    public static function fromRinggit(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
