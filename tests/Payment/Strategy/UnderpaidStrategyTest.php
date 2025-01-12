<?php

namespace Tests\Seb\NimiqLib\Payment\Strategy;

use PHPUnit\Framework\TestCase;
use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;
use Seb\NimiqLib\Payment\Strategy\UnderpaidStrategy;

/**
 * @internal
 *
 * @coversNothing
 */
class UnderpaidStrategyTest extends TestCase
{
    private UnderpaidStrategy $strategy;
    private float $underpaidThreshold = 100.0;

    protected function setUp(): void
    {
        $this->strategy = new UnderpaidStrategy($this->underpaidThreshold);
    }

    public function testMatchesWithinThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(450.0); // Underpaid by 50.0 <= threshold

        $this->assertTrue($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchExceedsThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(350.0); // Underpaid by 150.0 > threshold

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchExactAmount(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(500.0); // Exact amount

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testMatchesAtLowerThreshold(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(400.0); // Underpaid by 100.0 == threshold

        $this->assertTrue($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testDoesNotMatchOverpaidTransaction(): void
    {
        $expectedAmount = 500.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(550.0); // Overpaid

        $this->assertFalse($this->strategy->matches($expectedAmount, $transaction));
    }

    public function testGetState(): void
    {
        $this->assertSame(PaymentState::UNDERPAID, $this->strategy->getState());
    }

    public function testGetMessage(): void
    {
        $this->assertSame('Payment amount is less than the required amount.', $this->strategy->getMessage());
    }

    public function testMatchesZeroThreshold(): void
    {
        $strategyZeroThreshold = new UnderpaidStrategy(0.0);
        $expectedAmount = 500.0;

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(499.99); // Underpaid by 0.01 > 0.0

        $this->assertFalse($strategyZeroThreshold->matches($expectedAmount, $transaction));

        $transaction->method('getValueWithDigits')->willReturn(500.0); // Exact amount
        $this->assertFalse($strategyZeroThreshold->matches($expectedAmount, $transaction));
    }
}
