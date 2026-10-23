<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use DateTimeImmutable;
use DateTimeZone;
use Shetabit\Sms\Drivers\Amootsms;

final class AmootsmsTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'amootsms';
    }

    protected function driverClass() : string
    {
        return Amootsms::class;
    }

    public function testItSendsAPlainMessageThroughSendSimple() : void
    {
        $driver = $this->driver(['token' => 'the-token', 'from' => '50002710000000']);
        $this->fakeHttp($driver, [$this->jsonResponse(['Status' => 'Success'])]);

        $this->send($driver, ['09120000001']);

        $form = $this->requestForm();

        $this->assertSame('POST', $this->request()->getMethod());
        $this->assertSame('https://portal.amootsms.com/rest/SendSimple', $this->requestUrl());
        $this->assertSame('the-token', $this->request()->getHeaderLine('Authorization'));
        $this->assertSame(['SendDateTime', 'SMSMessageText', 'LineNumber', 'Mobiles'], array_keys($form));
        $this->assertSame('the message', $form['SMSMessageText']);
        $this->assertSame('50002710000000', $form['LineNumber']);
        $this->assertSame('09120000001', $form['Mobiles']);
    }

    public function testItAsksForTheMessageToBeSentRightAwayInTheTimeOfIran() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $sendAt = DateTimeImmutable::createFromFormat(DATE_ATOM, $this->requestForm()['SendDateTime']);

        $this->assertInstanceOf(DateTimeImmutable::class, $sendAt);
        $this->assertEqualsWithDelta(time(), $sendAt->getTimestamp(), 60);
        $this->assertSame(new DateTimeZone('Asia/Tehran')->getOffset($sendAt), $sendAt->getOffset());
    }

    public function testItSendsAPatternWhenTheMessageUsesATemplate() : void
    {
        $driver = $this->driver(['token' => 'the-token']);
        $this->fakeHttp($driver, [$this->jsonResponse(['Status' => 'Success'])]);

        $message = $this->message()->useTemplateIfSupports(1234, ['first', 'second']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame('https://portal.amootsms.com/rest/SendWithPattern', $this->requestUrl());
        $this->assertSame('the-token', $this->request()->getHeaderLine('Authorization'));
        $this->assertSame(
            ['Mobile' => '09120000001', 'PatternCodeID' => '1234', 'PatternValues' => 'first,second'],
            $this->requestForm()
        );
    }

    public function testItDoesNotDoubleTheSlashOfAConfiguredUrl() : void
    {
        $driver = $this->driver(['url' => 'https://portal.amootsms.com/rest/']);
        $this->fakeHttp($driver, [$this->jsonResponse([])]);

        $this->send($driver);

        $this->assertSame('https://portal.amootsms.com/rest/SendSimple', $this->requestUrl());
    }

    public function testItSendsOneRequestPerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [$this->jsonResponse([]), $this->jsonResponse([])]);

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertSame(2, $this->requestCount());
        $this->assertSame('09120000001', $this->requestForm(0)['Mobiles']);
        $this->assertSame('09120000002', $this->requestForm(1)['Mobiles']);
    }

    public function testItDecodesTheAnswerOfTheGateway() : void
    {
        $driver = $this->driver();
        $this->fakeHttp($driver, [
            $this->jsonResponse(['Status' => 'Success', 'Data' => [['Status' => 1, 'MessageID' => 123456]]]),
        ]);

        $answer = $this->send($driver);

        $this->assertIsObject($answer);
        $this->assertSame('Success', $answer->Status);
        $this->assertSame(123456, $answer->Data[0]->MessageID);
    }
}
