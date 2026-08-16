<?php

namespace Shetabit\Sms\Tests\Feature;

use ReflectionClass;
use Shetabit\Sms\Facades\Sms as SmsFacade;
use Shetabit\Sms\Sms;
use Shetabit\Sms\Tests\Fakes\FakeDriver;

final class SmsFacadeTest extends FeatureTestCase
{
    public function testItResolvesTheManager() : void
    {
        $this->assertInstanceOf(Sms::class, SmsFacade::getFacadeRoot());
        $this->assertSame(Sms::SERVICE_NAME, SmsFacade::getFacadeAccessor());
    }

    public function testItSendsThroughTheConfiguredDriver() : void
    {
        $this->fakeDriverIsConfigured();

        SmsFacade::to(['09120000001'])->message('the message')->send();

        $this->assertSame('09120000001', FakeDriver::$sent[0]['recipient']);
        $this->assertSame('the message', FakeDriver::$sent[0]['message']);
    }

    public function testItSwitchesTheDriverAtRuntime() : void
    {
        $this->fakeDriverIsConfigured(asDefault: false);
        $this->register('other', FakeDriver::class, ['from' => '40002']);

        SmsFacade::via('other')->to(['09120000001'])->message('the message')->send();

        $this->assertSame(['from' => '40002'], FakeDriver::$sent[0]['settings']);
    }

    public function testItIsAliasedToAShortName() : void
    {
        $this->assertTrue(class_exists('Sms'));
        $this->assertSame(SmsFacade::class, new ReflectionClass('Sms')->getName());
    }
}
