<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Shetabit\Sms\Drivers\Magfa;

final class MagfaTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'magfa';
    }

    protected function driverClass() : string
    {
        return Magfa::class;
    }

    public function testItPostsTheMessageToTheSendMethod() : void
    {
        $driver = $this->driver(['from' => '300012345']);
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 0, 'messages' => []])]);

        $this->send($driver, ['09120000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://sms.magfa.com/api/http/sms/v2/send', $this->requestUrl());
        $this->assertSame('application/json', $this->request()->getHeaderLine('Accept'));
        $this->assertSame(
            ['senders' => ['300012345'], 'recipients' => ['09120000001'], 'messages' => ['the message']],
            $this->requestJson()
        );
    }

    public function testItSignsInWithTheUsernameAndTheDomainOfTheAccount() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'domain' => 'the-domain', 'password' => 'the-password']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame(
            'Basic '.base64_encode('the-user/the-domain:the-password'),
            $this->request()->getHeaderLine('Authorization')
        );
    }

    public function testItSendsThePlainTextOfAMessageThatUsesATemplate() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver, ['09120000001'], $this->message()->useTemplateIfSupports('verify', ['code' => '12345']));

        $this->assertSame(['the message'], $this->requestJson()['messages']);
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://sms.magfa.com/api/http/sms/v2/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://sms.magfa.com/api/http/sms/v2/send', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame(['09120000001'], $this->requestJson(0)['recipients']);
        $this->assertSame(['09120000002'], $this->requestJson(1)['recipients']);
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['status' => 0, 'messages' => [['status' => 0, 'id' => 111111111]]]),
        ]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertSame(0, $answer->status);
        $this->assertSame(111111111, $answer->messages[0]->id);
    }
}
