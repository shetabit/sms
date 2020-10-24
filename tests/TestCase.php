<?php

namespace Shetabit\Sms\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shetabit\Sms\Tests\Mocks\Drivers\BarDriver;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return ['Shetabit\Sms\Provider\SmsServiceProvider'];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Sms' => 'Shetabit\Sms\Facade\Sms',
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $settings = require __DIR__.'/../src/Config/sms.php';
        $settings['drivers']['bar'] = ['key' => 'foo'];
        $settings['map']['bar'] = BarDriver::class;

        $app['config']->set('sms', $settings);
    }
}
