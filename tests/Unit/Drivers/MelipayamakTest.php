<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Melipayamak\MelipayamakApi;
use ReflectionProperty;
use Shetabit\Sms\Drivers\Melipayamak;
use Shetabit\Sms\Tests\Fakes\FakeMelipayamakApi;

final class MelipayamakTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'melipayamak';
    }

    protected function driverClass() : string
    {
        return Melipayamak::class;
    }

    public function testItSendsTheMessageWithTheConfiguredSender() : void
    {
        $driver = $this->driver(['from' => '30001']);
        $this->swapClient($driver, $client = new FakeMelipayamakApi());

        $this->send($driver, ['09120000001']);

        $this->assertSame(
            [['to' => '09120000001', 'from' => '30001', 'text' => 'the message', 'isFlash' => false]],
            $client->rest->sent
        );
    }

    public function testItSendsOneMessagePerRecipient() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeMelipayamakApi());

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(
            ['09120000001', '09120000002'],
            array_column($client->rest->sent, 'to')
        );
    }

    public function testAsFlashTurnsTheFlashFlagOn() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeMelipayamakApi());

        $driver->asFlash();
        $this->send($driver);

        $this->assertTrue($client->rest->sent[0]['isFlash']);
    }

    public function testItReturnsTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, new FakeMelipayamakApi());

        $this->assertSame('{"Value":"melipayamak-id","RetStatus":1}', $this->send($driver));
    }

    public function testItBuildsItsClientWithTheConfiguredCredentials() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(MelipayamakApi::class, $client);
        $this->assertSame('the-user', new ReflectionProperty($client, 'username')->getValue($client));
        $this->assertSame('the-password', new ReflectionProperty($client, 'password')->getValue($client));
    }
}
