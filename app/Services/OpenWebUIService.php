<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OpenWebUIService
{
    /**
     * @return array<string, string|array<string, int|string>>
     * @throws Exception
     */
    public function generateResponse(AiConversation $conversation, bool $useRag = false): array
    {
        $url = config('services.open_webui.url') . '/chat/completions';
        $token = config('services.open_webui.key');
        $model = $conversation->model_used;

        /**
         * @var Collection<int, AiMessage> $messages
         */
        $messages = $conversation->messages()
            ->where('status', 'completed')
            ->orderBy('created_at', 'asc')
            ->get();

        $messagesArray = $messages->map(fn (AiMessage $message): array => [
                'role' => $message->role->value,
                'content' => $message->content,
            ])
            ->toArray();

        $payload = [
            'model' => $model,
            'messages' => $messagesArray,
        ];

        if ($useRag) {
            $payload['files'] = [
                [
                    'type' => 'collection',
                    'id' => config('services.open_webui.collection_id')
                ]
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(120)
                ->post($url, $payload);

            if ($response->failed()) {
                throw new Exception('Open WebUI API Error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'metadata' => [
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                    'model_used' => $model,
                ],
            ];

        } catch (ConnectionException $e) {
            throw new Exception('Could not connect to Open WebUI.');
        }
    }
}
