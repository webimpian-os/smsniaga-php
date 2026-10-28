# Changelog

Notable changes to each release.

## [1.0.0] - 2026-10-28

### Added

- `SmsNiaga` client for the live SMS Niaga API, with `send()` and `balance()` shortcuts.
- Messages: `send()`, `preview()`, `sendBulk()` (automatic batches of 50) and `report()` for delivery reports.
- Sender IDs and Groups: full create, read, update and delete, plus `findByName()`, `default()`, `setDefault()` and `firstOrCreate()`.
- Subscribers: `find()`, `create()`, `update()` and `delete()`. Listing all subscribers is left out because that endpoint times out on large accounts.
- Typed results: `SendResult`, `BulkSendResult`, `DeliveryReport`, `RecipientStatus`, `Balance`, `SenderId`, `Subscriber`, `Group`.
- Helpers: `PhoneNumber` (Malaysian mobile validation and formatting), `SmsLength` (characters and credits, matching SMS Niaga's count) and `DeliveryReportCode`.
- Exceptions for each kind of failure, all implementing `SmsNiagaException`, including errors the API returns with HTTP 200.
- Support for PHP 8.0 to 8.5 and Guzzle 7 and 8.
