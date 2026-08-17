<?php

namespace Shetabit\Sms\Tests\Unit\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;
use Shetabit\Sms\Abstracts\Driver;
use Shetabit\Sms\Contracts\Message;
use Shetabit\Sms\Tests\TestCase;

/**
 * Base class of the driver tests.
 *
 * Every driver talks to its gateway through the client in its `$client` property,
 * which the tests replace with one that answers from a queue of prepared
 * responses. That makes it possible to run a driver end to end without touching
 * the network.
 *
 * The settings a driver receives are the ones from `config/sms.php`, so the tests
 * also notice when a driver and its shipped configuration drift apart.
 */
abstract class DriverTestCase extends TestCase
{
    /**
     * @var array<int, array{request: RequestInterface, response: mixed}>
     */
    protected array $httpHistory = [];

    /**
     * Name of the driver in `config/sms.php`.
     */
    abstract protected function driverName() : string;

    /**
     * Class of the driver under test.
     */
    abstract protected function driverClass() : string;

    /**
     * @param array<string, mixed> $settings
     */
    protected function driver(array $settings = []) : Driver
    {
        $class = $this->driverClass();

        /** @var Driver $driver */
        $driver = new $class($this->settings($settings));

        return $driver;
    }

    /**
     * The settings of the driver, as shipped in `config/sms.php`.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function settings(array $overrides = []) : array
    {
        return array_merge($this->packageConfig()['drivers'][$this->driverName()] ?? [], $overrides);
    }

    /**
     * @param array<int, string> $recipients
     */
    protected function send(Driver $driver, array $recipients = ['+10000000001'], Message|null $message = null) : mixed
    {
        return $driver->to($recipients)->message($message ?? $this->message())->send();
    }

    /**
     * Answer the requests of the given driver from a queue of prepared responses.
     *
     * @param array<int, Response|\Throwable> $responses
     */
    protected function fakeHttp(Driver $driver, array $responses) : void
    {
        $this->httpHistory = [];

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->httpHistory));

        $this->swapClient($driver, new Client(['handler' => $stack]));
    }

    protected function swapClient(Driver $driver, object $client) : void
    {
        new ReflectionProperty($driver, 'client')->setValue($driver, $client);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function jsonResponse(array $body, int $status = 200) : Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode($body));
    }

    /**
     * @param array<string, string> $headers
     */
    protected function response(string $body = '', int $status = 200, array $headers = []) : Response
    {
        return new Response($status, $headers, $body);
    }

    protected function requestCount() : int
    {
        return count($this->httpHistory);
    }

    protected function request(int $index = 0) : RequestInterface
    {
        $this->assertArrayHasKey($index, $this->httpHistory, "The driver did not send a request #$index.");

        return $this->httpHistory[$index]['request'];
    }

    protected function requestUrl(int $index = 0) : string
    {
        return (string) $this->request($index)->getUri();
    }

    protected function requestBody(int $index = 0) : string
    {
        return (string) $this->request($index)->getBody();
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestForm(int $index = 0) : array
    {
        $form = [];
        parse_str($this->requestBody($index), $form);

        return $form;
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestJson(int $index = 0) : array
    {
        $body = json_decode($this->requestBody($index), true);

        $this->assertIsArray($body, "The body of request #$index is not a JSON object.");

        /** @var array<string, mixed> $body */
        return $body;
    }
}
