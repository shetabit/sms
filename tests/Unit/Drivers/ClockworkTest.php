<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use mediaburst\ClockworkSMS\Clockwork as ClockworkClient;
use ReflectionProperty;
use Shetabit\Sms\Drivers\Clockwork;
use Shetabit\Sms\Tests\Fakes\FakeClockworkClient;

final class ClockworkTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'clockwork';
    }

    protected function driverClass() : string
    {
        return Clockwork::class;
    }

    public function testItSendsOneMessagePerRecipient() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeClockworkClient('the-key'));

        $this->send($driver, ['+447700900001', '+447700900002']);

        $this->assertSame(
            [
                ['to' => '+447700900001', 'message' => 'the message'],
                ['to' => '+447700900002', 'message' => 'the message'],
            ],
            $client->sent
        );
    }

    public function testItReturnsTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, new FakeClockworkClient('the-key'));

        $answer = $this->send($driver, ['+447700900001']);

        $this->assertSame('clockwork-id', $answer['id']);
    }

    public function testItBuildsItsClientWithTheConfiguredKey() : void
    {
        $driver = $this->driver(['key' => 'the-key']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(ClockworkClient::class, $client);
        $this->assertSame('the-key', $client->key);
    }
}
