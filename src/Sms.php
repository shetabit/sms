<?php

namespace Shetabit\Sms;

use Shetabit\Sms\Contracts\Driver;
use Shetabit\Sms\Contracts\Message as MessageContract;
use Shetabit\Sms\Exceptions\DriverNotFoundException;
use Shetabit\Sms\Exceptions\InvalidMessageException;

/**
 * @phpstan-type SmsConfig array{
 *     default?: string,
 *     drivers?: array<string, array<string, mixed>>,
 *     map?: array<string, class-string>,
 * }
 */
class Sms
{
    public const string SERVICE_NAME = 'shetabit-sms';

    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    protected string $driver = '';

    /**
     * @var array<int, string>
     */
    protected array $recipients = [];

    protected MessageContract|null $message = null;

    /**
     * @param SmsConfig $config
     *
     * @throws DriverNotFoundException
     */
    public function __construct(protected array $config = [])
    {
        $this->via($this->config['default'] ?? '');
    }

    /**
     * Overwrite the settings of the current driver at runtime.
     *
     * @param array<string, mixed>|string $key
     */
    public function config(array|string $key, mixed $value = null) : static
    {
        $this->settings = array_merge($this->settings, is_array($key) ? $key : [$key => $value]);

        return $this;
    }

    /**
     * @throws DriverNotFoundException
     */
    public function via(string $driver) : static
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->settings = $this->config['drivers'][$driver] ?? [];

        return $this;
    }

    public function getDriver() : string
    {
        return $this->driver;
    }

    /**
     * @param array<int, string> $recipients
     */
    public function to(array $recipients) : static
    {
        $this->recipients = array_values($recipients);

        return $this;
    }

    /**
     * @param array<int, string> $recipients
     */
    public function recipients(array $recipients) : static
    {
        return $this->to($recipients);
    }

    public function message(MessageContract|string $message) : static
    {
        $this->message = is_string($message) ? new Message($message) : $message;

        return $this;
    }

    /**
     * @throws DriverNotFoundException
     * @throws InvalidMessageException
     */
    public function send() : mixed
    {
        $message = $this->message;

        if (! $message instanceof MessageContract) {
            throw new InvalidMessageException('Message not selected or does not exist.');
        }

        return $this
            ->getDriverInstance()
            ->to($this->recipients)
            ->message($message)
            ->send();
    }

    /**
     * @throws DriverNotFoundException
     */
    protected function getDriverInstance() : Driver
    {
        $this->validateDriver();

        $class = $this->config['map'][$this->driver] ?? '';

        /** @var Driver $instance */
        $instance = new $class($this->settings);

        return $instance;
    }

    /**
     * @throws DriverNotFoundException
     */
    protected function validateDriver() : void
    {
        if ($this->driver === '') {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        $class = $this->config['map'][$this->driver] ?? null;

        if (empty($this->config['drivers'][$this->driver]) || $class === null) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (! class_exists($class)) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
        }

        if (! is_subclass_of($class, Driver::class)) {
            throw new DriverNotFoundException(sprintf('Driver [%s] must be an instance of %s.', $class, Driver::class));
        }
    }
}
