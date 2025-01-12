<?php

namespace HostMe\NimiqLib\Validator;

use HostMe\NimiqLib\Model\PaymentResult;

interface TransactionValidatorInterface
{
    /**
     * Validates a transaction and returns the payment result.
     *
     * @param string $transactionHash the hash of the transaction to validate
     * @param string $expectedAmount  the expected amount for the transaction
     *
     * @return PaymentResult the result of the payment validation
     */
    public function validateTransaction(string $transactionHash, string $expectedAmount): PaymentResult;
}
