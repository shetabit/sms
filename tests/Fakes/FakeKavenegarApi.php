<?php

namespace Shetabit\Sms\Tests\Fakes;

use Kavenegar\KavenegarApi;

class FakeKavenegarApi extends KavenegarApi
{
    /**
     * @var array<int, array{method: string, arguments: array<int, mixed>}>
     */
    public array $calls = [];

    /**
     * @param mixed $sender
     * @param mixed $receptor
     * @param mixed $message
     * @param mixed $date
     * @param mixed $type
     * @param mixed $localid
     */
    public function Send($sender, $receptor, $message, $date = null, $type = null, $localid = null) : string
    {
        $this->calls[] = ['method' => 'Send', 'arguments' => func_get_args()];

        return 'sent';
    }

    /**
     * @param mixed $receptor
     * @param mixed $token
     * @param mixed $token2
     * @param mixed $token3
     * @param mixed $template
     * @param mixed $type
     */
    public function VerifyLookup($receptor, $token, $token2, $token3, $template, $type = null) : string
    {
        $this->calls[] = ['method' => 'VerifyLookup', 'arguments' => func_get_args()];

        return 'looked-up';
    }
}
