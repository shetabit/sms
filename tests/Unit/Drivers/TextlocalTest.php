<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Drivers\Textlocal;

final class TextlocalTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'textlocal';
    }

    protected function driverClass() : string
    {
        return Textlocal::class;
    }

    public function testItPostsTheMessageAsAFormToTheConfiguredUrl() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'hash' => 'the-hash']);
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 'success'])]);

        $this->send($driver, ['+447700900001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame($this->settings()['url'], $this->requestUrl());
        $this->assertSame(
            [
                'username' => 'the-user',
                'hash' => 'the-hash',
                'numbers' => '+447700900001',
                'sender' => $this->settings()['sender'],
                'message' => 'the message',
            ],
            $this->requestForm()
        );
    }

    public function testItSendsTheSenderWithoutEncodingItTwice() : void
    {
        $driver = $this->driver(['sender' => 'Acme Ltd']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('Acme Ltd', $this->requestForm()['sender']);
        $this->assertStringContainsString('sender=Acme+Ltd', $this->requestBody());
    }

    public function testASingleRecipientGetsTheBareAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 'success'])]);

        $this->assertInstanceOf(ResponseInterface::class, $this->send($driver, ['+447700900001']));
    }

    public function testSeveralRecipientsGetACollectionKeyedByRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $answer = $this->send($driver, ['+447700900001', '+447700900002']);

        $this->assertInstanceOf(Collection::class, $answer);
        $this->assertSame(['+447700900001', '+447700900002'], $answer->keys()->all());
        $this->assertSame(2, $this->requestCount());
    }
}
