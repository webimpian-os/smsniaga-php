<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests\Support;

use PHPUnit\Framework\TestCase;
use Webimpian\SmsNiaga\Support\SmsLength;

final class SmsLengthTest extends TestCase
{
    /**
     * @dataProvider recordedPreviews
     */
    public function test_it_matches_what_sms_niaga_counted(string $message, string $type, int $characters, int $credits): void
    {
        $length = SmsLength::of($message, 'CUBAAN');

        $this->assertSame($type, $length->type);
        $this->assertSame($characters, $length->characters);
        $this->assertSame($credits, $length->credits);
    }

    // Recorded from live preview() calls with sender ID CUBAAN on 28 Oct 2026.
    public function recordedPreviews(): array
    {
        return [
            'plain text' => ['SMS Niaga PHP SDK live test. Please ignore.', SmsLength::ASCII, 55, 1],
            'special characters count twice' => ['Code {A1} [ok] | path\\x ~ ^ end', SmsLength::ASCII, 51, 1],
            'ascii, exactly one SMS' => [str_repeat('a', 148), SmsLength::ASCII, 160, 1],
            'ascii, one over' => [str_repeat('a', 149), SmsLength::ASCII, 161, 2],
            'ascii, two full parts' => [str_repeat('a', 294), SmsLength::ASCII, 306, 2],
            'ascii, three parts' => [str_repeat('a', 295), SmsLength::ASCII, 307, 3],
            'a special character tips it over' => [str_repeat('a', 147) . '~', SmsLength::ASCII, 161, 2],
            'backtick is plain ascii' => ['`' . str_repeat('a', 148), SmsLength::ASCII, 161, 2],
            'unicode, exactly one SMS' => [str_repeat('中', 58), SmsLength::UNICODE, 70, 1],
            'unicode, one over' => [str_repeat('中', 59), SmsLength::UNICODE, 71, 2],
            'unicode, two full parts' => [str_repeat('中', 122), SmsLength::UNICODE, 134, 2],
            'unicode, three parts' => [str_repeat('中', 123), SmsLength::UNICODE, 135, 3],
            'e-acute makes it unicode' => ['é' . str_repeat('a', 70), SmsLength::UNICODE, 83, 2],
            'pound sign makes it unicode' => ['£' . str_repeat('a', 70), SmsLength::UNICODE, 83, 2],
            'en dash makes it unicode' => ['–' . str_repeat('a', 70), SmsLength::UNICODE, 83, 2],
            'curly quotes' => ['“Hi”', SmsLength::UNICODE, 16, 1],
            'chinese' => ['你好，这是测试。', SmsLength::UNICODE, 20, 1],
            'emoji are removed' => ['Test 😀 end', SmsLength::ASCII, 21, 1],
            'newline' => ["Line1\nLine2", SmsLength::ASCII, 23, 1],
            'windows newline counts once' => ["Line1\r\nLine2", SmsLength::ASCII, 23, 1],
        ];
    }

    public function test_without_a_sender_id_it_counts_the_text_as_given(): void
    {
        $this->assertSame(12, SmsLength::of('RM0 CUBAAN: ')->characters);
        $this->assertSame(12, SmsLength::of('RM0 CUBAAN: ', '  ')->characters);
    }

    public function test_it_reports_characters_left_before_the_next_credit(): void
    {
        $this->assertSame(105, SmsLength::of('SMS Niaga PHP SDK live test. Please ignore.', 'CUBAAN')->remaining());
        $this->assertSame(0, SmsLength::of(str_repeat('a', 148), 'CUBAAN')->remaining());
        $this->assertSame(145, SmsLength::of(str_repeat('a', 149), 'CUBAAN')->remaining());
        $this->assertSame(63, SmsLength::of(str_repeat('中', 59), 'CUBAAN')->remaining());
    }

    public function test_is_unicode(): void
    {
        $this->assertTrue(SmsLength::of('Harga – RM10')->isUnicode());
        $this->assertFalse(SmsLength::of('Harga - RM10')->isUnicode());
    }
}
