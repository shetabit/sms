<?php

namespace Shetabit\Sms\Tests\Fakes;

use Illuminate\Notifications\Notification;
use Shetabit\Sms\Channels\SmsChannel;

class SilentNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable) : array
    {
        return [SmsChannel::class];
    }
}
