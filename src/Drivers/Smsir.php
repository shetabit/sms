<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Exceptions\SendingFailedException;

class Smsir extends Driver
{
    protected Client $client;

    protected string|null $token = null;

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

    /**
     * @throws SendingFailedException
     */
    protected function sendTo(string $recipient) : ResponseInterface
    {
        return $this->client->request('POST', $this->url('/api/MessageSend'), $this->payload($recipient));
    }

    /**
     * @return array{json: array<string, mixed>, headers: array<string, string>, connect_timeout: int}
     *
     * @throws SendingFailedException
     */
    protected function payload(string $recipient) : array
    {
        return [
            'json' => [
                'Messages' => [$this->message->toString()],
                'MobileNumbers' => [$recipient],
                'LineNumber' => $this->setting('from'),
            ],
            'headers' => [
                'x-sms-ir-secure-token' => $this->token(),
            ],
            'connect_timeout' => 30,
        ];
    }

    /**
     * @throws SendingFailedException
     */
    protected function token() : string
    {
        return $this->token ??= $this->fetchToken();
    }

    /**
     * @throws SendingFailedException
     */
    protected function fetchToken() : string
    {
        $response = $this->client->request('POST', $this->url('/api/Token'), [
            'json' => [
                'UserApiKey' => $this->setting('apiKey'),
                'SecretKey' => $this->setting('secretKey'),
            ],
            'connect_timeout' => 30,
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $token = is_array($body) ? ($body['TokenKey'] ?? null) : null;

        if (! is_string($token) || $token === '') {
            throw new SendingFailedException('Smsir token could not be generated.');
        }

        return $token;
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
