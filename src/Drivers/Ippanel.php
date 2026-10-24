<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;

class Ippanel extends Driver
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
        $response = $this->client->request('POST', $this->url('/api/send'), $this->payload($recipient));

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array{json: array<string, mixed>, headers: array<string, string>}
     */
    protected function payload(string $recipient) : array
    {
        return [
            'json' => $this->message->usesTemplate() ? $this->patternBody($recipient) : $this->webserviceBody($recipient),
            'headers' => [
                'Authorization' => $this->setting('apiKey'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function webserviceBody(string $recipient) : array
    {
        return [
            'sending_type' => 'webservice',
            'from_number' => $this->setting('from'),
            'message' => $this->message->toString(),
            'params' => [
                'recipients' => [$recipient],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function patternBody(string $recipient) : array
    {
        $template = $this->message->getTemplate();

        return [
            'sending_type' => 'pattern',
            'from_number' => $this->setting('from'),
            'code' => (string) $template['identifier'],
            'recipients' => [$recipient],
            'params' => $template['params'],
        ];
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
