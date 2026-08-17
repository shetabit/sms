<?php

namespace Shetabit\Sms\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Shetabit\Sms\Message;
use Shetabit\Sms\Providers\SmsServiceProvider;
use Shetabit\Sms\Facades\Sms as SmsFacade;
use Shetabit\Sms\Sms;
use Shetabit\Sms\Tests\Fakes\FakeDriver;

abstract class FeatureTestCase extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        FakeDriver::forget();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app) : array
    {
        return [SmsServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app) : array
    {
        return ['Sms' => SmsFacade::class];
    }

    /**
     * Add a driver to the configuration of the booted application.
     *
     * @param array<string, mixed> $settings
     */
    protected function register(string $name, string $class, array $settings = ['from' => '30001']) : void
    {
        config()->set("sms.drivers.$name", $settings);
        config()->set("sms.map.$name", $class);
    }

    protected function fakeDriverIsConfigured(bool $asDefault = true) : void
    {
        $this->register('fake', FakeDriver::class);

        if ($asDefault) {
            config()->set('sms.default', 'fake');
        }
    }

    protected function manager() : Sms
    {
        return $this->app->make(Sms::class);
    }

    protected function message(string $text = 'the message') : Message
    {
        return new Message($text);
    }
}
