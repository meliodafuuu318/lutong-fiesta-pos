<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Pushed to the devices of everyone who should know that an order changed.
 *
 * $reason says how, the same words as the TransactionUpdated event: edited, payment or delivered.
 * $detail is an optional extra line for the body (e.g. the amount of a payment).
 *
 * Not queued, for the same reason as NewTransactionNotification: the app runs on Vercel, where
 * nothing processes a queue.
 */
class TransactionUpdatedNotification extends Notification
{
    public const REASON_EDITED = 'edited';
    public const REASON_PAYMENT = 'payment';
    public const REASON_DELIVERED = 'delivered';

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string $reason,
        private readonly ?string $detail = null,
    ) {
    }

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        // Riders can't open the transaction page, so they're sent to their dashboard instead.
        $url = $notifiable instanceof User && $notifiable->isRider()
            ? route('dashboard')
            : route('transactions.show', $this->transaction);

        $body = $this->transaction->reference_number . ' · ' . ($this->detail ?? '₱' . number_format((float) $this->transaction->total, 2));

        return (new WebPushMessage)
            ->title(match ($this->reason) {
                self::REASON_PAYMENT => 'Payment received',
                self::REASON_DELIVERED => 'Order delivered',
                default => 'Order updated',
            })
            ->icon('/images/apple-touch-icon.png')
            ->badge('/images/favicon.png')
            ->body($body)
            // One notification per order and kind of change, so a new payment doesn't bury a
            // "new order" alert, while a second payment replaces (and re-alerts over) the first.
            ->tag('transaction-' . $this->transaction->id . '-' . $this->reason)
            ->data(['url' => $url]);
    }
}
