<?php

namespace App\Http\Resources;

use App\Support\Enums\ChatRegionLevel;
use App\Support\Enums\ChatThreadType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class ChatThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $otherUser = null;

        if ($this->type === ChatThreadType::DM && $viewer) {
            $otherUser = $this->user_one_id === $viewer->id ? $this->userTwo : $this->userOne;
        }

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'region_level' => $this->region_level?->value,
            'region_code' => $this->region_code,
            'title' => $this->resolveTitle($otherUser),
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar_url' => $otherUser->avatar_path ? asset('storage/'.$otherUser->avatar_path) : null,
            ] : null,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveTitle(?object $otherUser): string
    {
        return match ($this->type) {
            ChatThreadType::GLOBAL => 'GLOBAL - Grup Chat',
            ChatThreadType::OFFICIAL => 'Inovasi Online',
            ChatThreadType::REGION => "{$this->resolveRegionName()} - Grup Chat",
            ChatThreadType::DM => $otherUser?->name ?? 'Percakapan',
        };
    }

    private function resolveRegionName(): string
    {
        $model = match ($this->region_level) {
            ChatRegionLevel::PROVINCE => Province::where('code', $this->region_code)->first(),
            ChatRegionLevel::CITY => City::where('code', $this->region_code)->first(),
            ChatRegionLevel::DISTRICT => District::where('code', $this->region_code)->first(),
            ChatRegionLevel::VILLAGE => Village::where('code', $this->region_code)->first(),
            default => null,
        };

        return $model?->name ?? 'Wilayah';
    }
}
