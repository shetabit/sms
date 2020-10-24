<?php

namespace Shetabit\Sms\Channels;

use Exception;
use Illuminate\Notifications\Notification;
use Shetabit\Sms\Sms;

class SmsChannel
{
    /**
     * Send notification.
     *
     * @param $notifiable
     * @param Notification $notification
     *
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $manager = $notification->toSms($notifiable);

        if (! is_null($manager)) {
            $this->validate($manager);
            $manager->send();
        }
    }

    /**
     * Validate sms.
     *
     * @return void
     */
    protected function validate($manager)
    {
        if (! $manager instanceof Sms) {
            throw new Exception('Invalid data for sms notification.');
        }
    }
}
