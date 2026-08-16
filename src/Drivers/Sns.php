<?php

namespace Shetabit\Sms\Drivers;

use Aws\Sns\SnsClient;
use Shetabit\Sms\Abstracts\Driver;

class Sns extends Driver
{
    protected SnsClient $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : SnsClient
    {
        return new SnsClient([
            'credentials' => [
                'key' => $this->setting('key'),
                'secret' => $this->setting('secret'),
            ],
            'region' => $this->setting('region'),
            'version' => '2010-03-31',
        ]);
    }

    protected function sendTo(string $recipient) : mixed
    {
        return $this->client->publish($this->payload($recipient));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(string $recipient) : array
    {
        return [
            'Message' => $this->message->toString(),
            'MessageAttributes' => [
                'AWS.SNS.SMS.SenderID' => [
                    'DataType' => 'String',
                    'StringValue' => $this->setting('sender'),
                ],
                'AWS.SNS.SMS.SMSType' => [
                    'DataType' => 'String',
                    'StringValue' => $this->setting('type'),
                ],
            ],
            'PhoneNumber' => $recipient,
        ];
    }
}
