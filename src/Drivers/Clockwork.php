<?php

namespace Shetabit\Sms\Drivers;

use mediaburst\ClockworkSMS\Clockwork as ClockworkClient;
use Shetabit\Sms\Abstracts\Driver;

class Clockwork extends Driver
{
    protected ClockworkClient $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : ClockworkClient
    {
        return new ClockworkClient($this->setting('key'));
    }

    protected function sendTo(string $recipient) : mixed
    {
        return $this->client->send($this->payload($recipient));
    }

    /**
     * @return array<string, string>
     */
    protected function payload(string $recipient) : array
    {
        return [
            'to' => $recipient,
            'message' => $this->message->toString(),
        ];
    }
}
