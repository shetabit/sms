<?php

namespace Shetabit\Sms\Drivers;

use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;

class Asanak extends Driver
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
            return $this->post('/sendsms', $this->smsBody($recipient));
        }

        return $this->post('/template', $this->templateBody($recipient));
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function post(string $path, array $body) : mixed
    {
        $response = $this->client->request('POST', $this->url($path), [
            'json' => array_merge(
                ['username' => $this->setting('username'), 'password' => $this->setting('password')],
                $body,
                ['send_to_black_list' => (int) $this->booleanSetting('sendToBlackList', true)]
            ),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array<string, mixed>
     */
    protected function smsBody(string $recipient) : array
    {
        return [
            'source' => $this->setting('from'),
            'destination' => $recipient,
            'message' => $this->message->toString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function templateBody(string $recipient) : array
    {
        $template = $this->message->getTemplate();

        return [
            'template_id' => (int) $template['identifier'],
            'parameters' => $template['params'],
            'destination' => $recipient,
        ];
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
