<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;

class Farazsms extends Driver
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

    protected function sendTo(string $recipient) : mixed
    {
        $response = $this->client->request('POST', $this->setting('url'), $this->payload($recipient));

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array{form_params: array<string, string>}
     */
    protected function payload(string $recipient) : array
    {
        return [
            'form_params' => [
                'uname' => $this->setting('username'),
                'pass' => $this->setting('password'),
                'from' => $this->setting('from'),
                'message' => $this->message->toString(),
                'to' => (string) json_encode([$recipient]),
                'op' => 'send',
            ],
        ];
    }
}
