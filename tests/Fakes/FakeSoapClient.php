<?php

namespace Shetabit\Sms\Tests\Fakes;

use SoapClient;

/**
 * A soap client in non-wsdl mode, which never reaches out for a wsdl document.
 */
class FakeSoapClient extends SoapClient
{
    /**
     * @var array<int, array{name: string, arguments: array<int, mixed>}>
     */
    public array $calls = [];

    public function __construct(public mixed $answer = 'sent')
    {
        parent::__construct(null, ['location' => 'http://127.0.0.1/soap', 'uri' => 'urn:test']);
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $name, array $args) : mixed
    {
        $this->calls[] = ['name' => $name, 'arguments' => $args];

        return $this->answer;
    }
}
