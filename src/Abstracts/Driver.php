<?php

namespace Shetabit\Sms\Abstracts;

use Shetabit\Sms\Contracts\Driver as DriverContract;
use Shetabit\Sms\Contracts\Message;

abstract class Driver implements DriverContract
{
    /**
     * Recipients
     *
     * @param array
     */
    protected $recipients;

    /**
     * Message
     *
     * @var Message
     */
    protected $message;

    /**
     * Add reciepeints (phone or mobile numbers)
     *
     * @param array $recipients
     *
     * @return self
     */
    public function to(array $recipients) : self
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Set related message.
     *
     * @return self
     */
    public function message(Message $message) : self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Send message to recipients.
     *
     * @return array
     */
    abstract public function send();
}
