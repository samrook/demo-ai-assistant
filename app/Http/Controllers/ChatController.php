<?php

namespace App\Http\Controllers;

use App\Http\Resources\AiMessageResource;
use App\Models\AiConversation;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function view(AiConversation $conversation): Response
    {
        Gate::authorize('view', $conversation);

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
