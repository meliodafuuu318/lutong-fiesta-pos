<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Pushed to the devices of everyone who should know about a new order.
 *
 * Not queued: the app runs on Vercel, where nothing processes a queue, so it's sent
 * straight away (see TransactionService::notifyCreated).
 */
class NewTransactionNotification extends Notification
{
    public function __construct(private readonly Transaction $transaction)
    {
    }

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $isDelivery = $this->transaction->type === 'DELIVERY';

        // Riders can't open the transaction page, so they're sent to their dashboard instead.
        $url = $notifiable instanceof User && $notifiable->isRider()
            ? route('dashboard')
            : route('transactions.show', $this->transaction);

        return (new WebPushMessage)
            ->title($isDelivery ? 'New delivery order' : 'New walk-in order')
            ->icon('/images/apple-touch-icon.png')
            ->badge('/images/favicon.png')
            ->body($this->transaction->reference_number . ' · ₱' . number_format((float) $this->transaction->total, 2))
            ->tag('transaction-' . $this->transaction->id)
            ->data(['url' => $url]);
    }
}
