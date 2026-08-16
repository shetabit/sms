# Changelog

All Notable changes to `shetabit/sms` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## Unreleased

### Added

- A test suite that covers every driver and the Laravel side of the package, and that never talks to a gateway: the
  driver tests answer the HTTP calls of a driver from a queue of prepared responses, the drivers that go through an SDK
  of their own get a fake of that SDK, and the feature tests boot a real application with Testbench.
- `Shetabit\Sms\Abstracts\Driver::sendTo()`, which sends to a single recipient. Walking the list of recipients and
  collecting the answers is done by the abstract driver now, and no longer copied into every driver.
- `Shetabit\Sms\Abstracts\Driver::setting()` and `booleanSetting()` to read a single value out of the settings of a
  driver.
- `Shetabit\Sms\Exceptions\SendingFailedException` and `Shetabit\Sms\Exceptions\InvalidNotificationException`.
- `Shetabit\Sms\Sms::getDriver()` and the `Shetabit\Sms\Sms::SERVICE_NAME` constant.
- `Shetabit\Sms\Providers\SmsServiceProvider::configPath()`, the `sms-config` publish tag next to `config`, and a
  container alias so that `Shetabit\Sms\Sms` can be injected by class name.
- The shipped configuration is merged into the application configuration, so `config('sms')` is readable without
  publishing the file first.
- `guzzlehttp/guzzle` as a dependency. Five drivers use it, but it was never required.
- A `suggest` section that names the SDK each driver needs, and the SDKs themselves as dev dependencies so that every
  driver is covered by the test suite.
- GitHub Actions workflows for the test suite (PHP 8.4/8.5 × Laravel 12/13 × lowest/highest dependencies, plus a
  coverage gate), the coding style and the static analysis.
- `Dockerfile`, `Makefile` and `.dockerignore` to run the test suite and the checks without a PHP installation on the
  machine.
- `phpcs.xml.dist`, `phpstan.neon.dist` (level 7 with larastan), `rector.php` and the `composer analyse`,
  `composer test-coverage`, `composer rector` and `composer ci` scripts.

### Changed

- **Breaking:** the package requires `PHP 8.4+` and supports `Laravel 12` and `Laravel 13`. Support for PHP 7.2 and
  Laravel 5 to 8 is gone.
- **Breaking:** a custom driver implements `protected function sendTo(string $recipient) : mixed` instead of
  `public function send()`, has to call `parent::__construct($settings)` and must no longer redeclare the `$settings`
  property that `Shetabit\Sms\Abstracts\Driver` declares itself.
- **Breaking:** `Shetabit\Sms\Contracts\Driver::to()` and `message()` return `static` instead of `self`, and `send()`
  is declared to return `mixed`.
- **Breaking:** `Shetabit\Sms\Contracts\Message` declares `useTemplateIfSupports()`, `usesTemplate()` and
  `getTemplate()`. A custom message class has to implement them.
- **Breaking:** `Shetabit\Sms\Sms::send()` returns the answer of the driver, where it used to return nothing at all.
- **Breaking:** the package throws its own exceptions instead of a bare `\Exception`: `DriverNotFoundException` for a
  mapped class that is not a driver, `InvalidNotificationException` from the notification channel and
  `SendingFailedException` from the smsir driver.
- **Breaking:** `Shetabit\Sms\Message::getTemplate()` returns `params` as an array when no template is used, where it
  used to return `null`.
- **Breaking:** `Shetabit\Sms\Facades\Sms::getFacadeAccessor()` is public and typed, following the parent class.
- **Breaking:** the shipped url of the farazsms driver carries its scheme and the one of the smsir driver no longer
  carries a trailing slash. A published `config/sms.php` from an earlier version keeps working — the smsir driver
  trims the slash itself — but is worth bringing in line.
- Every driver builds its gateway client in a `createClient()` method of its own instead of inline in the constructor.
- The tsms driver builds its SOAP client on the first send and reuses it, instead of downloading the wsdl document of
  the gateway on every call to `send()`, and draws its message id with `random_int()` instead of `rand()`.
- The smsir driver fetches its token once and reuses it for every recipient.
- The twilio driver goes through the `$client->messages` shortcut of the SDK instead of `$client->account->messages`.
- The whole of `src/` carries parameter, return and property types, and passes PHPStan at level 7.

### Fixed

- `Shetabit\Sms\Sms::recipients()` called itself instead of `to()`, so every use of it ended in an infinite recursion
  that took the process down with a stack overflow.
- `Shetabit\Sms\Sms::send()` threw the answer of the driver away, leaving the caller with no way to tell what the
  gateway had replied.
- The linkmobility driver ran the sender name through `urlencode()` on top of the form encoding Guzzle already does,
  so a sender named `Acme Ltd` reached the gateway as `Acme+Ltd`.
- The farazsms driver was shipped with a url without a scheme, which Guzzle reads as a relative reference — the
  request never reached the gateway.
- The smsir driver pasted its path onto a url that ends in a slash, and asked for `https://ws.sms.ir//api/Token`.
- The smsgatewayme driver handed a single `SendMessageRequest` to `sendMessages()`, which reads a list of them, so
  nothing was delivered.
- The smsgatewayme driver wrote its api token into the global default `Configuration` of the SDK, from where it leaked
  into every other client the application builds with that SDK.
- The sns driver was shipped with `Tansactional` as its message type. Amazon knows `Transactional` and `Promotional`,
  so the transactional delivery the setting asks for was never the one that was used.
- A notification without a `toSms()` method died with `Call to undefined method`, instead of saying what is missing.

### Removed

- **Breaking:** `illuminate/broadcasting` is no longer a dependency, and `ext-soap` moved out of `require` — only the
  tsms driver needs it, and it is suggested instead.
- `Shetabit\Sms\Sms::getFreshDriverInstance()` and `validateMessage()`, and the `$driverInstance` property that was
  read but never written.
- `.travis.yml` and `.styleci.yml`, replaced by the GitHub Actions workflows.

## Date - 2020-01-09

### Fixed
- Nothing

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing
