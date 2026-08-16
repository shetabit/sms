<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Kavenegar\KavenegarApi;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use ReflectionProperty;
use Shetabit\Sms\Drivers\Kavenegar;
use Shetabit\Sms\Tests\Fakes\FakeKavenegarApi;

// The sdk of the gateway assigns its api key to a property it never declares,
// which every construction of a client reports as a deprecation.
#[IgnoreDeprecations]
final class KavenegarTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'kavenegar';
    }

    protected function driverClass() : string
    {
        return Kavenegar::class;
    }

    public function testItSendsAPlainMessageWithTheConfiguredSender() : void
    {
        $driver = $this->driver(['from' => '30001']);
        $this->swapClient($driver, $client = new FakeKavenegarApi('the-api-key'));

        $this->send($driver, ['09120000001']);

        $this->assertCount(1, $client->calls);
        $this->assertSame('Send', $client->calls[0]['method']);
        $this->assertSame(['30001', '09120000001', 'the message'], $client->calls[0]['arguments']);
    }

    public function testItLooksTheTemplateUpWhenTheMessageUsesOne() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeKavenegarApi('the-api-key'));

        $message = $this->message()->useTemplateIfSupports('verify', ['first', 'second', 'third']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame('VerifyLookup', $client->calls[0]['method']);
        $this->assertSame(
            ['09120000001', 'first', 'second', 'third', 'verify'],
            $client->calls[0]['arguments']
        );
    }

    public function testItTakesTheTemplateTokensInOrderWhateverTheyAreKeyedBy() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeKavenegarApi('the-api-key'));

        $message = $this->message()->useTemplateIfSupports(7, ['code' => 'first', 'name' => 'second']);

        $this->send($driver, ['09120000001'], $message);

        $this->assertSame(
            ['09120000001', 'first', 'second', null, 7],
            $client->calls[0]['arguments']
        );
    }

    public function testItCallsTheGatewayOncePerRecipient() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeKavenegarApi('the-api-key'));

        $this->send($driver, ['09120000001', '09120000002']);

        $this->assertCount(2, $client->calls);
        $this->assertSame('09120000002', $client->calls[1]['arguments'][1]);
    }

    public function testItBuildsItsClientWithTheConfiguredApiKey() : void
    {
        $driver = $this->driver(['apiKey' => 'the-api-key']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(KavenegarApi::class, $client);
        $this->assertSame('the-api-key', $client->apiKey);
    }
}
