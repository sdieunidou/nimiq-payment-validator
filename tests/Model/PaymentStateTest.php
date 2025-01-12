<?php

namespace Tests\Seb\NimiqLib\Model;

use PHPUnit\Framework\TestCase;
use Seb\NimiqLib\Model\PaymentState;

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
