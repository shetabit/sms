<?php

namespace Shetabit\Sms\Contracts;

interface Message
{
    /**
     * Retrieve string format of message.
     *
     * @return string
     */
    public function toString() : string;
}
