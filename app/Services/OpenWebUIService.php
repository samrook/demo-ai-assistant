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
        /**
         * @var string $baseUrl
         */
        $baseUrl = config('services.open_webui.url');
        $url = $baseUrl . '/chat/completions';
        /**
         * @var string $token
         */
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

            /**
             * @var array{
             *  "id": string,
             *  "created": int,
             *  "model": string,
             *  "object": string,
             *  "choices": array<
             *      int, array{
             *          "index": int,
             *          "logprobs": string|null,
             *          "finish_reason": string,
             *          "message": array{
             *              "role": string,
             *              "content": string
             *          }
             *      }
             *  >,
             *  "usage": array{
             *      "input_tokens": int,
             *      "output_tokens": int,
             *      "total_tokens": int,
             *      "prompt_tokens": int,
             *      "completion_tokens": int,
             *      "response_token/s": float,
             *      "prompt_token/s": float,
             *      "total_duration": int,
             *      "load_duration": int,
             *      "prompt_eval_count": int,
             *      "prompt_eval_duration": int,
             *      "eval_count": int,
             *      "eval_duration": int,
             *      "approximate_total": string,
             *      "completion_tokens_details": array{
             *          "reasoning_tokens": int,
             *          "accepted_prediction_tokens": int,
             *          "rejected_prediction_tokens": int
             *      }
             *  }
             * } $data
             */
            $data = $response->json();

            return [
                'content' => $data['choices'][0]['message']['content'],
                'metadata' => [
                    'prompt_tokens' => $data['usage']['prompt_tokens'],
                    'completion_tokens' => $data['usage']['completion_tokens'],
                    'total_tokens' => $data['usage']['total_tokens'],
                    'model_used' => $model,
                ],
            ];

        } catch (ConnectionException $e) {
            throw new Exception('Could not connect to Open WebUI.');
        }
    }
}
