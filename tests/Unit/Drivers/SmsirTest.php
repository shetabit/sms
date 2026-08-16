<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

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

    public function testItFetchesATokenAndSendsTheMessageWithIt() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'secretKey' => 'the-secret', 'from' => '30001']);
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
        $driver = $this->driver(['url' => 'https://ws.sms.ir/']);
        $this->fakeHttp($driver, [$this->jsonResponse(['TokenKey' => 'the-token']), $this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://ws.sms.ir/api/Token', $this->requestUrl(0));
        $this->assertSame('https://ws.sms.ir/api/MessageSend', $this->requestUrl(1));
    }

    public function testItFetchesTheTokenOnlyOnceForSeveralRecipients() : void
    {
        $driver = $this->driver();
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
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse(['Message' => 'invalid credentials'])]);

        $this->expectException(SendingFailedException::class);
        $this->expectExceptionMessage('Smsir token could not be generated.');

        $this->send($driver);
    }
}
