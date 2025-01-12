<?php

namespace HostMe\NimiqLib\Validator\Gateway;

use HostMe\NimiqLib\Model\Transaction;

interface ApiGatewayInterface
{
    /**
     * Retrieves a transaction by its hash.
     *
     * @return null|Transaction returns the Transaction object or null if not found
     */
    public function getTransactionByHash(string $transactionHash): ?Transaction;
}
