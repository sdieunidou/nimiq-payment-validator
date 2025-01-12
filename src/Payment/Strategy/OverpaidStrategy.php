<?php

namespace Seb\NimiqLib\Payment\Strategy;

use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;

class OverpaidStrategy implements PaymentStateStrategyInterface
{
    private float $overpaidThreshold;

    public function __construct(float $overpaidThreshold)
    {
        $this->overpaidThreshold = $overpaidThreshold;
    }

    public function matches(float $expectedAmount, Transaction $transaction): bool
    {
        $transactionValue = $transaction->getValueWithDigits();

        return $transactionValue > $expectedAmount
            && ($transactionValue <= $expectedAmount + $this->overpaidThreshold);
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
