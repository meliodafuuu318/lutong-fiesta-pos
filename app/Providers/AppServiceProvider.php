<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\Events\NotificationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // The push service refusing a message doesn't throw, so without this it would fail silently.
        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            Log::warning('Push delivery failed.', [
                'status' => $event->report->getResponse()?->getStatusCode(),
                'reason' => $event->report->getReason(),
                'endpoint_host' => parse_url($event->report->getEndpoint(), PHP_URL_HOST),
                'user_id' => $event->subscription->subscribable_id,
            ]);
        });
    }
}
