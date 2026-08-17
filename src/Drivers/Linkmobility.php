<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Abstracts\Driver;

class Linkmobility extends Driver
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
        return new Client();
    }

    protected function sendTo(string $recipient) : ResponseInterface
    {
        return $this->client->request('POST', $this->setting('url'), $this->payload($recipient));
    }

    /**
     * @return array{form_params: array<string, string>}
     */
    protected function payload(string $recipient) : array
    {
        return [
            'form_params' => [
                'USER' => $this->setting('username'),
                'PW' => $this->setting('password'),
                'RCV' => $recipient,
                'SND' => $this->setting('sender'),
                'TXT' => $this->message->toString(),
            ],
        ];
    }
}
