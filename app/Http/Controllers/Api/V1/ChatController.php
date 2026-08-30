<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\ChatService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(Request $request): JsonResponse
    {
        $threads = $this->chatService->threadsForUser($request->user());

        return ApiResponse::success(ChatThreadResource::collection($threads), 'Chat threads retrieved successfully.');
    }

    public function globalPreview(Request $request): JsonResponse
    {
        $thread = $this->chatService->globalThread();
        $messages = $this->chatService->latestGuestMessages($thread);

        return ApiResponse::success([
            'thread' => new ChatThreadResource($thread),
            'messages' => ChatMessageResource::collection($messages),
        ], 'Global chat preview retrieved successfully.');
    }

    public function messages(Request $request, ChatThread $thread): JsonResponse
    {
        if (! $this->chatService->canView($request->user(), $thread)) {
            return ApiResponse::error('You do not have access to this conversation.', [], 403);
        }

        $paginator = $this->chatService->messages($thread, $request->integer('per_page', 20));

        return ApiResponse::paginated($paginator, ChatMessageResource::class, 'Messages retrieved successfully.');
    }

    public function sendMessage(SendChatMessageRequest $request, ChatThread $thread): JsonResponse
    {
        try {
            $message = $this->chatService->sendMessage($request->user(), $thread, $request->string('body')->toString());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), [], 403);
        }

        return ApiResponse::success(new ChatMessageResource($message->load('sender')), 'Message sent successfully.', [], 201);
    }

    public function startDirectMessage(Request $request, User $user): JsonResponse
    {
        try {
            $thread = $this->chatService->startOrContinueDirectMessage($request->user(), $user);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        return ApiResponse::success(new ChatThreadResource($thread), 'Conversation retrieved successfully.');
    }
}
