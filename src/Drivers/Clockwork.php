<?php

namespace Shetabit\Sms\Drivers;

use mediaburst\ClockworkSMS\Clockwork as ClockworkClient;
use Shetabit\Sms\Abstracts\Driver;

class Clockwork extends Driver
{
    protected array $settings;

    protected ClockworkClient $client;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = new ClockworkClient(data_get($this->settings, 'key'));
    }

    public function send()
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            $response->put($recipient, $this->client->send([
                'to' => $recipient,
                'message' => $this->message->toString(),
            ]));
        }

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }
}
