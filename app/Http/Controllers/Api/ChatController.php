<?php

namespace App\Http\Controllers\Api;

use App\Enums\AiMessageRole;
use App\Enums\AiMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\AiMessageResource;
use App\Jobs\GenerateAiResponse;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function store(StoreMessageRequest $request): RedirectResponse
    {
        /**
         * @var array{"prompt": string, "use_rag": bool|null} $data
         */
        $data = $request->validated();

        $conversation = AiConversation::create([
            'user_id' => $request->user()?->id,
            'title' => Str::limit($data['prompt'], 30),
            'model_used' => config('services.open_webui.model'),
        ]);

        $this->processPrompt($conversation, $data);

        return redirect()->route('chat.show', $conversation->id);
    }

    public function storeMessage(StoreMessageRequest $request, AiConversation $conversation): AiMessageResource
    {
        /**
         * @var array{"prompt": string, "use_rag": bool|null} $data
         */
        $data = $request->validated();
        $message = $this->processPrompt($conversation, $data);

        return new AiMessageResource($message);
    }

    public function status(AiMessage $message): AiMessageResource
    {
        $message->load('conversation');

        Gate::authorize('view', $message->conversation);

        return new AiMessageResource($message);
    }

    /**
     * @param array{"prompt": string, "use_rag": bool|null} $data
     */
    private function processPrompt(AiConversation $conversation, array $data): AiMessage
    {
        $conversation->messages()->create([
            'role' => AiMessageRole::USER,
            'content' => $data['prompt'],
            'status' => AiMessageStatus::COMPLETED,
            'used_rag' => $data['use_rag'] ?? false,
        ]);

        /**
         * @var AiMessage $assistantMessage
         */
        $assistantMessage = $conversation->messages()->create([
            'role' => AiMessageRole::ASSISTANT,
            'content' => null,
            'status' => AiMessageStatus::PENDING,
            'used_rag' => $data['use_rag'] ?? false,
        ]);

        GenerateAiResponse::dispatch($assistantMessage);

        return $assistantMessage;
    }
}
