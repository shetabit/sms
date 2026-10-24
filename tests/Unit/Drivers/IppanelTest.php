<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Shetabit\Sms\Drivers\Ippanel;

final class IppanelTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'ippanel';
    }

    protected function driverClass() : string
    {
        return Ippanel::class;
    }

    public function testItSendsAPlainMessageThroughTheWebservice() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'from' => '+983000505']);
        $this->fakeHttp($driver, [$this->jsonResponse(['data' => ['message_outbox_ids' => [1]]])]);

        $this->send($driver, ['+989120000001']);

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://edge.ippanel.com/v1/api/send', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('Authorization'));
        $this->assertSame(
            [
                'sending_type' => 'webservice',
                'from_number' => '+983000505',
                'message' => 'the message',
                'params' => ['recipients' => ['+989120000001']],
            ],
            $this->requestJson()
        );
    }

    public function testItSendsAPatternWhenTheMessageUsesATemplate() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key', 'from' => '+983000505']);
        $this->fakeHttp($driver, [$this->jsonResponse(['data' => ['message_outbox_ids' => [1]]])]);

        $message = $this->message()->useTemplateIfSupports('the-pattern', ['name' => 'the name', 'code' => '12345']);

        $this->send($driver, ['+989120000001'], $message);

        $this->assertSame('https://edge.ippanel.com/v1/api/send', $this->requestUrl());
        $this->assertSame('the-api-key', $this->request()->getHeaderLine('Authorization'));
        $this->assertSame(
            [
                'sending_type' => 'pattern',
                'from_number' => '+983000505',
                'code' => 'the-pattern',
                'recipients' => ['+989120000001'],
                'params' => ['name' => 'the name', 'code' => '12345'],
            ],
            $this->requestJson()
        );
    }

    public function testItSendsTheCodeOfThePatternAsAString() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver, ['+989120000001'], $this->message()->useTemplateIfSupports(7, ['code' => '12345']));

        $this->assertSame('7', $this->requestJson()['code']);
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://edge.ippanel.com/v1/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://edge.ippanel.com/v1/api/send', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['+989120000001', '+989120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame(['recipients' => ['+989120000001']], $this->requestJson(0)['params']);
        $this->assertSame(['recipients' => ['+989120000002']], $this->requestJson(1)['params']);
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['data' => ['message_outbox_ids' => [1123544244]], 'meta' => ['status' => true]]),
        ]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertSame([1123544244], $answer->data->message_outbox_ids);
        $this->assertTrue($answer->meta->status);
    }
}
