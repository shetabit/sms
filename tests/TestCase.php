<?php

namespace Shetabit\Sms\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Shetabit\Sms\Message;

abstract class TestCase extends BaseTestCase
{
    /**
     * The configuration that ships with the package.
     *
     * @return array{default: string, drivers: array<string, array<string, mixed>>, map: array<string, class-string>}
     */
    protected function packageConfig() : array
    {
        /** @var array{default: string, drivers: array<string, array<string, mixed>>, map: array<string, class-string>} $config */
        $config = require dirname(__DIR__).'/config/sms.php';

        return $config;
    }

    protected function message(string $text = 'the message') : Message
    {
        return new Message($text);
    }
}
