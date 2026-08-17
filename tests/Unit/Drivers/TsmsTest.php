<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use ReflectionProperty;
use Shetabit\Sms\Drivers\Tsms;
use Shetabit\Sms\Tests\Fakes\FakeSoapClient;

final class TsmsTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'tsms';
    }

    protected function driverClass() : string
    {
        return Tsms::class;
    }

    public function testItDoesNotBuildItsSoapClientBeforeItSends() : void
    {
        // Building the client downloads the wsdl document of the gateway, which
        // must not happen while the manager is only picking a driver.
        $this->assertNull(new ReflectionProperty($this->driver(), 'client')->getValue($this->driver()));
    }

    public function testItCallsSendSmsWithTheConfiguredCredentials() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password', 'from' => '30001']);
        $this->swapClient($driver, $client = new FakeSoapClient());

        $this->send($driver, ['09120000001']);

        $this->assertCount(1, $client->calls);
        $this->assertSame('sendSms', $client->calls[0]['name']);

        [$username, $password, $from, $to, $messages, $flash] = $client->calls[0]['arguments'];

        $this->assertSame('the-user', $username);
        $this->assertSame('the-password', $password);
        $this->assertSame(['30001'], $from);
        $this->assertSame(['09120000001'], $to);
        $this->assertSame(['the message'], $messages);
        $this->assertSame([], $flash);
        $this->assertIsInt($client->calls[0]['arguments'][6]);
    }

    public function testItCallsTheGatewayOncePerRecipient() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeSoapClient());

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertCount(2, $client->calls);
        $this->assertSame(['09120000002'], $client->calls[1]['arguments'][3]);
    }
}
