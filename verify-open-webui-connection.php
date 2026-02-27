<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use Illuminate\Http\Client\Response;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$url = rtrim(env('OPEN_WEBUI_URL'), '/') . '/';
$token = env('OPEN_WEBUI_API_KEY');

$client = new Client([
    'base_uri' => $url,
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
        'Accepts' => 'application/json',
    ],
]);

try {
    $response = new Response($client->get('models'));
    
    if ($response->successful()) {
        echo "✅ Success! Found " . count($response->json()['data']) . " models.\n";
        print_r(collect($response->json()['data'])->pluck('id')->toArray());
    } else {
        echo "❌ Failed! Status: " . $response->status() . "\n";
        echo "Error: " . $response->body() . "\n";
    }
} catch (Throwable $e) {
    echo "❌ Failed! Error thrown: " . get_class($e) . "\n";
    echo "Error: " . $e->getMessage();
}
