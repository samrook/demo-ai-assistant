<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $model_used
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AiMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereModelUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiConversation whereUserId($value)
 * @mixin \Eloquent
 */
class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'model_used',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AiMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }
}
