<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only lets a request through when it carries the CRON_SECRET as a bearer token,
 * which is what Vercel Cron sends. The secret is only ever read from the
 * Authorization header, never from the URL, and it is never logged.
 */
class VerifyCronSecret
{
    /** Vercel recommends at least 16 characters; the backup holds sales and customer data, so require more. */
    private const MIN_SECRET_LENGTH = 32;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('backup.transactions.cron_secret');

        // No (or a weak) secret configured: nobody gets in, instead of everybody.
        if (strlen($secret) < self::MIN_SECRET_LENGTH) {
            Log::error('Cron request refused: CRON_SECRET is not set, or is shorter than '.self::MIN_SECRET_LENGTH.' characters.');

            return response()->json(['message' => 'Cron is not configured.'], 503);
        }

        $token = (string) $request->bearerToken();

        if ($token === '' || ! hash_equals($secret, $token)) {
            Log::warning('Cron request refused: missing or wrong bearer token.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
