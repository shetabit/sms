<?php

namespace Shetabit\Sms\Drivers;

use Kavenegar\KavenegarApi;
use Shetabit\Sms\Abstracts\Driver;

class Kavenegar extends Driver
{
    protected array $settings;

    protected KavenegarApi $client;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = new KavenegarApi(data_get($this->settings, 'apiKey'));
    }

    public function send()
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            $response->put($recipient, $this->tryToSend($recipient));
        }

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }

    public function tryToSend($recipient)
    {
        if ($this->message->usesTemplate()) {
            $template = $this->message->getTemplate();
            $identifier = $template['identifier'];
            $params = $template['params'];

            return $this->client->VerifyLookup(
                $recipient,
                array_shift($params),
                array_shift($params),
                array_shift($params),
                $identifier
            );
        }

        return $this->client->Send(data_get($this->settings, 'from'), $recipient, $this->message->toString());
    }
}
