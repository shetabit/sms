<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Shetabit\Sms\Drivers\Ghasedak;

final class GhasedakTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'ghasedak';
    }

    protected function driverClass() : string
    {
        return Ghasedak::class;
    }

    public function testItSendsAPlainMessageAsASingleSms() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'from' => '30005006']);
        $this->fakeHttp($driver, [$this->jsonResponse(['isSuccess' => true])]);

        $this->send($driver, ['09120000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://gateway.ghasedak.me/rest/api/v1/WebService/SendSingleSMS', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('ApiKey'));
        $this->assertSame(
            ['lineNumber' => '30005006', 'receptor' => '09120000001', 'message' => 'the message'],
            $this->requestJson()
        );
    }

    public function testItSendsAnOtpWhenTheMessageUsesATemplate() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key']);
        $this->fakeHttp($driver, [$this->jsonResponse(['isSuccess' => true])]);

        $message = $this->message()->useTemplateIfSupports('the-template', ['Code' => '12345', 'Name' => 'the name']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame('https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('ApiKey'));
        $this->assertSame(
            [
                'receptors' => [['mobile' => '09120000001']],
                'templateName' => 'the-template',
                'inputs' => [
                    ['param' => 'Code', 'value' => '12345'],
                    ['param' => 'Name', 'value' => 'the name'],
                ],
            ],
            $this->requestJson()
        );
    }

    public function testItSendsTheInputsOfTheTemplateAsStrings() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver, ['09120000001'], $this->message()->useTemplateIfSupports(7, ['Code' => 12345]));

        $this->assertSame('7', $this->requestJson()['templateName']);
        $this->assertSame([['param' => 'Code', 'value' => '12345']], $this->requestJson()['inputs']);
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://gateway.ghasedak.me/rest/api/v1/WebService/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://gateway.ghasedak.me/rest/api/v1/WebService/SendSingleSMS', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame('09120000001', $this->requestJson(0)['receptor']);
        $this->assertSame('09120000002', $this->requestJson(1)['receptor']);
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['isSuccess' => true, 'statusCode' => 200, 'data' => ['messageId' => '2387933']]),
        ]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertTrue($answer->isSuccess);
        $this->assertSame('2387933', $answer->data->messageId);
    }
}
