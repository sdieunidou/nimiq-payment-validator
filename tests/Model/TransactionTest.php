<?php

namespace Tests\HostMe\NimiqLib\Model;

use HostMe\NimiqLib\Model\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class TransactionTest extends TestCase
{
    public function testTransactionGetters(): void
    {
        // Arrange
        $hash = 'abcdef1234567890abcdef1234567890abcdef12';
        $senderAddress = 'NQXX SENDER';
        $recipientAddress = 'NQ01 RECEIVER';
        $value = '500000'; // smallest units
        $message = 'Payment for services';
        $height = 123456;
        $timestamp = 1672531200;
        $extra = ['key' => 'value'];

        // Act
        $transaction = new Transaction(
            $hash,
            $senderAddress,
            $recipientAddress,
            $value,
            $message,
            $height,
            $timestamp,
            $extra
        );

        // Assert
        $this->assertSame($hash, $transaction->getHash());
        $this->assertSame($senderAddress, $transaction->getSenderAddress());
        $this->assertSame($recipientAddress, $transaction->getRecipientAddress());
        $this->assertSame($value, $transaction->getValue());
        $this->assertSame($message, $transaction->getMessage());
        $this->assertSame($height, $transaction->getHeight());
        $this->assertSame($timestamp, $transaction->getTimestamp());
        $this->assertSame($extra, $transaction->getExtra());

        $this->assertSame(5.0, $transaction->getValueWithDigits());
    }

    public function testTransactionWithNoExtra(): void
    {
        // Arrange
        $hash = '1234567890abcdef1234567890abcdef12345678';
        $senderAddress = 'NQXX SENDER';
        $recipientAddress = 'NQ01 RECEIVER';
        $value = '750000'; // smallest units
        $message = '';
        $height = 123457;
        $timestamp = 1672531300;
        $extra = null;

        // Act
        $transaction = new Transaction(
            $hash,
            $senderAddress,
            $recipientAddress,
            $value,
            $message,
            $height,
            $timestamp,
            $extra
        );

        // Assert
        $this->assertSame($hash, $transaction->getHash());
        $this->assertSame($senderAddress, $transaction->getSenderAddress());
        $this->assertSame($recipientAddress, $transaction->getRecipientAddress());
        $this->assertSame($value, $transaction->getValue());
        $this->assertSame($message, $transaction->getMessage());
        $this->assertSame($height, $transaction->getHeight());
        $this->assertSame($timestamp, $transaction->getTimestamp());
        $this->assertNull($transaction->getExtra());

        $this->assertSame(7.5, $transaction->getValueWithDigits());
    }
}
