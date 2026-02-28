<?php

namespace App\Providers;

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        if (config('app.debug')) {
            Http::globalRequestMiddleware(function (Request $request): Request {
                $method = $request->getMethod();
                $uri = (string) $request->getUri();
                $body = $request->getBody();

                Log::debug("{$method} {$uri}", ['request' => [
                    'headers' => $request->getHeaders(),
                    'body' => $body->getContents(),
                ]]);

                $body->rewind();

                return $request;
            });
        }
    }
}
