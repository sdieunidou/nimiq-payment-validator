<?php

namespace HostMe\NimiqLib\Validator;

use HostMe\NimiqLib\Exception\InvalidTransactionHashException;
use HostMe\NimiqLib\Model\PaymentResult;
use HostMe\NimiqLib\Model\PaymentState;
use HostMe\NimiqLib\Payment\PaymentStateComputer;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\PaymentStateStrategyInterface;
use HostMe\NimiqLib\Validator\Gateway\ApiGatewayInterface;
use Psr\Log\LoggerInterface;

class TransactionValidator implements TransactionValidatorInterface
{
    private ApiGatewayInterface $apiGateway;
    private string $receiverAddress;
    private PaymentStateComputer $paymentStateComputer;
    private LoggerInterface $logger;

    /**
     * TransactionValidator constructor.
     *
     * @param ApiGatewayInterface             $apiGateway      the API gateway to interact with Nimiq
     * @param string                          $receiverAddress the receiver's Nimiq address
     * @param PaymentStateStrategyInterface[] $strategies      optional array of payment state strategies
     * @param LoggerInterface                 $logger          PSR-3 compliant logger
     */
    public function __construct(
        ApiGatewayInterface $apiGateway,
        string $receiverAddress,
        array $strategies = [],
        LoggerInterface $logger
    ) {
        $this->apiGateway = $apiGateway;
        $this->receiverAddress = $receiverAddress;
        $this->logger = $logger;

        // If no strategies are provided, use the default ones.
        if (empty($strategies)) {
            $strategies = [
                new PaidStrategy(),
            ];
        }

        $this->paymentStateComputer = new PaymentStateComputer($strategies);
    }

    public function validateTransaction(string $transactionHash, string $expectedAmount): PaymentResult
    {
        if (!ctype_xdigit($transactionHash)) {
            $this->logger->error('Invalid transaction hash provided.', ['transactionHash' => $transactionHash]);

            throw new InvalidTransactionHashException('Invalid hash (expected hexadecimal).');
        }

        $transaction = $this->apiGateway->getTransactionByHash($transactionHash);
        if (!$transaction) {
            $this->logger->warning('Transaction not found.', ['transactionHash' => $transactionHash]);

            return new PaymentResult(PaymentState::NOT_FOUND, 'Transaction not found.');
        }

        if ($transaction->getRecipientAddress() !== $this->receiverAddress) {
            $this->logger->warning('Recipient address mismatch.', [
                'transactionHash' => $transactionHash,
                'expectedAddress' => $this->receiverAddress,
                'actualAddress' => $transaction->getRecipientAddress(),
            ]);

            return new PaymentResult(PaymentState::FAILED, 'Transaction recipient address does not match.');
        }

        return $this->paymentStateComputer->determinePaymentState(
            $expectedAmount,
            $transaction,
        );
    }
}
