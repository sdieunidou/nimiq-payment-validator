<?php

namespace Tests\HostMe\NimiqLib\Model;

use HostMe\NimiqLib\Model\PaymentState;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PaymentStateTest extends TestCase
{
    public function testPaymentStateConstants(): void
    {
        $this->assertSame('PAID', PaymentState::PAID);
        $this->assertSame('OVERPAID', PaymentState::OVERPAID);
        $this->assertSame('UNDERPAID', PaymentState::UNDERPAID);
        $this->assertSame('FAILED', PaymentState::FAILED);
        $this->assertSame('NOT_FOUND', PaymentState::NOT_FOUND);
    }
}
