<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;

class Ghasedak extends Driver
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
        if (! $this->message->usesTemplate()) {
            return $this->post('/SendSingleSMS', $this->singleBody($recipient));
        }

        return $this->post('/SendOtpSMS', $this->otpBody($recipient));
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function post(string $path, array $body) : mixed
    {
        $response = $this->client->request('POST', $this->url($path), [
            'json' => $body,
            'headers' => [
                'ApiKey' => $this->setting('apiKey'),
            ],
        ]);

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array<string, mixed>
     */
    protected function singleBody(string $recipient) : array
    {
        return [
            'lineNumber' => $this->setting('from'),
            'receptor' => $recipient,
            'message' => $this->message->toString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function otpBody(string $recipient) : array
    {
        $template = $this->message->getTemplate();
        $inputs = [];

        foreach ($template['params'] as $param => $value) {
            $inputs[] = ['param' => (string) $param, 'value' => (string) $value];
        }

        return [
            'receptors' => [
                ['mobile' => $recipient],
            ],
            'templateName' => (string) $template['identifier'],
            'inputs' => $inputs,
        ];
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
