<?php

namespace Tests\Seb\NimiqLib\Payment\Strategy;

use PHPUnit\Framework\TestCase;
use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;
use Seb\NimiqLib\Payment\Strategy\PaidStrategy;

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
