<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Drivers\Smsir;
use Shetabit\Sms\Exceptions\SendingFailedException;

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

    /**
     * A driver set up for the legacy api of the gateway, the way a configuration published from an earlier version is.
     *
     * @param array<string, mixed> $settings
     */
    protected function legacyDriver(array $settings = []) : Driver
    {
        return $this->driver(array_merge(['url' => 'https://ws.sms.ir', 'secretKey' => 'the-secret'], $settings));
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

    public function testItDoesNotDoubleTheSlashOfTheUrlOfTheCurrentApi() : void
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

    public function testItFetchesATokenAndSendsTheMessageWithIt() : void
    {
        $driver = $this->legacyDriver(['apiKey' => 'the-api-key', 'secretKey' => 'the-secret', 'from' => '30001']);
        $this->fakeHttp($driver, [
            $this->jsonResponse(['TokenKey' => 'the-token']),
            $this->jsonResponse(['IsSuccessful' => true]),
        ]);

        $this->send($driver, ['09120000001']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame(['UserApiKey' => 'the-api-key', 'SecretKey' => 'the-secret'], $this->requestJson(0));
        $this->assertSame(
            ['Messages' => ['the message'], 'MobileNumbers' => ['09120000001'], 'LineNumber' => '30001'],
            $this->requestJson(1)
        );
        $this->assertSame('the-token', $this->request(1)->getHeaderLine('x-sms-ir-secure-token'));
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->legacyDriver(['url' => 'https://ws.sms.ir/']);
        $this->fakeHttp($driver, [$this->jsonResponse(['TokenKey' => 'the-token']), $this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://ws.sms.ir/api/Token', $this->requestUrl(0));
        $this->assertSame('https://ws.sms.ir/api/MessageSend', $this->requestUrl(1));
    }

    public function testItFetchesTheTokenOnlyOnceForSeveralRecipients() : void
    {
        $driver = $this->legacyDriver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['TokenKey' => 'the-token']),
            $this->jsonResponse([]),
            $this->jsonResponse([]),
        ]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(3, $this->requestCount());
        $this->assertStringEndsWith('/api/Token', $this->requestUrl(0));
        $this->assertStringEndsWith('/api/MessageSend', $this->requestUrl(1));
        $this->assertStringEndsWith('/api/MessageSend', $this->requestUrl(2));
    }

    public function testItFailsWhenTheGatewayHandsOutNoToken() : void
    {
        $driver = $this->legacyDriver();
        $this->fakeHttp($driver, [$this->jsonResponse(['Message' => 'invalid credentials'])]);

        $this->expectException(SendingFailedException::class);
        $this->expectExceptionMessage('Smsir token could not be generated.');

        $this->send($driver);
    }

    public function testTheLegacyApiSendsThePlainTextOfAMessageThatUsesATemplate() : void
    {
        $driver = $this->legacyDriver();
        $this->fakeHttp($driver, [$this->jsonResponse(['TokenKey' => 'the-token']), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001'], $this->message()->useTemplateIfSupports(123456, ['Code' => '12345']));

        $this->assertStringEndsWith('/api/MessageSend', $this->requestUrl(1));
        $this->assertSame(['the message'], $this->requestJson(1)['Messages']);
    }
}
