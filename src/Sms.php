<?php

namespace Shetabit\Sms;

use Shetabit\Sms\Contracts\Driver;
use Shetabit\Sms\Contracts\Message as MessageContract;
use Shetabit\Sms\Exceptions\DriverNotFoundException;
use Shetabit\Sms\Exceptions\InvalidMessageException;

class Sms
{
    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Driver Settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Driver Name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Driver Instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * Recipients
     *
     * @param array
     */
    protected $recipients = [];

    /**
     * Message.
     *
     * @var Message
     */
    protected $message;

    /**
     * Sms constructor.
     *
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->via($this->config['default']);
    }

    /**
     * Set custom configs
     * we can use this method when we want to use dynamic configs
     *
     * @param $key
     * @param $value|null
     *
     * @return $this
     */
    public function config($key, $value = null)
    {
        $configs = [];

        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $configs[$k] = $v;
        }

        $this->settings = array_merge($this->settings, $configs);

        return $this;
    }

    /**
     * Change the driver on the fly.
     *
     * @param $driver
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function via($driver)
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->settings = $this->config['drivers'][$driver];

        return $this;
    }

    /**
     * Set recipients.
     *
     * @param array $recipients
     *
     * @return self
     */
    public function to(array $recipients) : self
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * @alias to
     *
     * @return self
     */
    public function recipients(array $recipients) : self
    {
        return $this->recipients($recipients);
    }

    /**
     * Set message instance.
     *
     * @param string|Message $message
     *
     * @return self
     */
    public function message($message)
    {
        $this->message = is_string($message) ? new Message($message) : $message;

        return $this;
    }

    /**
     * Sends the sms message.
     *
     * @param $finalizeCallback|null
     *
     * @return ReceiptInterface
     *
     * @throws InvalidMessageException
     */
    public function send()
    {
        $this->validateMessage();

        $this
            ->getDriverInstance()
            ->to($this->recipients)
            ->message($this->message)
            ->send();
    }

    /**
     * Validate Message.
     *
     * @throws InvalidMessageException
     */
    protected function validateMessage()
    {
        if (empty($this->message) || ! $this->message instanceof MessageContract) {
            throw new InvalidMessageException('Message not selected or does not exist.');
        }
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getDriverInstance()
    {
        if (!empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();
        $class = $this->config['map'][$this->driver];

        return new $class($this->settings);
    }

    /**
     * Validate driver.
     *
     * @throws \Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('Driver not selected or default driver does not exist.');
        }

        if (empty($this->config['drivers'][$this->driver]) || empty($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver not found in config file. Try updating the package.');
        }

        if (!class_exists($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('Driver source not found. Please update the package.');
        }

        $reflect = new \ReflectionClass($this->config['map'][$this->driver]);

        if (!$reflect->implementsInterface(Driver::class)) {
            throw new \Exception("Driver must be an instance of Contracts\Driver.");
        }
    }
}
