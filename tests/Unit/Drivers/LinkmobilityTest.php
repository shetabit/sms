<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Shetabit\Sms\Drivers\Linkmobility;

final class LinkmobilityTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'linkmobility';
    }

    protected function driverClass() : string
    {
        return Linkmobility::class;
    }

    public function testItPostsTheMessageAsAFormToTheConfiguredUrl() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password']);
        $this->fakeHttp($driver, [$this->response('OK')]);

        $this->send($driver, ['+4700000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame($this->settings()['url'], $this->requestUrl());
        $this->assertSame(
            [
                'USER' => 'the-user',
                'PW' => 'the-password',
                'RCV' => '+4700000001',
                'SND' => $this->settings()['sender'],
                'TXT' => 'the message',
            ],
            $this->requestForm()
        );
    }

    public function testItSendsTheSenderWithoutEncodingItTwice() : void
    {
        $driver = $this->driver(['sender' => 'Acme Ltd']);
        $this->fakeHttp($driver, [$this->response('OK')]);

        $this->send($driver);

        $this->assertSame('Acme Ltd', $this->requestForm()['SND']);
        $this->assertStringContainsString('SND=Acme+Ltd', $this->requestBody());
    }
}
