<?php

namespace Shetabit\Sms\Providers;

use Shetabit\Sms\Sms;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../config/sms.php' => config_path('sms.php'),
            ],
            'config'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Bind to service container.
         */
        $this->app->bind('shetabit-sms', function () {
            $config = config('sms') ?? [];

            return new Sms($config);
        });
    }
}
