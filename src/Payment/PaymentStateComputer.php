<?php

namespace HostMe\NimiqLib\Payment;

use HostMe\NimiqLib\Model\PaymentResult;
use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;
use HostMe\NimiqLib\Payment\Strategy\PaymentStateStrategyInterface;

class PaymentStateComputer
{
    /**
     * @var PaymentStateStrategyInterface[]
     */
    private array $strategies;

    /**
     * PaymentUtils constructor.
     *
     * @param PaymentStateStrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * Determines the payment state based on comparison and thresholds.
     */
    public function determinePaymentState(float $expectedAmount, Transaction $transaction): PaymentResult
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->matches($expectedAmount, $transaction)) {
                return new PaymentResult($strategy->getState(), $strategy->getMessage());
            }
        }

        // Default state if no strategy matches.
        return new PaymentResult(PaymentState::FAILED);
    }
}
