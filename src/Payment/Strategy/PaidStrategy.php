<?php

namespace Seb\NimiqLib\Payment\Strategy;

use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;

class PaidStrategy implements PaymentStateStrategyInterface
{
    public function matches(float $expectedAmount, Transaction $transaction): bool
    {
        return $expectedAmount === $transaction->getValueWithDigits();
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
