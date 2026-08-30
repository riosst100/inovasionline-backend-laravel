<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadParticipant;
use App\Models\User;
use App\Support\Enums\ChatRegionLevel;
use App\Support\Enums\ChatThreadType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChatService
{
    public const GUEST_MESSAGE_LIMIT = 10;

    public function globalThread(): ChatThread
    {
        return ChatThread::firstOrCreate(['type' => ChatThreadType::GLOBAL->value]);
    }

    public function officialThread(): ChatThread
    {
        return ChatThread::firstOrCreate(['type' => ChatThreadType::OFFICIAL->value]);
    }

    /**
     * Join a user into the global, official, and region chat threads that
     * match their address. Region threads are created lazily on first use.
     */
    public function syncMembershipsForUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->joinThread($user, $this->globalThread());
            $this->joinThread($user, $this->officialThread());

            $regions = [
                [ChatRegionLevel::VILLAGE, $user->village_code],
                [ChatRegionLevel::DISTRICT, $user->district_code],
                [ChatRegionLevel::CITY, $user->city_code],
            ];

            foreach ($regions as [$level, $code]) {
                if ($code === null) {
                    continue;
                }

                $thread = ChatThread::firstOrCreate([
                    'type' => ChatThreadType::REGION->value,
                    'region_code' => $code,
                ], [
                    'region_level' => $level->value,
                ]);

                $this->joinThread($user, $thread);
            }
        });
    }

    private function joinThread(User $user, ChatThread $thread): void
    {
        ChatThreadParticipant::firstOrCreate(
            ['thread_id' => $thread->id, 'user_id' => $user->id],
            ['joined_at' => now()]
        );
    }

    /**
     * Order: Official, region (village -> district -> city), global,
     * then direct messages (most recently updated first).
     */
    private const TYPE_SORT_ORDER = [
        'official' => 0,
        'region' => 1,
        'global' => 2,
        'dm' => 3,
    ];

    private const REGION_LEVEL_SORT_ORDER = [
        'village' => 0,
        'district' => 1,
        'city' => 2,
    ];

    /**
     * @return Collection<int, ChatThread>
     */
    public function threadsForUser(User $user): Collection
    {
        $threads = ChatThread::query()
            ->where(function ($query) use ($user) {
                $query->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                    ->orWhere('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->with(['userOne', 'userTwo'])
            ->latest('updated_at')
            ->get();

        return $threads->sort(function (ChatThread $a, ChatThread $b) {
            $typeCompare = self::TYPE_SORT_ORDER[$a->type->value] <=> self::TYPE_SORT_ORDER[$b->type->value];
            if ($typeCompare !== 0) {
                return $typeCompare;
            }

            if ($a->type === ChatThreadType::REGION) {
                return self::REGION_LEVEL_SORT_ORDER[$a->region_level->value]
                    <=> self::REGION_LEVEL_SORT_ORDER[$b->region_level->value];
            }

            return $b->updated_at <=> $a->updated_at;
        })->values();
    }

    public function startOrContinueDirectMessage(User $user, User $other): ChatThread
    {
        if ($user->id === $other->id) {
            throw new RuntimeException('Cannot start a conversation with yourself.');
        }

        $ids = [$user->id, $other->id];
        sort($ids);
        [$userOneId, $userTwoId] = $ids;

        return ChatThread::firstOrCreate([
            'type' => ChatThreadType::DM->value,
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    public function canPost(User $user, ChatThread $thread): bool
    {
        if ($thread->type === ChatThreadType::OFFICIAL) {
            return $user->isPlatformAdmin();
        }

        return $thread->participants()->where('user_id', $user->id)->exists()
            || ($thread->type === ChatThreadType::DM
                && in_array($user->id, [$thread->user_one_id, $thread->user_two_id], true));
    }

    public function canView(?User $user, ChatThread $thread): bool
    {
        if ($thread->type === ChatThreadType::GLOBAL || $thread->type === ChatThreadType::OFFICIAL) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $thread->participants()->where('user_id', $user->id)->exists()
            || in_array($user->id, [$thread->user_one_id, $thread->user_two_id], true);
    }

    public function sendMessage(User $sender, ChatThread $thread, string $body): ChatMessage
    {
        if (! $this->canPost($sender, $thread)) {
            throw new RuntimeException('You are not allowed to post in this conversation.');
        }

        $message = ChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $thread->touch();

        return $message;
    }

    public function messages(ChatThread $thread, int $perPage = 20): LengthAwarePaginator
    {
        return $thread->messages()
            ->with('sender')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function latestGuestMessages(ChatThread $thread): Collection
    {
        return $thread->messages()
            ->with('sender')
            ->latest('created_at')
            ->limit(self::GUEST_MESSAGE_LIMIT)
            ->get();
    }
}
