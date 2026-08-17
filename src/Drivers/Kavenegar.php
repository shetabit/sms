<?php

namespace Shetabit\Sms\Drivers;

use Kavenegar\KavenegarApi;
use Shetabit\Sms\Abstracts\Driver;

class Kavenegar extends Driver
{
    protected KavenegarApi $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : KavenegarApi
    {
        return new KavenegarApi($this->setting('apiKey'));
    }

    protected function sendTo(string $recipient) : mixed
    {
        if (! $this->message->usesTemplate()) {
            return $this->client->Send($this->setting('from'), $recipient, $this->message->toString());
        }

        $template = $this->message->getTemplate();
        $tokens = array_values($template['params']);

        return $this->client->VerifyLookup(
            $recipient,
            $tokens[0] ?? null,
            $tokens[1] ?? null,
            $tokens[2] ?? null,
            $template['identifier']
        );
    }
}
