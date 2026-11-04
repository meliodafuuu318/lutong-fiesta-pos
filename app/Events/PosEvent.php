<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

/**
 * Base of every realtime event: "something changed, reload your data".
 *
 * Broadcast right away (ShouldBroadcastNow) rather than queued: the app runs on Vercel, where
 * nothing processes a queue. The payload only carries ids, never order or money details; the
 * page reloads through its normal, authorised routes.
 */
abstract class PosEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int|string|null $id = null,
        public readonly ?string $reason = null,
    ) {
    }

    /** The name the browser listens for: the class name, without a namespace. */
    public function broadcastAs(): string
    {
        return class_basename($this);
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('pos')];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['id' => $this->id, 'reason' => $this->reason];
    }

    /**
     * Dispatch, but never let a broadcasting problem (Pusher down, bad keys) fail the
     * request that caused it: the data is already saved.
     */
    public static function fire(int|string|null $id = null, ?string $reason = null): void
    {
        try {
            static::dispatch($id, $reason);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
