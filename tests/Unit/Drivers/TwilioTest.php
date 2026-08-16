<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use ReflectionProperty;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Drivers\Twilio;
use Shetabit\Sms\Tests\Fakes\RecordingTwilioHttpClient;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client as TwilioClient;

final class TwilioTest extends DriverTestCase
{
    private const string SID = 'AC00000000000000000000000000000001';

    protected function driverName() : string
    {
        return 'twilio';
    }

    protected function driverClass() : string
    {
        return Twilio::class;
    }

    public function testItPostsTheMessageToTheMessagesResourceOfTheAccount() : void
    {
        $driver = $this->driver(['from' => '+10000000002']);
        $http = $this->fakeTwilio($driver);

        $this->send($driver, ['+10000000001']);

        $this->assertCount(1, $http->requests);
        $this->assertSame('POST', $http->requests[0]['method']);
        $this->assertStringEndsWith('/Accounts/'.self::SID.'/Messages.json', $http->requests[0]['url']);
        $this->assertSame(
            ['To' => '+10000000001', 'From' => '+10000000002', 'Body' => 'the message'],
            $http->requests[0]['data']
        );
    }

    public function testItPostsOncePerRecipient() : void
    {
        $driver = $this->driver();
        $http = $this->fakeTwilio($driver);

        $this->send($driver, ['+10000000001', '+10000000002']);

        $this->assertSame(
            ['+10000000001', '+10000000002'],
            array_column(array_column($http->requests, 'data'), 'To')
        );
    }

    public function testItReturnsTheMessageTheGatewayCreated() : void
    {
        $driver = $this->driver();
        $this->fakeTwilio($driver);

        $answer = $this->send($driver, ['+10000000001']);

        $this->assertInstanceOf(MessageInstance::class, $answer);
        $this->assertSame('queued', $answer->status);
        $this->assertSame('+10000000001', $answer->to);
    }

    public function testItBuildsItsClientWithTheConfiguredCredentials() : void
    {
        $driver = $this->driver(['sid' => self::SID, 'token' => 'the-token']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(TwilioClient::class, $client);
        $this->assertSame(self::SID, $client->getUsername());
        $this->assertSame('the-token', $client->getPassword());
        $this->assertSame(self::SID, $client->getAccountSid());
    }

    private function fakeTwilio(Driver $driver) : RecordingTwilioHttpClient
    {
        $http = new RecordingTwilioHttpClient();

        $this->swapClient($driver, new TwilioClient(self::SID, 'the-token', null, null, $http));

        return $http;
    }
}
