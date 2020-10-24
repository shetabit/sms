<?php

namespace Shetabit\Sms\Contracts;

interface Driver
{
    /**
     * Add reciepeints (phone or mobile numbers)
     *
     * @param array $recipients
     *
     * @return self
     */
    public function to(array $recipients) : self;

    /**
     * Set related message.
     *
     * @return self
     */
    public function message(Message $message) : self;

    /**
     * Send message to recipients.
     *
     * @return array
     */
    public function send();
}
