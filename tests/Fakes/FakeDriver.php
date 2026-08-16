<?php

namespace Shetabit\Sms\Tests\Fakes;

use Shetabit\Sms\Abstracts\Driver;

/**
 * A driver that answers like a gateway would, without talking to one.
 *
 * It hands the settings it was constructed with back through its answer, so that
 * the tests can tell which configuration the manager gave it.
 */
class FakeDriver extends Driver
{
    public const string DRIVER_NAME = 'fake';

    /**
     * @var array<int, array{recipient: string, message: string, settings: array<string, mixed>}>
     */
    public static array $sent = [];

    public static function forget() : void
    {
        self::$sent = [];
    }

    /**
     * @return array{recipient: string, message: string, settings: array<string, mixed>}
     */
    protected function sendTo(string $recipient) : array
    {
        $sent = [
            'recipient' => $recipient,
            'message' => $this->message->toString(),
            'settings' => $this->settings,
        ];

        self::$sent[] = $sent;

        return $sent;
    }
}
