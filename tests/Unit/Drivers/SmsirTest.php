<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Drivers\Smsir;

final class SmsirTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'smsir';
    }

    protected function driverClass() : string
    {
        return Smsir::class;
    }

    public function testItSendsAPlainMessageThroughTheBulkEndpoint() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'from' => '30001']);
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 1])]);

        $answer = $this->send($driver, ['09120000001']);

        $this->assertInstanceOf(ResponseInterface::class, $answer);
        $this->assertSame(1, $this->requestCount());
        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://api.sms.ir/v1/send/bulk', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('X-API-KEY'));
        $this->assertSame('application/json', $this->request()->getHeaderLine('Accept'));
        $this->assertSame(
            ['lineNumber' => '30001', 'messageText' => 'the message', 'mobiles' => ['09120000001']],
            $this->requestJson()
        );
    }

    public function testItSendsAVerifyMessageWhenTheMessageUsesATemplate() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key']);
        $this->fakeHttp($driver, [$this->jsonResponse(['status' => 1])]);

        $message = $this->message()->useTemplateIfSupports('123456', ['Code' => '12345', 'Name' => 'the name']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame('https://api.sms.ir/v1/send/verify', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('X-API-KEY'));
        $this->assertSame(
            [
                'mobile' => '09120000001',
                'templateId' => 123456,
                'parameters' => [
                    ['name' => 'Code', 'value' => '12345'],
                    ['name' => 'Name', 'value' => 'the name'],
                ],
            ],
            $this->requestJson()
        );
    }

    public function testItSendsTheParametersOfTheTemplateAsStrings() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver, ['09120000001'], $this->message()->useTemplateIfSupports(123456, ['Code' => 12345]));

        $this->assertSame([['name' => 'Code', 'value' => '12345']], $this->requestJson()['parameters']);
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://api.sms.ir/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://api.sms.ir/v1/send/bulk', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame(['09120000001'], $this->requestJson(0)['mobiles']);
        $this->assertSame(['09120000002'], $this->requestJson(1)['mobiles']);
    }

    public function testASecretKeyLeftInTheSettingsDoesNotBringTheLegacyApiBack() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'secretKey' => 'the-secret']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame(1, $this->requestCount());
        $this->assertSame('https://api.sms.ir/v1/send/bulk', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('X-API-KEY'));
    }
}
