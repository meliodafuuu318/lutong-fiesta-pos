<?php

namespace App\Exceptions;

use Exception;

/**
 * A transaction couldn't be created because of a business rule, not bad input.
 */
class TransactionException extends Exception
{
    public function __construct(string $message, public readonly ?int $dailyItemId = null)
    {
        parent::__construct($message);
    }
}
