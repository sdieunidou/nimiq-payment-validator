<?php

namespace HostMe\NimiqLib\Payment\Strategy;

use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;

class OverpaidStrategy implements PaymentStateStrategyInterface
{
    private float $overpaidThreshold;

    private int $minConfirmations;

    public function __construct(float $overpaidThreshold, int $minConfirmations = 120)
    {
        $this->overpaidThreshold = $overpaidThreshold;
        $this->minConfirmations = $minConfirmations;
    }

    public function matches(float $expectedAmount, Transaction $transaction): bool
    {
        $transactionValue = $transaction->getValueWithDigits();

        return $transactionValue > $expectedAmount
            && ($transactionValue <= $expectedAmount + $this->overpaidThreshold)
            && $transaction->getConfirmations() >= $this->minConfirmations;
    }

    public function getState(): string
    {
        return PaymentState::OVERPAID;
    }

    public function getMessage(): ?string
    {
        return 'Payment amount exceeds the required amount.';
    }
}
