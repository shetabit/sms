<?php

namespace Shetabit\Sms;

use Shetabit\Sms\Contracts\Message as MessageContract;

class Message implements MessageContract
{
    protected int|string|null $templateIdentifier = null;

    /**
     * @var array<int|string, mixed>
     */
    protected array $templateParams = [];

    public function __construct(protected readonly string $message)
    {
    }

    public function toString() : string
    {
        return $this->message;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function useTemplateIfSupports(int|string $templateIdentifier, array $params) : static
    {
        $this->templateIdentifier = $templateIdentifier;
        $this->templateParams = $params;

        return $this;
    }

    public function usesTemplate() : bool
    {
        return $this->templateIdentifier !== null;
    }

    /**
     * @return array{identifier: int|string|null, params: array<int|string, mixed>}
     */
    public function getTemplate() : array
    {
        return [
            'identifier' => $this->templateIdentifier,
            'params' => $this->templateParams,
        ];
    }
}
