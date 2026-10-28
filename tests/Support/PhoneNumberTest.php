<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests\Support;

use PHPUnit\Framework\TestCase;
use Webimpian\SmsNiaga\Exceptions\InvalidArgumentException;
use Webimpian\SmsNiaga\Support\PhoneNumber;

final class PhoneNumberTest extends TestCase
{
    /**
     * @dataProvider formats
     */
    public function test_it_normalizes_common_formats(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    public function formats(): array
    {
        return [
            'local' => ['0123456789', '60123456789'],
            'local with dashes and spaces' => ['012-345 6789', '60123456789'],
            'local 011 number' => ['011-1234 5678', '601112345678'],
            'international with plus' => ['+60 12-345 6789', '60123456789'],
            'international with 00' => ['0060123456789', '60123456789'],
            'already normalized' => ['60123456789', '60123456789'],
            'brackets' => ['(012) 345-6789', '60123456789'],
            'missing leading zero' => ['12-345 6789', '60123456789'],
            'missing leading zero, 011 number' => ['11-1234 5678', '601112345678'],
            'dots' => ['012.345.6789', '60123456789'],
            'surrounding whitespace' => ["  012 345 6789\t\n", '60123456789'],
            'non-breaking spaces from a paste' => ["+60\u{00A0}12\u{00A0}345\u{00A0}6789", '60123456789'],
            'invisible direction marks from a paste' => ["\u{202A}+60 12-345 6789\u{202C}", '60123456789'],
        ];
    }

    /**
     * @dataProvider invalid
     */
    public function test_it_rejects_anything_but_a_malaysian_mobile(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid Malaysian mobile number');

        PhoneNumber::normalize($input);
    }

    public function invalid(): array
    {
        return [
            'empty' => [''],
            'letters' => ['not a phone'],
            'letters mixed into digits' => ['0123456789abc'],
            'letter O instead of zero' => ['O123456789'],
            'plus in the middle' => ['012+3456789'],
            'other symbols' => ['012*345#6789'],
            'too short' => ['012-345'],
            'too long' => ['012-3456 78901'],
            'landline' => ['03-1234 5678'],
            'singapore' => ['+65 9123 4567'],
            'indonesia' => ['+62 812 3456 7890'],
        ];
    }

    public function test_normalize_many_splits_trims_and_deduplicates(): void
    {
        $this->assertSame(
            ['60123456789', '60198765432'],
            PhoneNumber::normalizeMany("012-345 6789,\n+60123456789 ; 0198765432,,"),
        );
        $this->assertSame(['60123456789'], PhoneNumber::normalizeMany(['0123456789', ' ', '60123456789']));
        $this->assertSame([], PhoneNumber::normalizeMany(''));
    }

    public function test_normalize_many_rejects_the_whole_list_if_one_number_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"+65 9123 4567"');

        PhoneNumber::normalizeMany(['0123456789', '+65 9123 4567']);
    }

    public function test_is_valid(): void
    {
        $this->assertTrue(PhoneNumber::isValid('012-345 6789'));
        $this->assertTrue(PhoneNumber::isValid('+60 11-1234 5678'));
        $this->assertFalse(PhoneNumber::isValid('03-1234 5678'));
        $this->assertFalse(PhoneNumber::isValid('+65 9123 4567'));
        $this->assertFalse(PhoneNumber::isValid('hello'));
    }

    public function test_it_formats_for_local_display(): void
    {
        $this->assertSame('0123456789', PhoneNumber::toLocal('+60 12-345 6789'));
        $this->assertSame('01112345678', PhoneNumber::toLocal('601112345678'));
    }
}
