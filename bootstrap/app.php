<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/dashboard');
        $middleware->alias(['role' => EnsureUserHasRole::class]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // TEMPORARY, for the Vercel 500 on /login: one short line with the real cause
        // first. Vercel cuts off long log lines, and a full stack trace buries the
        // message. stop() also skips Laravel's own (long) log entry for the error.
        // Remove this once login works.
        $exceptions->report(function (\Throwable $e): void {
            $root = $e;
            while ($root->getPrevious()) {
                $root = $root->getPrevious();
            }

            Log::error(sprintf(
                'LOGIN-DEBUG root=[%s] %s | top=[%s] %s | at %s:%d',
                (new \ReflectionClass($root))->getShortName(),
                mb_strimwidth($root->getMessage(), 0, 400, '…'),
                (new \ReflectionClass($e))->getShortName(),
                mb_strimwidth($e->getMessage(), 0, 200, '…'),
                basename($e->getFile()),
                $e->getLine(),
            ));
        })->stop();
    })->create();
