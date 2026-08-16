<?php

namespace Shetabit\Sms\Tests\Feature;

use Illuminate\Support\Facades\Notification as NotificationDispatcher;
use Shetabit\Sms\Channels\SmsChannel;
use Shetabit\Sms\Exceptions\InvalidNotificationException;
use Shetabit\Sms\Tests\Fakes\FakeDriver;
use Shetabit\Sms\Tests\Fakes\FakeNotifiable;
use Shetabit\Sms\Tests\Fakes\SilentNotification;
use Shetabit\Sms\Tests\Fakes\SmsNotification;

final class SmsChannelTest extends FeatureTestCase
{
    public function testItDeliversANotificationThroughTheConfiguredDriver() : void
    {
        $this->fakeDriverIsConfigured();

        $sms = $this->manager()->to(['09120000001'])->message('the message');

        NotificationDispatcher::send(new FakeNotifiable(), new SmsNotification($sms));

        $this->assertSame(
            [['recipient' => '09120000001', 'message' => 'the message', 'settings' => ['from' => '30001']]],
            FakeDriver::$sent
        );
    }

    public function testItReturnsTheAnswerOfTheDriver() : void
    {
        $this->fakeDriverIsConfigured();

        $sms = $this->manager()->to(['09120000001'])->message('the message');

        $answer = $this->channel()->send(new FakeNotifiable(), new SmsNotification($sms));

        $this->assertIsArray($answer);
        $this->assertSame('09120000001', $answer['recipient']);
    }

    public function testANotificationThatSendsNothingIsSkipped() : void
    {
        $this->fakeDriverIsConfigured();

        $this->assertNull($this->channel()->send(new FakeNotifiable(), new SmsNotification()));
        $this->assertSame([], FakeDriver::$sent);
    }

    public function testANotificationWithoutAToSmsMethodIsRefused() : void
    {
        $this->expectException(InvalidNotificationException::class);
        $this->expectExceptionMessage(
            sprintf('[%s] must define a toSms() method to be sent over the sms channel.', SilentNotification::class)
        );

        $this->channel()->send(new FakeNotifiable(), new SilentNotification());
    }

    public function testANotificationThatDoesNotHandOverAManagerIsRefused() : void
    {
        $this->expectException(InvalidNotificationException::class);
        $this->expectExceptionMessage('Invalid data for sms notification.');

        $this->channel()->send(new FakeNotifiable(), new SmsNotification('the message'));
    }

    private function channel() : SmsChannel
    {
        return $this->app->make(SmsChannel::class);
    }
}
