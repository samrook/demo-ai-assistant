<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\AiMessageResource;
use App\Models\AiConversation;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function view(StoreMessageRequest $request, AiConversation $conversation): Response {
        return Inertia::render('Chat/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'model_used' => $conversation->model_used,
                'messages' => AiMessageResource::collection($conversation->messages)->resolve(),
            ]
        ]);
    }
}
