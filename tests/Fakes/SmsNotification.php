<?php

namespace Shetabit\Sms\Tests\Fakes;

use Illuminate\Notifications\Notification;
use Shetabit\Sms\Channels\SmsChannel;

class SmsNotification extends Notification
{
    public function __construct(protected readonly mixed $sms = null)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable) : array
    {
        return [SmsChannel::class];
    }

    public function toSms(mixed $notifiable) : mixed
    {
        return $this->sms;
    }
}
