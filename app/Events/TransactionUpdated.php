<?php

namespace App\Events;

/** A transaction changed. $reason says how: edited, payment, delivered, cancelled or refunded. */
class TransactionUpdated extends PosEvent
{
}
