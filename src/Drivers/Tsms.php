<?php

namespace Shetabit\Sms\Drivers;

use Shetabit\Sms\Abstracts\Driver;
use SoapClient;

class Tsms extends Driver
{
    protected SoapClient|null $client = null;

    protected function createClient() : SoapClient
    {
        return new SoapClient($this->setting('url'));
    }

    protected function client() : SoapClient
    {
        return $this->client ??= $this->createClient();
    }

    protected function sendTo(string $recipient) : mixed
    {
        return $this->client()->sendSms(
            $this->setting('username'),
            $this->setting('password'),
            [$this->setting('from')],
            [$recipient],
            [$this->message->toString()],
            [],
            random_int(1, mt_getrandmax())
        );
    }
}
