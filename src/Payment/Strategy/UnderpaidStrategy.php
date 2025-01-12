<?php

namespace Seb\NimiqLib\Payment\Strategy;

use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;

class UnderpaidStrategy implements PaymentStateStrategyInterface
{
    private float $underpaidThreshold;

    public function __construct(float $underpaidThreshold)
    {
        $this->underpaidThreshold = $underpaidThreshold;
    }

    public function matches(float $expectedAmount, Transaction $transaction): bool
    {
        $transactionValue = $transaction->getValueWithDigits();

        return $transactionValue < $expectedAmount
            && ($transactionValue >= $expectedAmount - $this->underpaidThreshold);
    }

    public function getState(): string
    {
        return PaymentState::UNDERPAID;
    }

    public function getMessage(): ?string
    {
        return 'Payment amount is less than the required amount.';
    }
}
