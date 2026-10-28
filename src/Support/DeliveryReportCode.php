<?php

declare(strict_types=1);

namespace Webimpian\SmsNiaga\Support;

final class DeliveryReportCode
{
    public const DELIVERED = '000';
    public const WAITING_FOR_STATUS = '111';
    public const UNKNOWN_ERROR = '255';

    private const DESCRIPTIONS = [
        '000' => 'OK. No error encountered.',
        '001' => 'Absent subscriber.',
        '002' => 'Handset memory capacity exceeded. Handset has run out of free memory to store new message.',
        '003' => 'Equipment protocol error.',
        '004' => 'Equipment not equipped with short-message capability.',
        '005' => 'Unknown subscriber. The IMSI is unknown in the HLR.',
        '006' => 'Illegal subscriber. The mobile station failed authentication.',
        '007' => 'Teleservice not provisioned. Mobile subscription identified by the MSISDN number does include the short message service.',
        '008' => 'Illegal equipment. IMEI check failed, i.e. the IMEI is either blacklisted or not whitelisted.',
        '009' => 'Call barred. Operator barred the MSISDN number.',
        '010' => 'Facility not supported. VLR in the PLMN does not support MT short message service.',
        '011' => 'Subscriber busy for MT short message.',
        '012' => 'System failure. Task cannot be completed because of a problem in another entity.',
        '013' => 'Data missing. Necessary parameter is not present in the primitive.',
        '014' => 'Unexpected data value. Necessary data is badly formatted in the primitive.',
        '015' => 'Unidentified subscriber.',
        '016' => 'Absent subscriber. No paging response.',
        '017' => 'Absent subscriber. IMSI detached.',
        '018' => 'Absent subscriber. Roaming restriction.',
        '047' => 'Application context not supported.',
        '050' => 'Temporary error received from peer SMSC.',
        '051' => 'SMS malformed. SMS is not formed correctly. This error is specific to IP-based protocols like SMPP.',
        '052' => 'SMS expired.',
        '053' => 'Please contact administrator.',
        '054' => 'Invalid destination. Receiver is not a valid number.',
        '055' => 'Unable to find outbound route for this SMS.',
        '056' => 'SMS buffered.',
        '057' => 'Timeout waiting for response from peer.',
        '058' => 'Throttling error. The user has exceeded allowed message limit.',
        '061' => 'Subscriber blacklisted.',
        '062' => 'Subscriber not whitelisted.',
        '069' => 'Invalid sender ID.',
        '071' => 'Subscriber opted out from receiving SMS.',
        '074' => 'SMS rejected. Error received from peer SMSC.',
        '075' => 'SMS rejected. Inappropriate SMS content.',
        '076' => 'Sender ID blacklisted.',
        '077' => 'Sender ID not whitelisted.',
        '111' => 'Waiting for status.',
        '255' => 'Unknown error.',
    ];

    public static function normalize(string|int|null $code): ?string
    {
        if ($code === null || trim((string) $code) === '') {
            return null;
        }

        return str_pad(trim((string) $code), 3, '0', STR_PAD_LEFT);
    }

    public static function describe(string|int|null $code): string
    {
        $code = self::normalize($code);

        if ($code === null) {
            return self::DESCRIPTIONS[self::WAITING_FOR_STATUS];
        }

        return self::DESCRIPTIONS[$code] ?? "Unrecognised delivery report code {$code}.";
    }

    public static function isDelivered(string|int|null $code): bool
    {
        return self::normalize($code) === self::DELIVERED;
    }

    public static function isPending(string|int|null $code): bool
    {
        $code = self::normalize($code);

        return $code === null || $code === self::WAITING_FOR_STATUS;
    }

    public static function isFailed(string|int|null $code): bool
    {
        return ! self::isDelivered($code) && ! self::isPending($code);
    }

    public static function all(): array
    {
        return self::DESCRIPTIONS;
    }
}
