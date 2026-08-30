<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'is_official' => $this->sender_id === null,
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }
}
