<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

use Stringable;
use Webimpian\SmsNiaga\Support\Amount;

final class Balance implements Stringable
{
    public function __construct(
        public float $amount,
        public string $formatted,
        public string $currency,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: Amount::fromUnits($data['cents'] ?? 0),
            formatted: (string) ($data['human'] ?? ''),
            currency: (string) ($data['currency'] ?? 'MYR'),
        );
    }

    public function isBelow(float $ringgit): bool
    {
        return $this->amount < $ringgit;
    }

    public function __toString(): string
    {
        return $this->formatted !== '' ? $this->formatted : sprintf('RM%.2f', $this->amount);
    }
}
