<?php

namespace Tests\HostMe\NimiqLib\Payment;

use HostMe\NimiqLib\Model\PaymentResult;
use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Model\Transaction;
use HostMe\NimiqLib\Payment\PaymentStateComputer;
use HostMe\NimiqLib\Payment\Strategy\OverpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\UnderpaidStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class PaymentStateComputerTest extends TestCase
{
    private PaymentStateComputer $computer;
    private float $underpaidThreshold = 0.5;
    private float $overpaidThreshold = 0.5;

    protected function setUp(): void
    {
        $strategies = [
            new UnderpaidStrategy($this->underpaidThreshold, 0),
            new OverpaidStrategy($this->overpaidThreshold, 0),
            new PaidStrategy(120),
        ];

        $this->computer = new PaymentStateComputer($strategies);
    }

    public function testDeterminePaymentStatePaidWithoutConfirmation(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(5.0);
        $transaction->method('getConfirmations')->willReturn(10);

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testDeterminePaymentStatePaid(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(5.0);
        $transaction->method('getConfirmations')->willReturn(150);

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::PAID, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testDeterminePaymentStateUnderpaid(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(4.5); // Underpaid by 0.5 <= threshold

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::UNDERPAID, $paymentResult->getState());
        $this->assertSame('Payment amount is less than the required amount.', $paymentResult->getMessage());
    }

    public function testDeterminePaymentStateOverpaid(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(5.5); // Overpaid by 0.5 <= threshold

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::OVERPAID, $paymentResult->getState());
        $this->assertSame('Payment amount exceeds the required amount.', $paymentResult->getMessage());
    }

    public function testDeterminePaymentStateFailedUnderpaidExceedsThreshold(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(3.5); // Underpaid by 1.5 > threshold

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testDeterminePaymentStateFailedOverpaidExceedsThreshold(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(6.5); // Overpaid by 1.5 > threshold

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testDeterminePaymentStateNoStrategyMatch(): void
    {
        $expectedAmount = 5.0;
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getValueWithDigits')->willReturn(7.0); // Overpaid by 2.0 > threshold

        $paymentResult = $this->computer->determinePaymentState($expectedAmount, $transaction);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }
}
