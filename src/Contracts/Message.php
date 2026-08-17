<?php

namespace Shetabit\Sms\Contracts;

interface Message
{
    public function toString() : string;

    /**
     * @param array<int|string, mixed> $params
     */
    public function useTemplateIfSupports(int|string $templateIdentifier, array $params) : static;

    public function usesTemplate() : bool;

    /**
     * @return array{identifier: int|string|null, params: array<int|string, mixed>}
     */
    public function getTemplate() : array;
}
