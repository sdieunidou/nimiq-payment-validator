<?php

namespace Tests\HostMe\NimiqLib\Model;

use HostMe\NimiqLib\Model\PaymentResult;
use HostMe\NimiqLib\Model\PaymentState;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PaymentResultTest extends TestCase
{
    public function testPaymentResultWithMessage(): void
    {
        $state = PaymentState::FAILED;
        $message = 'Transaction failed due to insufficient funds.';

        $paymentResult = new PaymentResult($state, $message);

        $this->assertSame($state, $paymentResult->getState());
        $this->assertSame($message, $paymentResult->getMessage());
    }

    public function testPaymentResultWithoutMessage(): void
    {
        $state = PaymentState::PAID;

        $paymentResult = new PaymentResult($state);

        $this->assertSame($state, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }
}
