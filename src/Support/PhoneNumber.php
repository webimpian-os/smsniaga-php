<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Support;

use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;

final class PhoneNumber
{
    public static function normalize(string $phone): string
    {
        // \p{Z} and \p{Cf} cover the non-breaking spaces and invisible marks that come with numbers pasted from WhatsApp or Excel.
        if (preg_match('/^[\s\p{Z}\p{Cf}]*\+?[0-9\s\p{Z}\p{Cf}().-]+$/u', $phone) !== 1) {
            throw self::invalid($phone);
        }

        $digits = preg_replace('/[^0-9]+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '6' . $digits;
        } elseif (str_starts_with($digits, '1')) {
            $digits = '60' . $digits;
        }

        if (preg_match('/^601[0-9]{8,9}$/', $digits) !== 1) {
            throw self::invalid($phone);
        }

        return $digits;
    }

    public static function normalizeMany(string|array $phones): array
    {
        if (is_string($phones)) {
            $phones = preg_split('/[,;\r\n]+/', $phones) ?: [];
        }

        $normalized = [];

        foreach ($phones as $phone) {
            $phone = trim((string) $phone);

            if ($phone !== '') {
                $normalized[] = self::normalize($phone);
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function isValid(string $phone): bool
    {
        try {
            self::normalize($phone);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public static function toLocal(string $phone): string
    {
        return substr(self::normalize($phone), 1);
    }

    private static function invalid(string $phone): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf('"%s" is not a valid Malaysian mobile number.', trim($phone)));
    }
}
