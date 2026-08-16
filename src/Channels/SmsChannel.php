<?php

namespace Shetabit\Sms\Channels;

use Illuminate\Notifications\Notification;
use Shetabit\Sms\Exceptions\InvalidNotificationException;
use Shetabit\Sms\Sms;

class SmsChannel
{
    /**
     * @throws InvalidNotificationException
     */
    public function send(mixed $notifiable, Notification $notification) : mixed
    {
        if (! method_exists($notification, 'toSms')) {
            throw new InvalidNotificationException(
                sprintf('[%s] must define a toSms() method to be sent over the sms channel.', $notification::class)
            );
        }

        $manager = $notification->toSms($notifiable);

        if ($manager === null) {
            return null;
        }

        if (! $manager instanceof Sms) {
            throw new InvalidNotificationException('Invalid data for sms notification.');
        }

        return $manager->send();
    }
}
