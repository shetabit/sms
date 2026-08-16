<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use ReflectionProperty;
use Shetabit\Sms\Drivers\SmsGatewayMe;
use Shetabit\Sms\Tests\Fakes\FakeMessageApi;
use SMSGatewayMe\Client\Api\MessageApi;
use SMSGatewayMe\Client\Configuration;
use SMSGatewayMe\Client\Model\SendMessageRequest;

final class SmsGatewayMeTest extends DriverTestCase
{
    protected function driverName() : string
    {
        return 'smsgatewayme';
    }

    protected function driverClass() : string
    {
        return SmsGatewayMe::class;
    }

    public function testItSendsAListOfMessagesEvenForASingleRecipient() : void
    {
        // The endpoint reads a list. Handing it a bare request serialises an
        // object where the gateway expects an array and nothing is delivered.
        $driver = $this->driver(['from' => '4242']);
        $this->swapClient($driver, $client = new FakeMessageApi());

        $this->send($driver, ['+10000000001']);

        $this->assertCount(1, $client->sent);
        $this->assertIsArray($client->sent[0]);
        $this->assertCount(1, $client->sent[0]);

        $request = $client->sent[0][0];

        $this->assertInstanceOf(SendMessageRequest::class, $request);
        $this->assertSame('+10000000001', $request->getPhoneNumber());
        $this->assertSame('the message', $request->getMessage());
        $this->assertSame(4242, $request->getDeviceId());
    }

    public function testItSendsTheDeviceIdAsANumber() : void
    {
        $driver = $this->driver(['from' => '4242']);
        $this->swapClient($driver, $client = new FakeMessageApi());

        $this->send($driver);

        $this->assertIsInt($client->sent[0][0]->getDeviceId());
    }

    public function testItCallsTheGatewayOncePerRecipient() : void
    {
        $driver = $this->driver();
        $this->swapClient($driver, $client = new FakeMessageApi());

        $this->send($driver, ['+10000000001', '+10000000002']);

        $this->assertCount(2, $client->sent);
        $this->assertSame('+10000000002', $client->sent[1][0]->getPhoneNumber());
    }

    public function testItBuildsItsClientWithTheConfiguredApiToken() : void
    {
        $driver = $this->driver(['apiToken' => 'the-api-token']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(MessageApi::class, $client);
        $this->assertSame('the-api-token', $client->getApiClient()->getConfig()->getApiKey('Authorization'));
    }

    public function testItLeavesTheDefaultConfigurationOfTheSdkAlone() : void
    {
        // The sdk keeps one global default configuration. Writing the api token
        // into it leaks the credentials of a driver into every other client the
        // application builds from the same sdk.
        $this->driver(['apiToken' => 'the-api-token']);

        $this->assertNull(Configuration::getDefaultConfiguration()->getApiKey('Authorization'));
    }
}
