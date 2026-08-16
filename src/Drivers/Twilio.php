<?php

namespace Shetabit\Sms\Drivers;

use Shetabit\Sms\Abstracts\Driver;
use Twilio\Rest\Client;

class Twilio extends Driver
{
    protected Client $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : Client
    {
        return new Client($this->setting('sid'), $this->setting('token'));
    }

    protected function sendTo(string $recipient) : mixed
    {
        return $this->client->messages->create($recipient, $this->payload());
    }

    /**
     * @return array<string, string>
     */
    protected function payload() : array
    {
        return [
            'from' => $this->setting('from'),
            'body' => $this->message->toString(),
        ];
    }
}
