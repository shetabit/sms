<?php

namespace Shetabit\Sms\Drivers;

use Melipayamak\MelipayamakApi;
use Melipayamak\SmsRest;
use Shetabit\Sms\Abstracts\Driver;

class Melipayamak extends Driver
{
    protected MelipayamakApi $client;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(array $settings = [])
    {
        parent::__construct($settings);

        $this->client = $this->createClient();
    }

    protected function createClient() : MelipayamakApi
    {
        return new MelipayamakApi($this->setting('username'), $this->setting('password'));
    }

    public function asFlash(bool $flash = true) : static
    {
        $this->settings['flash'] = $flash;

        return $this;
    }

    protected function sendTo(string $recipient) : mixed
    {
        /**
         * @var SmsRest $sms
         *
         * @phpstan-ignore method.notFound (the sdk routes this through __call)
         */
        $sms = $this->client->sms();

        return $sms->send(
            $recipient,
            $this->setting('from'),
            $this->message->toString(),
            $this->booleanSetting('flash')
        );
    }
}
