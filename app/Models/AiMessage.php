<?php

namespace App\Models;

use App\Enums\AiMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'status',
        'used_rag',
        'metadata',
    ];

    protected $casts = [
        'status' => AiMessageStatus::class,
        'used_rag' => 'boolean',
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
