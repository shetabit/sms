<?php

namespace Shetabit\Sms\Contracts;

interface Driver
{
    /**
     * @param array<int, string> $recipients phone or mobile numbers
     */
    public function to(array $recipients) : static;

    public function message(Message $message) : static;

    /**
     * The answer of the gateway, or a collection of them when there is more than one recipient.
     */
    public function send() : mixed;
}
