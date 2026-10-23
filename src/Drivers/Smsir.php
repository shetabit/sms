<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Abstracts\Driver;

class Smsir extends Driver
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
        if ($this->message->usesTemplate()) {
            return $this->client->request('POST', $this->url('/v1/send/verify'), $this->verifyPayload($recipient));
        }

        return $this->client->request('POST', $this->url('/v1/send/bulk'), $this->payload($recipient));
    }

    /**
     * @return array{json: array<string, mixed>, headers: array<string, string>, connect_timeout: int}
     */
    protected function payload(string $recipient) : array
    {
        return [
            'json' => [
                'lineNumber' => $this->setting('from'),
                'messageText' => $this->message->toString(),
                'mobiles' => [$recipient],
            ],
            'headers' => $this->headers(),
            'connect_timeout' => 30,
        ];
    }

    /**
     * @return array{json: array<string, mixed>, headers: array<string, string>, connect_timeout: int}
     */
    protected function verifyPayload(string $recipient) : array
    {
        $template = $this->message->getTemplate();
        $parameters = [];

        foreach ($template['params'] as $name => $value) {
            $parameters[] = ['name' => (string) $name, 'value' => (string) $value];
        }

        return [
            'json' => [
                'mobile' => $recipient,
                'templateId' => (int) $template['identifier'],
                'parameters' => $parameters,
            ],
            'headers' => $this->headers(),
            'connect_timeout' => 30,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function headers() : array
    {
        return [
            'X-API-KEY' => $this->setting('apiKey'),
            'Accept' => 'application/json',
        ];
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
