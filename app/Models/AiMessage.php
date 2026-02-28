<?php

namespace App\Models;

use App\Enums\AiMessageRole;
use App\Enums\AiMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ai_conversation_id
 * @property AiMessageRole $role
 * @property string|null $content
 * @property AiMessageStatus $status
 * @property bool $used_rag
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AiConversation $conversation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereAiConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiMessage whereUsedRag($value)
 * @mixin \Eloquent
 */
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
        'role' => AiMessageRole::class,
        'used_rag' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
