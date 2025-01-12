<?php

namespace HostMe\NimiqLib\Model;

class PaymentResult
{
    private string $state;
    private ?string $message;

    /**
     * Constructor.
     *
     * @param string      $state   The payment state (e.g., PAID, FAILED).
     * @param null|string $message an optional message providing additional context
     */
    public function __construct(string $state, ?string $message = null)
    {
        $this->state = $state;
        $this->message = $message;
    }

    /**
     * Retrieves the payment state.
     *
     * @return string the payment state
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Retrieves the payment message.
     *
     * @return null|string the payment message, if any
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}
