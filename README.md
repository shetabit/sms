<p align="center"><img src="resources/images/sms.png?raw=true"></p>


# Laravel Sms Gateway

[![Software License][ico-license]](LICENSE.md)
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads on Packagist][ico-download]][link-packagist]
[![Tests][ico-tests]][link-tests]
[![Code Style][ico-code-style]][link-code-style]
[![Static Analysis][ico-static-analysis]][link-static-analysis]
[![Code Coverage][ico-coverage]][link-coverage]

This is a Laravel package for sms gateway integration. This package requires `PHP 8.4+` and supports `Laravel 12` and
`Laravel 13`.

[Donate me](https://yekpay.me/mahdikhanzadi) if you like this package :sunglasses: :bowtie:

> This package works with multiple drivers, and you can create custom drivers if you can't find them in the
> [current drivers list](#list-of-available-drivers) below.

# List of contents

- [List of available drivers](#list-of-available-drivers)
- [Install](#install)
- [Configure](#configure)
- [How to use](#how-to-use)
  - [Send a message](#send-a-message)
  - [Choose a driver at runtime](#choose-a-driver-at-runtime)
  - [Change the settings of a driver at runtime](#change-the-settings-of-a-driver-at-runtime)
  - [Send to several recipients at once](#send-to-several-recipients-at-once)
  - [Use a template of the gateway](#use-a-template-of-the-gateway)
  - [Notifications](#notifications)
  - [Create custom drivers](#create-custom-drivers)
- [Testing](#testing)
- [Change log](#change-log)
- [Contributing](#contributing)
- [Security](#security)
- [Credits](#credits)
- [License](#license)

# List of available drivers

| Driver | Gateway | Needs |
| --- | --- | --- |
| `clockwork` | [Clockwork](https://www.clockworksms.com/) | `composer require mediaburst/clockworksms` |
| `farazsms` | [Faraz SMS](https://farazsms.com/) | — |
| `kavenegar` | [Kavenegar](https://kavenegar.com/) | `composer require kavenegar/php` |
| `linkmobility` | [LINK Mobility](https://www.linkmobility.com/) | — |
| `melipayamak` | [Melipayamak](https://www.melipayamak.com/) | `composer require melipayamak/php` |
| `smsgatewayme` | [SMSGateway.me](https://smsgateway.me/) | `composer require smsgatewayme/client` |
| `smsir` | [SMS.ir](https://sms.ir/) | — |
| `sns` | [Amazon SNS](https://aws.amazon.com/sns/) | `composer require aws/aws-sdk-php` |
| `textlocal` | [Textlocal](https://www.textlocal.in/) | — |
| `tsms` | [TSMS](http://tsms.ir/) | `ext-soap` |
| `twilio` | [Twilio](https://www.twilio.com/) | `composer require twilio/sdk` |

Only the drivers you actually use have to be installed — the package itself pulls none of the gateway SDKs in.

## Install

Via Composer

``` bash
$ composer require shetabit/sms
```

The service provider and the `Sms` facade are registered by package discovery, so there is nothing to add to
`config/app.php`.

## Configure

Publish the configuration file:

``` bash
$ php artisan vendor:publish --tag=sms-config
```

The published `config/sms.php` holds three things: the driver that is used when none is named, the settings of every
driver and the class each driver name maps to.

``` php
return [
    'default' => 'textlocal',

    'drivers' => [
        'kavenegar' => [
            'apiKey' => 'Your Api Key',
            'from' => 'Your Default From Number',
        ],
        // ...
    ],

    'map' => [
        'kavenegar' => \Shetabit\Sms\Drivers\Kavenegar::class,
        // ...
    ],
];
```

Publishing is optional. The shipped configuration is merged in, so `config('sms.drivers')` is readable right after
installation and your own `config/sms.php` only has to carry the keys you want to change.

## How to use

### Send a message

``` php
use Shetabit\Sms\Facades\Sms;

Sms::to(['09xxxxxxxxx'])
    ->message('Hello world')
    ->send();
```

`send()` returns the answer of the gateway: for a single recipient the bare answer, for several of them a `Collection`
keyed by recipient number.

### Choose a driver at runtime

``` php
Sms::via('kavenegar')
    ->to(['09xxxxxxxxx'])
    ->message('Hello world')
    ->send();
```

### Change the settings of a driver at runtime

``` php
Sms::via('kavenegar')
    ->config('from', '10004346')
    ->to(['09xxxxxxxxx'])
    ->message('Hello world')
    ->send();
```

Several settings can be handed over at once:

``` php
Sms::via('kavenegar')->config(['from' => '10004346', 'apiKey' => '...']);
```

`config()` overwrites the settings of the driver that is currently selected, so call it after `via()`.

### Send to several recipients at once

``` php
$answers = Sms::to(['09xxxxxxxx1', '09xxxxxxxx2'])
    ->message('Hello world')
    ->send();

$answers->get('09xxxxxxxx1');
```

### Use a template of the gateway

Gateways that deliver from a template of their own — Kavenegar's `VerifyLookup` for instance — are addressed through
the message:

``` php
use Shetabit\Sms\Facades\Sms;
use Shetabit\Sms\Message;

$message = new Message('Hello world');
$message->useTemplateIfSupports('registration-verify', ['token' => '12345']);

Sms::via('kavenegar')->to(['09xxxxxxxxx'])->message($message)->send();
```

Drivers that do not know templates ignore them and send the plain text instead.

### Notifications

The package ships a notification channel. Let `via()` return it and build the message in a `toSms()` method:

``` php
use Illuminate\Notifications\Notification;
use Shetabit\Sms\Channels\SmsChannel;
use Shetabit\Sms\Facades\Sms;

class OrderShipped extends Notification
{
    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return Sms::to([$notifiable->mobile])->message('Your order has been shipped.');
    }
}
```

`toSms()` has to hand back a `Shetabit\Sms\Sms` instance, or `null` to send nothing at all.

### Create custom drivers

Add the settings of your driver to the `drivers` array and its class to the `map` array of `config/sms.php`:

``` php
'drivers' => [
    'my_driver' => [
        'apiKey' => '...',
        'from' => '...',
    ],
],

'map' => [
    'my_driver' => \App\Sms\MyDriver::class,
],
```

The class has to extend `Shetabit\Sms\Abstracts\Driver` and implement `sendTo()`, which delivers the message to one
recipient and returns the answer of the gateway. Walking the list of recipients is the job of the abstract driver:

``` php
namespace App\Sms;

use Shetabit\Sms\Abstracts\Driver;

class MyDriver extends Driver
{
    protected function sendTo(string $recipient) : mixed
    {
        return $this->client->send([
            'apiKey' => $this->setting('apiKey'),
            'from' => $this->setting('from'),
            'to' => $recipient,
            'text' => $this->message->toString(),
        ]);
    }
}
```

`setting()` and `booleanSetting()` read a single value out of the settings the driver was given.

## Testing

Every pull request and every push to `master` is checked by [GitHub Actions][link-actions]: the test suite runs on
PHP 8.4 and 8.5, against Laravel 12 and 13 and against both the lowest and the highest supported dependencies, the
coding style is checked with PHP_CodeSniffer, the sources are analysed with PHPStan and the code coverage of the test
suite is measured and has to stay above 95%.

No test talks to a gateway. The driver tests answer the HTTP calls of a driver from a queue of prepared responses, and
the drivers that go through an SDK of their own are given a fake of that SDK — see `tests/Unit/Drivers/DriverTestCase.php`
and `tests/Fakes/`. The feature tests boot a real Laravel application with Testbench and send through a driver that
records what it was asked to deliver.

You can run the same checks locally. With PHP and Composer installed on your machine:

```bash
composer install

composer test           # run the test suite
composer test-coverage  # run the test suite and report code coverage
composer check-style    # check the coding style
composer fix-style      # fix the coding style where possible
composer analyse        # run static analysis
composer ci             # run all of the checks above
```

If you would rather not install PHP on your machine, the shipped `Dockerfile` and `Makefile` run everything inside a
container:

```bash
make test              # run the test suite
make coverage          # run the test suite and report code coverage
make check-style       # check the coding style
make fix-style         # fix the coding style where possible
make analyse           # run static analysis
make ci                # run all of the checks above
make shell             # open a shell inside the container
make help              # list every available target
```

Another PHP version can be used with `make test PHP_VERSION=8.5`, and a single Laravel version with
`make test-laravel LARAVEL=12`.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email khanzadimahdi@gmail.com instead of using the issue tracker.

## Credits

- [Mahdi khanzadi][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/shetabit/sms.svg?style=flat-square
[ico-download]: https://img.shields.io/packagist/dt/shetabit/sms.svg?color=%23F18&style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/shetabit/sms/tests.yml?branch=master&label=Tests&style=flat-square
[ico-code-style]: https://img.shields.io/github/actions/workflow/status/shetabit/sms/code-style.yml?branch=master&label=Code%20Style&style=flat-square
[ico-static-analysis]: https://img.shields.io/github/actions/workflow/status/shetabit/sms/static-analysis.yml?branch=master&label=Static%20Analysis&style=flat-square
[ico-coverage]: https://img.shields.io/codecov/c/github/shetabit/sms/master?label=Coverage&style=flat-square

[link-packagist]: https://packagist.org/packages/shetabit/sms
[link-actions]: https://github.com/shetabit/sms/actions
[link-tests]: https://github.com/shetabit/sms/actions/workflows/tests.yml
[link-code-style]: https://github.com/shetabit/sms/actions/workflows/code-style.yml
[link-static-analysis]: https://github.com/shetabit/sms/actions/workflows/static-analysis.yml
[link-coverage]: https://codecov.io/gh/shetabit/sms
[link-author]: https://github.com/khanzadimahdi
[link-contributors]: ../../contributors
