<?php

namespace Shetabit\Sms\Drivers;

use Shetabit\Sms\Abstracts\Driver;
use SMSGatewayMe\Client\Api\MessageApi;
use SMSGatewayMe\Client\ApiClient;
use SMSGatewayMe\Client\Configuration;
use SMSGatewayMe\Client\Model\SendMessageRequest;

class SmsGatewayMe extends Driver
{
    protected MessageApi $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : MessageApi
    {
        $configuration = new Configuration();
        $configuration->setApiKey('Authorization', $this->setting('apiToken'));

        return new MessageApi(new ApiClient($configuration));
    }

    protected function sendTo(string $recipient) : mixed
    {
        return $this->client->sendMessages([$this->payload($recipient)]);
    }

    protected function payload(string $recipient) : SendMessageRequest
    {
        return new SendMessageRequest([
            'phoneNumber' => $recipient,
            'message' => $this->message->toString(),
            'deviceId' => (int) $this->setting('from'),
        ]);
    }
}
