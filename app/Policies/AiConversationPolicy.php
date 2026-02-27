<?php

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

class AiConversationPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }
    
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AiConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }
}
