<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sns\SnsClient;
use ReflectionProperty;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Drivers\Sns;

final class SnsTest extends DriverTestCase
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $commands = [];

    protected function driverName() : string
    {
        return 'sns';
    }

    protected function driverClass() : string
    {
        return Sns::class;
    }

    public function testItPublishesTheMessageToThePhoneNumber() : void
    {
        $driver = $this->driver(['sender' => 'Acme', 'type' => 'Transactional']);
        $this->fakeSns($driver);

        $this->send($driver, ['+10000000001']);

        $this->assertCount(1, $this->commands);
        $this->assertSame('the message', $this->commands[0]['Message']);
        $this->assertSame('+10000000001', $this->commands[0]['PhoneNumber']);
        $this->assertSame(
            [
                'AWS.SNS.SMS.SenderID' => ['DataType' => 'String', 'StringValue' => 'Acme'],
                'AWS.SNS.SMS.SMSType' => ['DataType' => 'String', 'StringValue' => 'Transactional'],
            ],
            $this->commands[0]['MessageAttributes']
        );
    }

    public function testItSendsTheMessageTypeTheGatewayKnows() : void
    {
        $driver = $this->driver();
        $this->fakeSns($driver);

        $this->send($driver);

        $this->assertSame(
            'Transactional',
            $this->commands[0]['MessageAttributes']['AWS.SNS.SMS.SMSType']['StringValue']
        );
    }

    public function testItPublishesOncePerRecipient() : void
    {
        $driver = $this->driver();
        $this->fakeSns($driver);

        $this->send($driver, ['+10000000001', '+10000000002']);

        $this->assertSame(['+10000000001', '+10000000002'], array_column($this->commands, 'PhoneNumber'));
    }

    public function testItBuildsItsClientWithTheConfiguredCredentialsAndRegion() : void
    {
        $driver = $this->driver(['key' => 'the-key', 'secret' => 'the-secret', 'region' => 'eu-west-1']);

        $client = new ReflectionProperty($driver, 'client')->getValue($driver);

        $this->assertInstanceOf(SnsClient::class, $client);
        $this->assertSame('eu-west-1', $client->getRegion());
        $this->assertSame('2010-03-31', $client->getApi()->getApiVersion());
        $this->assertSame('the-key', $client->getCredentials()->wait()->getAccessKeyId());
        $this->assertSame('the-secret', $client->getCredentials()->wait()->getSecretKey());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function driver(array $overrides = []) : Driver
    {
        return parent::driver(array_merge(['region' => 'eu-west-1'], $overrides));
    }

    private function fakeSns(Driver $driver) : void
    {
        $this->commands = [];

        $handler = new MockHandler();

        foreach (range(1, 4) as $ignored) {
            $handler->append(function (CommandInterface $command) : Result {
                $this->commands[] = $command->toArray();

                return new Result(['MessageId' => 'sns-id']);
            });
        }

        $this->swapClient($driver, new SnsClient([
            'credentials' => ['key' => 'the-key', 'secret' => 'the-secret'],
            'region' => 'eu-west-1',
            'version' => '2010-03-31',
            'handler' => $handler,
        ]));
    }
}
