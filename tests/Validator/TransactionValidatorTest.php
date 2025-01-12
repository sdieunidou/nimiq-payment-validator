<?php

namespace Tests\Seb\NimiqLib\Validator;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Seb\NimiqLib\Exception\InvalidTransactionHashException;
use Seb\NimiqLib\Model\PaymentResult;
use Seb\NimiqLib\Model\PaymentState;
use Seb\NimiqLib\Model\Transaction;
use Seb\NimiqLib\Payment\Strategy\OverpaidStrategy;
use Seb\NimiqLib\Payment\Strategy\PaidStrategy;
use Seb\NimiqLib\Payment\Strategy\UnderpaidStrategy;
use Seb\NimiqLib\Validator\Gateway\ApiGatewayInterface;
use Seb\NimiqLib\Validator\TransactionValidator;

/**
 * @internal
 *
 * @coversNothing
 */
class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;
    private ApiGatewayInterface $apiGateway;
    private LoggerInterface $logger;
    private array $strategies;

    protected function setUp(): void
    {
        $this->apiGateway = $this->createMock(ApiGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->underpaidThreshold = 50000.0;
        $this->overpaidThreshold = 50000.0;

        $this->strategies = [
            new UnderpaidStrategy($this->underpaidThreshold),
            new OverpaidStrategy($this->overpaidThreshold),
            new PaidStrategy(),
        ];

        $this->validator = new TransactionValidator(
            $this->apiGateway,
            'NQ01 RECEIVER',
            $this->strategies,
            $this->logger
        );
    }

    public function testValidateTransactionWithInvalidHash(): void
    {
        $invalidHash = 'INVALID_HASH!'; // Non-hexadecimal

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Invalid transaction hash provided.',
                ['transactionHash' => $invalidHash]
            )
        ;

        $this->expectException(InvalidTransactionHashException::class);
        $this->expectExceptionMessage('Invalid hash (expected hexadecimal).');

        $this->validator->validateTransaction($invalidHash, '500000');
    }

    public function testValidateTransactionNotFound(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn(null)
        ;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Transaction not found.',
                ['transactionHash' => $transactionHash]
            )
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::NOT_FOUND, $paymentResult->getState());
        $this->assertSame('Transaction not found.', $paymentResult->getMessage());
    }

    public function testValidateTransactionRecipientMismatch(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ02 WRONG_RECEIVER', // Mismatch
            500000 * 100000,
            '',
            123456,
            1672531200,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Recipient address mismatch.',
                [
                    'transactionHash' => $transactionHash,
                    'expectedAddress' => 'NQ01 RECEIVER',
                    'actualAddress' => 'NQ02 WRONG_RECEIVER',
                ]
            )
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertSame('Transaction recipient address does not match.', $paymentResult->getMessage());
    }

    public function testValidateTransactionPaid(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            500000 * 100000,
            '',
            123457,
            1672531300,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::PAID, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testValidateTransactionUnderpaidWithinThreshold(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            450000 * 100000, // Underpaid by 50000 <= threshold
            '',
            123458,
            1672531400,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::UNDERPAID, $paymentResult->getState());
        $this->assertSame('Payment amount is less than the required amount.', $paymentResult->getMessage());
    }

    public function testValidateTransactionOverpaidWithinThreshold(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            550000 * 100000, // Overpaid by 50000 <= threshold
            '',
            123459,
            1672531500,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::OVERPAID, $paymentResult->getState());
        $this->assertSame('Payment amount exceeds the required amount.', $paymentResult->getMessage());
    }

    public function testValidateTransactionUnderpaidExceedsThreshold(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            350000 * 100000, // Underpaid by 150000 > threshold
            '',
            123460,
            1672531600,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testValidateTransactionOverpaidExceedsThreshold(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            650000 * 100000, // Overpaid by 150000 > threshold
            '',
            123461,
            1672531700,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertInstanceOf(PaymentResult::class, $paymentResult);
        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }

    public function testValidateTransactionRecipientMismatchWithOverpaidThresholdExceeded(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12'; // Valid hex
        $expectedAmount = 500000;

        $transaction = new Transaction(
            'abcdef1234567890abcdef1234567890abcdef12',
            'NQXX SENDER',
            'NQ01 RECEIVER',
            700000 * 100000, // Overpaid by 200000 > threshold
            '',
            123462,
            1672531800,
            null
        );

        $this->apiGateway->expects($this->once())
            ->method('getTransactionByHash')
            ->with($transactionHash)
            ->willReturn($transaction)
        ;

        $paymentResult = $this->validator->validateTransaction($transactionHash, $expectedAmount);

        $this->assertSame(PaymentState::FAILED, $paymentResult->getState());
        $this->assertNull($paymentResult->getMessage());
    }
}
