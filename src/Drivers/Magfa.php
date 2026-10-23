<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;

class Magfa extends Driver
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
        $response = $this->client->request('POST', $this->url('/send'), $this->payload($recipient));

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array{json: array<string, mixed>, auth: array{string, string}, headers: array<string, string>}
     */
    protected function payload(string $recipient) : array
    {
        return [
            'json' => [
                'senders' => [$this->setting('from')],
                'recipients' => [$recipient],
                'messages' => [$this->message->toString()],
            ],
            // The gateway signs its users in with "username/domain" as the user name of the basic auth.
            'auth' => [$this->setting('username').'/'.$this->setting('domain'), $this->setting('password')],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
