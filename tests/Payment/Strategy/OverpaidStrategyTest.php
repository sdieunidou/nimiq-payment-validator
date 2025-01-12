<?php

namespace Tests\HostMe\NimiqLib\Payment\Strategy;

use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;
use HostMe\NimiqLib\Payment\Strategy\OverpaidStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OverpaidStrategyTest extends TestCase
{
    private OverpaidStrategy $strategy;
    private float $overpaidThreshold = 100.0;

    protected function setUp(): void
    {
        $this->strategy = new OverpaidStrategy($this->overpaidThreshold, 0);
    }

    public function testMatchesWithinThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(550.0); // Overpaid by 50.0 <= threshold

        $this->assertTrue($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchExceedsThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(650.0); // Overpaid by 150.0 > threshold

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchExactAmount(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(500.0); // Exact amount

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testMatchesAtUpperThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(600.0); // Overpaid by 100.0 == threshold

        $this->assertTrue($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchUnderpaidTransaction(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(450.0); // Underpaid

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testGetState(): void
    {
        $this->assertSame(PaymentState::OVERPAID, $this->strategy->getState());
    }

    public function testGetMessage(): void
    {
        $this->assertSame('Payment amount exceeds the required amount.', $this->strategy->getMessage());
    }

    public function testMatchesZeroThreshold(): void
    {
        $strategyZeroThreshold = new OverpaidStrategy(0.0);
        $expectedAmount = 500.0;

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(501.0); // Overpaid by 1.0 > 0.0

        $this->assertFalse($strategyZeroThreshold->matches($expectedAmount, $transaction));

        $transaction->method('getValueWithDigits')->willReturn(500.0); // Exact amount
        $this->assertFalse($strategyZeroThreshold->matches($expectedAmount, $transaction));
    }
}
