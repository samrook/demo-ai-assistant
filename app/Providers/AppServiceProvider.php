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

                Log::debug("{$method} {$uri}", ['request' => [
                    'headers' => $this->redactHeaders($request->getHeaders()),
                    'body' => '[REDACTED]',
                ]]);

                return $request;
            });
        }
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    private function redactHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'proxy-authorization',
            'cookie',
            'set-cookie',
            'x-api-key',
            'x-auth-token',
        ];

        $redacted = [];

        foreach ($headers as $name => $values) {
            $redacted[$name] = in_array(strtolower($name), $sensitiveHeaders, true)
                ? ['[REDACTED]']
                : $values;
        }

        return $redacted;
    }
}
