<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Shetabit\Sms\Drivers\Asanak;

final class AsanakTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'asanak';
    }

    protected function driverClass() : string
    {
        return Asanak::class;
    }

    public function testItSendsAPlainMessageThroughSendSms() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password', 'from' => '9821000000']);
        $this->fakeHttp($driver, [$this->jsonResponse(['meta' => ['status' => 200]])]);

        $this->send($driver, ['09120000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://sms.asanak.ir/webservice/v2rest/sendsms', $this->requestUrl());
        $this->assertSame('application/json', $this->request()->getHeaderLine('Accept'));
        $this->assertSame(
            [
                'username' => 'the-user',
                'password' => 'the-password',
                'source' => '9821000000',
                'destination' => '09120000001',
                'message' => 'the message',
                'send_to_black_list' => 1,
            ],
            $this->requestJson()
        );
    }

    public function testItSendsATemplateWhenTheMessageUsesOne() : void
    {
        $driver = $this->driver(['username' => 'the-user', 'password' => 'the-password']);
        $this->fakeHttp($driver, [$this->jsonResponse(['meta' => ['status' => 200]])]);

        $message = $this->message()->useTemplateIfSupports('101', ['code' => '12345', 'name' => 'the name']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame('https://sms.asanak.ir/webservice/v2rest/template', $this->requestUrl());
        $this->assertSame(
            [
                'username' => 'the-user',
                'password' => 'the-password',
                'template_id' => 101,
                'parameters' => ['code' => '12345', 'name' => 'the name'],
                'destination' => '09120000001',
                'send_to_black_list' => 1,
            ],
            $this->requestJson()
        );
    }

    public function testItSkipsBlackListedNumbersWhenTheSettingsSaySo() : void
    {
        $driver = $this->driver(['sendToBlackList' => false]);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame(0, $this->requestJson()['send_to_black_list']);
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://sms.asanak.ir/webservice/v2rest/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://sms.asanak.ir/webservice/v2rest/sendsms', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame('09120000001', $this->requestJson(0)['destination']);
        $this->assertSame('09120000002', $this->requestJson(1)['destination']);
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['meta' => ['status' => 200, 'message' => 'OK'], 'data' => ['1234567']]),
        ]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertSame(200, $answer->meta->status);
        $this->assertSame(['1234567'], $answer->data);
    }
}
