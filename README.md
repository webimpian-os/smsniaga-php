# SMS Niaga PHP SDK

[![tests](https://github.com/webimpian-os/smsniaga-php/actions/workflows/tests.yml/badge.svg)](https://github.com/webimpian-os/smsniaga-php/actions/workflows/tests.yml)
![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE.md)

A PHP client for the [SMS Niaga](https://manage.smsniaga.com) API. Send SMS, track delivery, check your balance, and manage sender IDs, groups and subscribers.

```php
use Webimpian\SmsNiaga\SmsNiaga;

$sms = new SmsNiaga('your-api-token');

$result = $sms->send('012-345 6789', 'Your order #1024 has shipped.');

$result->uuid;         // Use it to get the delivery report
$result->totalCharge;  // 0.1 (RM)
```

> [!IMPORTANT]
> SMS Niaga has no sandbox. Every `send()` delivers a real SMS and is charged. Use [`preview()`](#preview-for-free) to test for free.

## Contents

- [Installation](#installation)
- [Sending SMS](#sending-sms)
- [Delivery reports](#delivery-reports)
- [Balance](#balance)
- [Sender IDs, groups and subscribers](#sender-ids-groups-and-subscribers)
- [Helpers](#helpers)
- [Error handling](#error-handling)
- [Testing your app](#testing-your-app)

## Installation

```bash
composer require webimpian/smsniaga-php
```

You need PHP 8.0 or later and your API token, which is in the SMS Niaga dashboard under **Profile > API Token**.

```php
$sms = new SmsNiaga(
    token: 'your-api-token',
    timeout: 30,          // Optional. Seconds to wait for a response
    connectTimeout: 10,   // Optional. Seconds to wait for a connection
    httpClient: null,     // Optional. Your own Guzzle client, for a proxy, logging or tests
);
```

**Laravel:** register one shared client in `AppServiceProvider::register()`. Then type-hint `SmsNiaga` wherever you need it.

```php
// config/services.php
'smsniaga' => ['token' => env('SMSNIAGA_TOKEN')],

// AppServiceProvider::register()
$this->app->singleton(SmsNiaga::class, fn () => new SmsNiaga((string) config('services.smsniaga.token')));
```

## Sending SMS

```php
$sms->send('012-345 6789', 'Your verification code is 482913.');

$sms->send(['0123456789', '+60 19-876 5432'], 'Branch closes at 6pm today.');

$sms->send('0123456789', 'Your parcel is out for delivery.', senderId: 'MYSHOP');
// Recipient sees: RM0 MYSHOP: Your parcel is out for delivery.
```

- **Numbers:** Malaysian mobiles only, typed any way you like. The SDK cleans, validates and de-duplicates them before sending (see [`PhoneNumber`](#phonenumber)). One call takes up to 50 numbers.
- **Sender ID:** SMS Niaga puts `RM0 <SENDER ID>: ` in front of every message. Leave out `senderId` to use your account's default.

### Preview for free

`preview()` takes the same arguments as `send()`, but sends nothing and charges nothing.

```php
$preview = $sms->messages()->preview('0123456789', 'Your order has shipped.', senderId: 'MYSHOP');

$preview->message;       // "RM0 MYSHOP: Your order has shipped." as the recipient sees it
$preview->charCount;     // 35
$preview->messagePages;  // 1 credit per recipient
$preview->totalCharge;   // 0.1 (RM)
```

### More than 50 numbers

`sendBulk()` sends to any number of recipients, 50 per request.

```php
$result = $sms->messages()->sendBulk($phones, 'Raya sale: 30% off this weekend.');

$result->totalNumbers();  // Recipients who were sent the message
$result->totalCharge();   // Total cost in RM
$result->uuids();         // One message UUID per batch

if ($result->hasFailures()) {
    $retryLater = $result->failedNumbers();
}
```

If the first batch fails, nothing has been sent, so the exception is thrown. After that, failed batches are recorded in `$result->failures` instead, so you always know who received the message.

### What `send()` returns

| Property | Description |
|---|---|
| `uuid` | Message UUID, for the delivery report. `null` for a preview. |
| `numbers`, `totalNumbers` | The recipients. |
| `message`, `charCount`, `messagePages` | The final text, its length and its credits per recipient. |
| `totalCharge`, `balanceAfter` | The cost and your remaining balance, in RM. |
| `senderId` | The sender ID that was used. |

### Message length and cost

Each credit costs RM0.10 per recipient.

| Message type | First credit | Each credit after |
|---|---|---|
| ASCII | 160 characters | 153 characters |
| Unicode | 70 characters | 67 characters |

- One character outside plain ASCII, such as `é`, `£`, `–`, curly quotes or Chinese, makes the whole message Unicode.
- In ASCII messages, `{ } [ ] | \ ~ ^` count as 2 characters each.
- The `RM0 <SENDER ID>: ` prefix counts toward the length.
- Emoji are removed by SMS Niaga.

`SmsLength` counts a message the same way SMS Niaga does, without calling the API. It's useful for a live counter in a form.

```php
use Webimpian\SmsNiaga\Support\SmsLength;

$length = SmsLength::of('Your order #1024 has shipped.', 'MYSHOP');

$length->type;         // "ascii"
$length->characters;   // 41, including "RM0 MYSHOP: "
$length->credits;      // 1
$length->remaining();  // 119 characters left before the next credit
```

## Delivery reports

```php
$report = $sms->messages()->report($result->uuid);

$report->isFullyDelivered();  // true when every recipient has it
$report->isComplete();        // true when no recipient is still pending

foreach ($report->failed() as $recipient) {
    echo "{$recipient->phone}: {$recipient->description()}";
    // 60123456789: Absent subscriber.
}
```

You can also use `pending()`, `deliveredRecipients()` and `recipient($phone)`. Each recipient has `code`, `receivedAt` and `isDelivered()`, `isPending()` and `isFailed()`. Operators can take up to 48 hours to return a final status, for example when the phone is off.

## Balance

```php
$balance = $sms->balance();

$balance->amount;       // 36.8
echo $balance;          // RM36.80
$balance->isBelow(20);  // true when it's time to top up
```

## Sender IDs, groups and subscribers

```php
$shop = $sms->senderIds()->create('MYSHOP', 'Online store');
$sms->senderIds()->setDefault($shop->uuid);

$vip = $sms->groups()->firstOrCreate('VIP Customers');

$aisyah = $sms->subscribers()->create('012-345 6789', name: 'Aisyah', groupUuid: $vip->uuid);
$sms->subscribers()->update($aisyah->uuid, email: 'aisyah@example.com');
```

For `update()`, pass only the fields you want to change, as named arguments. The SDK fills in the rest from the current record.

**Sender IDs:** `$sms->senderIds()`

| Method | What it does |
|---|---|
| `all()` | Lists every sender ID. |
| `find($uuid)` | Gets one sender ID. |
| `findByName($name)` | Finds a sender ID by name. Case-insensitive. |
| `default()` | Gets the account's default sender ID. |
| `create($name, $description, $isDefault)` | Creates a sender ID. Only the name is required. |
| `update($uuid, name:, description:, isDefault:)` | Changes the fields you pass. |
| `setDefault($uuid)` | Makes this sender ID the default. |
| `delete($uuid)` | Deletes a sender ID that has never been used to send. |

**Groups:** `$sms->groups()`

| Method | What it does |
|---|---|
| `all()` | Lists every group. |
| `find($uuid)` | Gets one group. |
| `findByName($name)` | Finds a group by name. Case-insensitive. |
| `create($name)` | Creates a group. |
| `firstOrCreate($name)` | Gets the group with this name, or creates it. |
| `update($uuid, $name)` | Renames a group. |
| `delete($uuid)` | Deletes a group. |

**Subscribers:** `$sms->subscribers()`

| Method | What it does |
|---|---|
| `find($uuid)` | Gets one subscriber, with their groups. |
| `create($phone, $name, $email, $groupUuid)` | Adds a subscriber, optionally to a group. Only the phone is required. |
| `update($uuid, phone:, name:, email:)` | Changes the fields you pass. |
| `delete($uuid)` | Deletes a subscriber. |

There is no method to list every subscriber. SMS Niaga returns them all in one unpaginated response, which times out on large accounts. Keep the UUID that `create()` returns.

## Helpers

Static helpers in `Webimpian\SmsNiaga\Support`. They make no API calls, so you can use them anywhere, for example in form validation.

### PhoneNumber

| Call | Result |
|---|---|
| `PhoneNumber::normalize('012-345 6789')` | `"60123456789"` |
| `PhoneNumber::normalizeMany('0123456789, 0198765432')` | `["60123456789", "60198765432"]` |
| `PhoneNumber::isValid('03-1234 5678')` | `false`. Never throws. |
| `PhoneNumber::toLocal('60123456789')` | `"0123456789"` |

`normalize()` returns the `60…` format SMS Niaga expects, or throws `InvalidArgumentException`:

| Accepted | Rejected |
|---|---|
| `012-345 6789`, `(012) 345-6789`, `012.345.6789` | Letters or symbols: `0123456789abc`, `O123456789` |
| `+60 12-345 6789`, `0060123456789`, `60123456789` | Landlines: `03-1234 5678` |
| A missing leading zero: `12-345 6789` | Numbers outside Malaysia: `+65 9123 4567` |
| Numbers pasted from WhatsApp or Excel | Too short or too long: `012-345` |

### DeliveryReportCode

```php
use Webimpian\SmsNiaga\Support\DeliveryReportCode;

DeliveryReportCode::describe('071');     // "Subscriber opted out from receiving SMS."
DeliveryReportCode::isDelivered('000');  // true
DeliveryReportCode::isPending('111');    // true
DeliveryReportCode::all();               // Every code and its description
```

| Code | Meaning |
|---|---|
| `000` | Delivered |
| `111` | Waiting for the operator's status |
| `001`, `016`, `017` | Phone is off or out of coverage |
| `005`, `054` | Number doesn't exist or is invalid |
| `069`, `076`, `077` | Problem with the sender ID |
| `071` | Recipient has opted out |

## Error handling

Every SDK exception implements `SmsNiagaException`, so one `catch` can handle them all.

```php
use Webimpian\SmsNiaga\Exceptions\ConnectionException;
use Webimpian\SmsNiaga\Exceptions\SmsNiagaException;
use Webimpian\SmsNiaga\Exceptions\ValidationException;

try {
    $sms->send($phone, $message, senderId: 'MYSHOP');
} catch (ValidationException $e) {
    // SMS Niaga rejected the request, e.g. an unknown sender ID
} catch (ConnectionException $e) {
    // Timeout or network failure. The SMS may already have been sent, so check before you retry.
} catch (SmsNiagaException $e) {
    // Anything else
}
```

| Exception | When it's thrown |
|---|---|
| `InvalidArgumentException` | Your input was rejected before any API call: an invalid number, an empty message, or more than 50 numbers in `send()`. |
| `AuthenticationException` | The API token is missing, wrong or revoked. |
| `AuthorizationException` | The token can't act on that record. |
| `NotFoundException` | The record doesn't exist. |
| `ValidationException` | SMS Niaga rejected the request. `errors()` returns field errors when the API sends them. |
| `RateLimitException` | Too many requests. |
| `ServerException` | SMS Niaga had an internal error. |
| `ConnectionException` | SMS Niaga couldn't be reached, or the request timed out. |

Every API error extends `ApiException`, which has `getStatusCode()` and `getResponseData()`. Errors that SMS Niaga returns with HTTP 200 are detected too. The SDK never retries on its own, because sending twice means paying twice.

## Testing your app

Give the client a Guzzle mock so your tests never send a real SMS:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(200, [], json_encode(['data' => ['uuid' => 'test-uuid', 'numbers' => ['60123456789'], 'total_charge' => 1000]])),
]);

$sms = new SmsNiaga('test-token', httpClient: new Client(['handler' => HandlerStack::create($mock)]));
```

In Laravel, swap it in with `$this->app->instance(SmsNiaga::class, $sms)`.

## Development

```bash
composer test      # PHPUnit
composer analyse   # PHPStan
```

API reference: [smsniaga.stoplight.io](https://smsniaga.stoplight.io/docs/api-reference/ZG9jOjE-introduction). License: [MIT](LICENSE.md).
