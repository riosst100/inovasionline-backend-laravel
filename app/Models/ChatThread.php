<?php

namespace App\Models;

use App\Support\Enums\ChatRegionLevel;
use App\Support\Enums\ChatThreadType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'type',
        'region_level',
        'region_code',
        'user_one_id',
        'user_two_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChatThreadType::class,
            'region_level' => ChatRegionLevel::class,
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatThreadParticipant::class, 'thread_id');
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }
}
