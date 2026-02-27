<?php

use App\Enums\AiMessageRole;
use App\Enums\AiMessageStatus;
use App\Jobs\GenerateAiResponse;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->conversation = AiConversation::create([
        'user_id' => $this->user->id,
        'title' => 'Controller Test',
    ]);
});

it('stores a user prompt, creates a pending assistant message, and dispatches the job', function () {
    Queue::fake();

    $response = actingAs($this->user)->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => 'Create a Vue component.',
        'use_rag' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'status', 'role']]);

    $assistantMessageId = $response->json('data.id');

    assertDatabaseHas('ai_messages', [
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::USER,
        'content' => 'Create a Vue component.',
        'status' => AiMessageStatus::COMPLETED,
    ]);

    assertDatabaseHas('ai_messages', [
        'id' => $assistantMessageId,
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'content' => null,
        'status' => AiMessageStatus::PENDING,
        'used_rag' => true,
    ]);

    Queue::assertPushed(GenerateAiResponse::class, function ($job) use ($assistantMessageId) {
        return $job->message->id === $assistantMessageId;
    });
});

it('returns the status of a specific message for frontend polling', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'status' => AiMessageStatus::PROCESSING,
    ]);

    $response = actingAs($this->user)->getJson(route('message.status', $message->id));

    $response->assertStatus(200)
             ->assertJson([
                 'status' => 'processing',
                 'content' => null,
             ]);
});

it('creates a new conversation, dispatches the job, and redirects to the chat page', function () {
    Queue::fake();

    $response = actingAs($this->user)->post(route('chat.store'), [
        'prompt' => 'How do I install Vue in Laravel?',
        'use_rag' => true,
    ]);

    assertDatabaseHas('ai_conversations', [
        'user_id' => $this->user->id,
        'title' => 'How do I install Vue in Larave...',
    ]);

    $conversation = AiConversation::latest('id')->first();

    $response->assertRedirect(route('chat.show', $conversation->id));

    Queue::assertPushed(GenerateAiResponse::class);
});
