<?php

namespace Shetabit\Sms\Tests\Fakes;

use Melipayamak\MelipayamakApi;

class FakeMelipayamakApi extends MelipayamakApi
{
    public FakeSmsRest $rest;

    public function __construct(string $username = 'username', string $password = 'password')
    {
        parent::__construct($username, $password);

        $this->rest = new FakeSmsRest($username, $password);
    }

    public function sms() : FakeSmsRest
    {
        return $this->rest;
    }
}
