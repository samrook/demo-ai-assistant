<?php

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\OpenWebUIService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    
    $this->conversation = AiConversation::create([
        'user_id' => $this->user->id,
        'title' => 'TDD Test',
        'model_used' => 'laravel-expert',
    ]);
});

it('formats a standard request and parses the response', function () {
    Http::fake([
        '*/api/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Here is your Laravel code.']]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ]
        ], 200)
    ]);

    AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'Write a test.',
        'status' => 'completed',
        'used_rag' => false,
    ]);

    $service = new OpenWebUIService();

    $result = $service->generateResponse($this->conversation, false);

    expect($result['content'])->toBe('Here is your Laravel code.')
        ->and($result['metadata']['total_tokens'])->toBe(30);

    Http::assertSent(function ($request) {
        return $request->url() === config('services.open_webui.url') . '/chat/completions'
            && !isset($request->data()['files']);
    });
});

it('injects the knowledge base collection when RAG is enabled', function () {
    config()->set('services.open_webui.collection_id', 'laravel-docs-test');

    Http::fake([
        '*/api/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Docs used.']]],
            'usage' => ['total_tokens' => 50]
        ], 200)
    ]);

    AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => 'user',
        'content' => 'How do routing work?',
        'status' => 'completed',
        'used_rag' => true,
    ]);

    $service = new OpenWebUIService();

    $service->generateResponse($this->conversation, true);

    Http::assertSent(function ($request) {
        $payload = $request->data();
        return isset($payload['files']) 
            && $payload['files'][0]['type'] === 'collection'
            && isset($payload['files'][0]['id'])
            && $payload['files'][0]['id'] === config('services.open_webui.collection_id');
    });
});
