<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use GuzzleHttp\Psr7\Uri;
use Shetabit\Sms\Drivers\Farazsms;

final class FarazsmsTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'farazsms';
    }

    protected function driverClass() : string
    {
        return Farazsms::class;
    }

    public function testItPostsTheMessageAsAFormToTheConfiguredUrl() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password', 'from' => '+983000']);
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 'ok'])]);

        $this->send($driver, ['+989120000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame($this->settings()['url'], $this->requestUrl());
        $this->assertSame(
            [
                'uname' => 'the-user',
                'pass' => 'the-password',
                'from' => '+983000',
                'message' => 'the message',
                'to' => '["+989120000001"]',
                'op' => 'send',
            ],
            $this->requestForm()
        );
    }

    public function testItSendsItsRequestToAnAbsoluteUrl() : void
    {
        // Guzzle turns a url without a scheme into a relative reference, which
        // never reaches the gateway.
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $uri = new Uri($this->requestUrl());

        $this->assertNotSame('', $uri->getScheme());
        $this->assertNotSame('', $uri->getHost());
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 'ok', 'id' => 12345])]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertSame('ok', $answer->status);
        $this->assertSame(12345, $answer->id);
    }
}
