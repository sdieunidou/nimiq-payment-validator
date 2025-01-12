<?php

namespace Seb\NimiqLib\Model;

class Transaction
{
    private string $hash;
    private string $senderAddress;
    private string $recipientAddress;
    private string $value;
    private string $message;
    private int $height;
    private int $timestamp;
    private ?array $extra;

    /**
     * Constructor.
     */
    public function __construct(
        string $hash,
        string $senderAddress,
        string $recipientAddress,
        string $value,
        string $message,
        int $height,
        int $timestamp,
        ?array $extra = null
    ) {
        $this->hash = $hash;
        $this->senderAddress = $senderAddress;
        $this->recipientAddress = $recipientAddress;
        $this->value = $value;
        $this->message = $message;
        $this->height = $height;
        $this->timestamp = $timestamp;
        $this->extra = $extra;
    }

    /**
     * Gets the transaction hash.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Gets the sender's address.
     */
    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * Gets the recipient's address.
     */
    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    /**
     * Gets the value of the transaction in smallest units.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getValueWithDigits(): float
    {
        return $this->value / 100000;
    }

    /**
     * Gets the message attached to the transaction.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the block height of the transaction.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Gets the timestamp of the transaction.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Gets any extra data attached to the transaction.
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }
}
