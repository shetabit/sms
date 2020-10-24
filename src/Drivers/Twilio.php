<?php

namespace Shetabit\Sms\Drivers;

use Twilio\Rest\Client;
use Shetabit\Sms\Abstracts\Driver;

class Twilio extends Driver
{
    protected array $settings;

    protected Client $client;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = new Client(data_get($this->settings, 'sid'), data_get($this->settings, 'token'));
    }

    public function send()
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            /**
             * @psalm-suppress UndefinedMagicPropertyFetch
             */
            $result = $this->client->account->messages->create(
                $recipient,
                ['from' => data_get($this->settings, 'from'), 'body' => $this->message->toString()]
            );

            $response->put($recipient, $result);
        }

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }
}
