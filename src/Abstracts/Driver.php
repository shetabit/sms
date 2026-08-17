<?php

namespace Shetabit\Sms\Abstracts;

use Illuminate\Support\Collection;
use Shetabit\Sms\Contracts\Driver as DriverContract;
use Shetabit\Sms\Contracts\Message;

abstract class Driver implements DriverContract
{
    /**
     * @var array<int, string>
     */
    protected array $recipients = [];

    protected Message $message;

    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(protected array $settings = [])
    {
    }

    /**
     * @param array<int, string> $recipients
     */
    public function to(array $recipients) : static
    {
        $this->recipients = array_values($recipients);

        return $this;
    }

    public function message(Message $message) : static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed the answer of the gateway for a single recipient,
     *               a Collection keyed by recipient for several of them
     */
    public function send() : mixed
    {
        /** @var Collection<string, mixed> $responses */
        $responses = collect();

        foreach ($this->recipients as $recipient) {
            $responses->put($recipient, $this->sendTo($recipient));
        }

        return count($this->recipients) === 1 ? $responses->first() : $responses;
    }

    abstract protected function sendTo(string $recipient) : mixed;

    protected function setting(string $key, string $default = '') : string
    {
        $value = $this->settings[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    protected function booleanSetting(string $key, bool $default = false) : bool
    {
        $value = $this->settings[$key] ?? null;

        return is_scalar($value) ? (bool) $value : $default;
    }
}
