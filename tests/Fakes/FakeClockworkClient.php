<?php

namespace Shetabit\Sms\Tests\Fakes;

use mediaburst\ClockworkSMS\Clockwork;

class FakeClockworkClient extends Clockwork
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $sent = [];

    /**
     * @param array<string, mixed> $sms
     *
     * @return array<string, mixed>
     */
    public function send(array $sms) : array
    {
        $this->sent[] = $sms;

        return ['id' => 'clockwork-id', 'success' => true, 'to' => $sms['to'] ?? null];
    }
}
