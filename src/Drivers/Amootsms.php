<?php

namespace Shetabit\Sms\Drivers;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Client;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Exceptions\InvalidMessageException;

class Amootsms extends Driver
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

    /**
     * @throws InvalidMessageException
     */
    protected function sendTo(string $recipient) : mixed
    {
        if (! $this->message->usesTemplate()) {
            return $this->post('/SendSimple', $this->simpleForm($recipient));
        }

        return $this->post('/SendWithPattern', $this->patternForm($recipient));
    }

    /**
     * @param array<string, string> $form
     */
    protected function post(string $path, array $form) : mixed
    {
        $response = $this->client->request('POST', $this->url($path), [
            'form_params' => $form,
            'headers' => [
                'Authorization' => $this->setting('token'),
            ],
        ]);

        return json_decode((string) $response->getBody());
    }

    /**
     * @return array<string, string>
     */
    protected function simpleForm(string $recipient) : array
    {
        return [
            // The gateway asks when to send the message: right away, in the time of Iran.
            'SendDateTime' => new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'))->format('c'),
            'SMSMessageText' => $this->message->toString(),
            'LineNumber' => $this->setting('from'),
            'Mobiles' => $recipient,
        ];
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidMessageException
     */
    protected function patternForm(string $recipient) : array
    {
        $template = $this->message->getTemplate();

        return [
            'Mobile' => $recipient,
            'PatternCodeID' => (string) $template['identifier'],
            'PatternValues' => $this->patternValues($template['params']),
        ];
    }

    /**
     * The gateway takes the values comma separated, in the order the pattern lists its variables, so a comma inside a
     * value would push the rest of it into the next variable.
     *
     * @param array<int|string, mixed> $params
     *
     * @throws InvalidMessageException
     */
    protected function patternValues(array $params) : string
    {
        foreach ($params as $key => $value) {
            if (str_contains((string) $value, ',')) {
                throw new InvalidMessageException(
                    sprintf('The value [%s] of an AmootSMS pattern must not contain a comma.', $key)
                );
            }
        }

        return implode(',', $params);
    }

    protected function url(string $path) : string
    {
        return rtrim($this->setting('url'), '/').$path;
    }
}
