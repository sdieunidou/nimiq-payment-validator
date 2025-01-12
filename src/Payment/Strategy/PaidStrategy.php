<?php

namespace HostMe\NimiqLib\Payment\Strategy;

use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;

class PaidStrategy implements PaymentStateStrategyInterface
{
    private int $minConfirmations;

    public function __construct(int $minConfirmations = 120)
    {
        $this->minConfirmations = $minConfirmations;
    }

    public function matches(float $expectedAmount, Transaction $transaction): bool
    {
        return $expectedAmount === $transaction->getValueWithDigits()
        && $transaction->getConfirmations() >= $this->minConfirmations;
    }

    public function getState(): string
    {
        return PaymentState::PAID;
    }

    public function getMessage(): ?string
    {
        return null; // No message needed for PAID.
    }
}
