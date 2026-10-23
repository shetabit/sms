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
        if ($this->usesLegacyApi()) {
            return $this->client->request('POST', $this->url('/api/MessageSend'), $this->payload($recipient));
        }

        if ($this->message->usesTemplate()) {
            return $this->client->request('POST', $this->url('/v1/send/verify'), $this->verifyPayload($recipient));
        }

        return $this->client->request('POST', $this->url('/v1/send/bulk'), $this->bulkPayload($recipient));
    }

    /**
     * The legacy api of the gateway signs in with a secret key next to the api key, the current one with an api key
     * alone. A configuration that still carries a secret key, like one published from an earlier version, keeps
     * talking to the legacy api.
     */
    protected function usesLegacyApi() : bool
    {
        return $this->setting('secretKey') !== '';
    }

    /**
     * @return array{json: array<string, mixed>, headers: array<string, string>, connect_timeout: int}
     */
    protected function bulkPayload(string $recipient) : array
    {
        return [
            'json' => [
                'lineNumber' => $this->setting('from'),
                'messageText' => $this->message->toString(),
                'mobiles' => [$recipient],
            ],
            'headers' => $this->apiKeyHeaders(),
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
            'headers' => $this->apiKeyHeaders(),
            'connect_timeout' => 30,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function apiKeyHeaders() : array
    {
        return [
            'X-API-KEY' => $this->setting('apiKey'),
            'Accept' => 'application/json',
        ];
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
