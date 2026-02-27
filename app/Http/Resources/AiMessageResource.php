<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'content' => $this->content,
            'status' => $this->status->value,
            'used_rag' => $this->used_rag,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
