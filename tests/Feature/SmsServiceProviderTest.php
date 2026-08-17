<?php

namespace Shetabit\Sms\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Shetabit\Sms\Providers\SmsServiceProvider;
use Shetabit\Sms\Sms;

final class SmsServiceProviderTest extends FeatureTestCase
{
    public function testItBindsTheManagerUnderItsServiceName() : void
    {
        $this->assertTrue($this->app->bound(Sms::SERVICE_NAME));
        $this->assertInstanceOf(Sms::class, $this->app->make(Sms::SERVICE_NAME));
    }

    public function testItAliasesTheManagerToItsClass() : void
    {
        $this->assertInstanceOf(Sms::class, $this->app->make(Sms::class));
    }

    public function testEveryResolutionGetsItsOwnManager() : void
    {
        $this->assertNotSame($this->app->make(Sms::class), $this->app->make(Sms::class));
    }

    public function testItMergesTheShippedConfiguration() : void
    {
        $shipped = require SmsServiceProvider::configPath();

        $this->assertSame($shipped['default'], config('sms.default'));
        $this->assertSame(array_keys($shipped['map']), array_keys(config('sms.map')));
        $this->assertSame($shipped['drivers'], config('sms.drivers'));
    }

    public function testTheApplicationKeepsItsOwnConfiguration() : void
    {
        config()->set('sms.default', 'kavenegar');

        $this->assertSame('kavenegar', config('sms.default'));
        $this->assertInstanceOf(Sms::class, $this->app->make(Sms::class));
        $this->assertSame('kavenegar', $this->app->make(Sms::class)->getDriver());
    }

    public function testEveryConfiguredDriverHasSettingsAndAClass() : void
    {
        foreach (array_keys(config('sms.map')) as $driver) {
            $this->assertIsArray(config("sms.drivers.$driver"), "The driver [$driver] has no settings.");
            $this->assertTrue(class_exists(config("sms.map.$driver")), "The driver [$driver] has no class.");
        }
    }

    public function testItOffersItsConfigurationForPublishing() : void
    {
        $paths = ServiceProvider::pathsToPublish(SmsServiceProvider::class, 'sms-config');

        $this->assertSame([SmsServiceProvider::configPath() => config_path('sms.php')], $paths);
        $this->assertSame($paths, ServiceProvider::pathsToPublish(SmsServiceProvider::class, 'config'));
    }
}
