<?php

use App\Enums\AiMessageRole;
use App\Enums\AiMessageStatus;
use App\Jobs\GenerateAiResponse;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\OpenWebUIService;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->conversation = AiConversation::create([
        'user_id' => $this->user->id,
        'title' => 'Job Test',
        'model_used' => 'laravel-expert',
    ]);
});

it('processes a pending message and updates it to completed', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'content' => null,
        'status' => AiMessageStatus::PENDING,
        'used_rag' => true,
    ]);

    mock(OpenWebUIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateResponse')
            ->once()
            ->andReturn([
                'content' => 'Here is the generated code.',
                'metadata' => ['total_tokens' => 150],
            ]);
    });

    $job = new GenerateAiResponse($message);
    app()->call([$job, 'handle']);

    $message->refresh();
    
    expect($message->status)->toBe(AiMessageStatus::COMPLETED)
        ->and($message->content)->toBe('Here is the generated code.')
        ->and($message->metadata['total_tokens'])->toBe(150);
});

it('marks the message as failed if the service throws an exception', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'status' => AiMessageStatus::PENDING,
        'used_rag' => false,
    ]);

    mock(OpenWebUIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateResponse')
            ->once()
            ->andThrow(new Exception('GPU Out of Memory'));
    });

    Log::shouldReceive('error')->once();

    $job = new GenerateAiResponse($message);
    app()->call([$job, 'handle']);

    $message->refresh();
    
    expect($message->status)->toBe(AiMessageStatus::FAILED)
        ->and($message->content)->toBeNull();
});
