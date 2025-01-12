<?php

namespace Tests\HostMe\NimiqLib\Payment\Strategy;

use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PaidStrategyTest extends TestCase
{
    private PaidStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new PaidStrategy();
    }

    public function testMatchesExactAmount(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(5.0);

        $this->assertTrue($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchDifferentAmount(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(4.999);

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));

        $transaction->method('getValueWithDigits')->willReturn(5.001);
        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testGetState(): void
    {
        $this->assertSame(PaymentState::PAID, $this->strategy->getState());
    }

    public function testGetMessage(): void
    {
        $this->assertNull($this->strategy->getMessage());
    }
}
