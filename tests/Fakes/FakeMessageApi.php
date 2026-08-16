<?php

namespace Shetabit\Sms\Tests\Fakes;

use SMSGatewayMe\Client\Api\MessageApi;

class FakeMessageApi extends MessageApi
{
    /**
     * @var array<int, mixed>
     */
    public array $sent = [];

    /**
     * @param mixed $messages
     *
     * @return array<int, string>
     */
    public function sendMessages($messages) : array
    {
        $this->sent[] = $messages;

        return ['smsgatewayme-id'];
    }
}
