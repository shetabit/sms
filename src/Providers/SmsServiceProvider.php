<?php

namespace Shetabit\Sms\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Shetabit\Sms\Sms;

class SmsServiceProvider extends ServiceProvider
{
    public function boot() : void
    {
        $this->publishes([self::configPath() => config_path('sms.php')], ['config', 'sms-config']);
    }

    public function register() : void
    {
        $this->mergeConfigFrom(self::configPath(), 'sms');

        $this->app->bind(Sms::SERVICE_NAME, static function (Container $app) : Sms {
            /** @var array<string, mixed> $config */
            $config = $app->make(Repository::class)->get('sms', []);

            return new Sms($config);
        });

        $this->app->alias(Sms::SERVICE_NAME, Sms::class);
    }

    public static function configPath() : string
    {
        return dirname(__DIR__, 2).'/config/sms.php';
    }
}
