<?php

namespace Shetabit\Sms\Tests\Fakes;

use Melipayamak\SmsRest;

class FakeSmsRest extends SmsRest
{
    /**
     * @var array<int, array{to: mixed, from: mixed, text: mixed, isFlash: mixed}>
     */
    public array $sent = [];

    /**
     * @param mixed $to
     * @param mixed $from
     * @param mixed $text
     * @param mixed $isFlash
     */
    public function send($to, $from, $text, $isFlash = false) : string
    {
        $this->sent[] = ['to' => $to, 'from' => $from, 'text' => $text, 'isFlash' => $isFlash];

        return '{"Value":"melipayamak-id","RetStatus":1}';
    }
}
