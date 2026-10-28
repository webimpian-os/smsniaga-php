<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Data;

final class BulkSendResult
{
    public function __construct(
        public array $results,
        public array $failures = [],
    ) {
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }

    public function totalNumbers(): int
    {
        return array_sum(array_map(static fn (SendResult $result): int => $result->totalNumbers, $this->results));
    }

    public function totalCharge(): float
    {
        return round(array_sum(array_map(static fn (SendResult $result): float => $result->totalCharge, $this->results)), 4);
    }

    public function uuids(): array
    {
        return array_values(array_filter(array_map(static fn (SendResult $result): ?string => $result->uuid, $this->results)));
    }

    public function failedNumbers(): array
    {
        return array_merge([], ...array_map(static fn (FailedBatch $batch): array => $batch->numbers, $this->failures));
    }
}
