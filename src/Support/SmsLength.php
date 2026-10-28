<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Support;

final class SmsLength
{
    public const ASCII = 'ascii';
    public const UNICODE = 'unicode';

    private const LIMITS = [
        self::ASCII => ['single' => 160, 'multi' => 153],
        self::UNICODE => ['single' => 70, 'multi' => 67],
    ];

    public function __construct(
        public string $type,
        public int $characters,
        public int $credits,
    ) {
    }

    public static function of(string $message, ?string $senderId = null): self
    {
        if ($senderId !== null && trim($senderId) !== '') {
            $message = 'RM0 ' . trim($senderId) . ': ' . $message;
        }

        // Mirrors SMS Niaga: emoji and other characters outside the Basic Multilingual Plane are removed, and \r\n counts once.
        $message = str_replace("\r\n", "\n", preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $message) ?? $message);

        if (preg_match('/[^\x00-\x7F]/', $message) === 1) {
            $type = self::UNICODE;
            $characters = (int) preg_match_all('/./su', $message);
        } else {
            $type = self::ASCII;
            $characters = strlen($message) + (int) preg_match_all('/[{}\[\]|\\\\~^]/', $message);
        }

        $limits = self::LIMITS[$type];
        $credits = $characters <= $limits['single'] ? 1 : (int) ceil($characters / $limits['multi']);

        return new self($type, $characters, $credits);
    }

    public function isUnicode(): bool
    {
        return $this->type === self::UNICODE;
    }

    public function remaining(): int
    {
        $limits = self::LIMITS[$this->type];

        return $this->credits === 1
            ? $limits['single'] - $this->characters
            : $this->credits * $limits['multi'] - $this->characters;
    }
}
