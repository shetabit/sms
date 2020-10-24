<?php

namespace Shetabit\Sms;

use Shetabit\Sms\Contracts\Message as MessageContract;

Class Message implements MessageContract
{
    /**
     * Plain text message
     *
     * @param string
     */
    protected $message;

    /**
     * Template options.
     *
     * @var array
     */
    protected $template = [
        'identifier' => null,
        'params' => null,
    ];

    /**
     * Message constructor
     *
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Retrieve string format of message.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->message;
    }

    /**
     * Retrieve string format of message.
     *
     * @param int|string $templateIdentifier
     * @param array $params
     *
     * @return self
     */
    public function useTemplateIfSupports($templateIdentifier, array $params)
    {
        $this->template['identifier'] = $templateIdentifier;
        $this->template['params'] = $params;

        return $this;
    }

    /**
     * Determine if message uses a template.
     */
    public function usesTemplate() : bool
    {
        return ! is_null($this->template['identifier']);
    }

    /**
     * Retrieve template options.
     *
     * @return array
     */
    public function getTemplate() : array
    {
        return $this->template;
    }
}
