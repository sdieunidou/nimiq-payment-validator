<?php

namespace HostMe\NimiqLib\Model;

class PaymentState
{
    public const PAID = 'PAID';
    public const OVERPAID = 'OVERPAID';
    public const UNDERPAID = 'UNDERPAID';
    public const FAILED = 'FAILED';
    public const NOT_FOUND = 'NOT_FOUND';
}
