<?php

namespace Shetabit\Sms\Facades;

use Illuminate\Support\Facades\Facade;
use Shetabit\Sms\Sms as SmsManager;

/**
 * @method static SmsManager via(string $driver)
 * @method static SmsManager config(array<string, mixed>|string $key, mixed $value = null)
 * @method static SmsManager to(array<int, string> $recipients)
 * @method static SmsManager recipients(array<int, string> $recipients)
 * @method static SmsManager message(\Shetabit\Sms\Contracts\Message|string $message)
 * @method static string getDriver()
 * @method static mixed send()
 *
 * @see SmsManager
 */
class Sms extends Facade
{
    public static function getFacadeAccessor() : string
    {
        return SmsManager::SERVICE_NAME;
    }
}
