<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Tests\Support;

use PHPUnit\Framework\TestCase;
use Webimpian\SmsNiaga\Support\DeliveryReportCode;

final class DeliveryReportCodeTest extends TestCase
{
    public function test_it_describes_codes(): void
    {
        $this->assertSame('OK. No error encountered.', DeliveryReportCode::describe('000'));
        $this->assertSame('Subscriber opted out from receiving SMS.', DeliveryReportCode::describe('071'));
        $this->assertSame('Absent subscriber.', DeliveryReportCode::describe(1));
        $this->assertSame('Waiting for status.', DeliveryReportCode::describe(null));
        $this->assertSame('Unrecognised delivery report code 999.', DeliveryReportCode::describe('999'));
    }

    public function test_it_classifies_codes(): void
    {
        $this->assertTrue(DeliveryReportCode::isDelivered('000'));
        $this->assertTrue(DeliveryReportCode::isDelivered(0));
        $this->assertTrue(DeliveryReportCode::isPending('111'));
        $this->assertTrue(DeliveryReportCode::isPending(null));
        $this->assertTrue(DeliveryReportCode::isPending(''));
        $this->assertTrue(DeliveryReportCode::isFailed('054'));
        $this->assertFalse(DeliveryReportCode::isFailed('000'));
        $this->assertFalse(DeliveryReportCode::isFailed('111'));
    }

    public function test_it_lists_every_documented_code(): void
    {
        $this->assertCount(39, DeliveryReportCode::all());
    }
}
