<?php

namespace Shetabit\Sms\Drivers;

use Melipayamak\MelipayamakApi;
use Shetabit\Sms\Abstracts\Driver;

class Melipayamak extends Driver
{
    protected array $settings;

    protected MelipayamakApi $client;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = new MelipayamakApi(data_get($this->settings, 'username'), data_get($this->settings, 'password'));
    }

    public function asFlash($flash = true)
    {
        $this->settings['flash'] = $flash;

        return $this;
    }

    public function send()
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            $response->put($recipient, $this->client->sms()->send(
                $recipient,
                data_get($this->settings, 'from'),
                $this->message->toString(),
                data_get($this->settings, 'flash')
            ));
        }

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }
}
