<?php

namespace Seb\NimiqLib\Payment\Strategy;

use Seb\NimiqLib\Model\Transaction;

interface PaymentStateStrategyInterface
{
    /**
     * Determines if the strategy matches the given comparison and thresholds.
     */
    public function matches(float $expectedAmount, Transaction $transaction): bool;

    /**
     * Retrieves the corresponding payment state.
     *
     * @return string the payment state constant
     */
    public function getState(): string;

    /**
     * Retrieves an optional message explaining the payment state.
     *
     * @return null|string the payment message, if any
     */
    public function getMessage(): ?string;
}
