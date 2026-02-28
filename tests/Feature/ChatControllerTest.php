<?php

use App\Enums\AiMessageRole;
use App\Enums\AiMessageStatus;
use App\Jobs\GenerateAiResponse;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Str;
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

it('touches conversation updated_at when a new message is posted', function () {
    Queue::fake();

    $this->conversation->forceFill([
        'updated_at' => now()->subHour(),
    ])->save();
    $before = $this->conversation->updated_at;

    actingAs($this->user)->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => 'Bump conversation activity timestamp',
        'use_rag' => false,
    ])->assertStatus(201);

    $this->conversation->refresh();

    expect($this->conversation->updated_at->greaterThan($before))->toBeTrue();
});

it('returns the status of a specific message for frontend polling', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'status' => AiMessageStatus::PROCESSING,
    ]);

    $response = actingAs($this->user)->getJson(route('message.status', $message->id));

    $response->assertStatus(200)
        ->assertJsonFragment([
            'content' => null,
            'status' => 'processing',
        ]);
});

it('returns 401 when an unauthenticated user polls a message status', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'status' => AiMessageStatus::PROCESSING,
    ]);

    $response = $this->getJson(route('message.status', $message->id));

    $response->assertUnauthorized();
});

it('returns 403 when another authenticated user polls a message they do not own', function () {
    $message = AiMessage::create([
        'ai_conversation_id' => $this->conversation->id,
        'role' => AiMessageRole::ASSISTANT,
        'status' => AiMessageStatus::PROCESSING,
    ]);
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)->getJson(route('message.status', $message->id));

    $response->assertForbidden();
});

it('returns 401 when an unauthenticated user creates a new conversation', function () {
    $response = $this->postJson(route('chat.store'), [
        'prompt' => 'Hello from guest',
        'use_rag' => false,
    ]);

    $response->assertUnauthorized();
});

it('returns 401 when an unauthenticated user posts a message to a conversation', function () {
    $response = $this->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => 'Guest message',
        'use_rag' => false,
    ]);

    $response->assertUnauthorized();
});

it('returns 403 when another authenticated user posts to a conversation they do not own', function () {
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => 'Trying to post to another user conversation',
        'use_rag' => false,
    ]);

    $response->assertForbidden();
});

it('validates that prompt is required when posting a message', function () {
    $response = actingAs($this->user)->postJson(route('chat-message.store', $this->conversation->id), [
        'use_rag' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['prompt']);
});

it('validates that prompt is at most 5000 characters', function () {
    $response = actingAs($this->user)->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => str_repeat('a', 5001),
        'use_rag' => false,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['prompt']);
});

it('validates that use_rag is a boolean value', function () {
    $response = actingAs($this->user)->postJson(route('chat-message.store', $this->conversation->id), [
        'prompt' => 'Check boolean validation',
        'use_rag' => 'not-a-boolean',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['use_rag']);
});

it('allows the owner to view the web chat page', function () {
    $response = actingAs($this->user)->get(route('chat.show', $this->conversation->id));

    $response->assertOk();
});

it('returns 403 when another user tries to view the web chat page', function () {
    $otherUser = User::factory()->create();

    $response = actingAs($otherUser)->get(route('chat.show', $this->conversation->id));

    $response->assertForbidden();
});

it('creates a new conversation, dispatches the job, and redirects to the chat page', function () {
    Queue::fake();
    $prompt = 'How do I install Vue in Laravel?';

    $response = actingAs($this->user)->post(route('chat.store'), [
        'prompt' => $prompt,
        'use_rag' => true,
    ]);

    $conversation = AiConversation::latest('id')->first();

    assertDatabaseHas('ai_conversations', [
        'user_id' => $this->user->id,
        'title' => Str::limit($prompt, 30),
    ]);

    expect($conversation)->not->toBeNull();
    expect($conversation->title)->toBe(Str::limit($prompt, 30));

    $response->assertRedirect(route('chat.show', $conversation->id));

    Queue::assertPushed(GenerateAiResponse::class);
});
