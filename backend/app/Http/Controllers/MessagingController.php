<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\DirectConversationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'messages.view');
        $search = trim((string) $request->query('search', ''));

        $query = Conversation::query()
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', $request->user()->id))
            ->whereHas('participantStates', fn (Builder $q) => $q->where('user_id', $request->user()->id)->whereNull('archived_at'))
            ->with(['participants:id,name,first_name,last_name,username,job_title', 'participantStates' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->with(['messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByDesc('last_message_at')->orderByDesc('id');

        if ($search !== '') {
            $query->whereHas('participants', function (Builder $q) use ($request, $search): void {
                $q->where('users.id', '!=', $request->user()->id)
                    ->where(function (Builder $q) use ($search): void {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('job_title', 'like', "%{$search}%");
                    });
            });
        }

        $conversations = $query->paginate(25);
        $conversations->getCollection()->transform(fn (Conversation $conversation) => $this->conversationPayload($conversation, $request->user()));

        return response()->json($conversations);
    }

    public function eligibleUsers(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'messages.send');
        $search = trim((string) $request->query('search', ''));
        $users = User::query()->whereKeyNot($request->user()->id)
            ->when($search !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%");
            }))
            ->orderBy('name')->limit(30)->get();

        return response()->json(['data' => $users->map(fn (User $user) => $this->userPayload($user))->values()]);
    }

    public function store(Request $request, DirectConversationService $service): JsonResponse
    {
        $this->requirePermission($request, 'messages.send');
        $validated = $request->validate(['recipient_id' => ['required', 'integer', Rule::exists('users', 'id')]]);
        $recipient = User::query()->findOrFail($validated['recipient_id']);
        $conversation = $service->findOrCreate($request->user(), $recipient);
        $conversation->load(['participants:id,name,first_name,last_name,username,job_title', 'participantStates' => fn ($q) => $q->where('user_id', $request->user()->id), 'messages' => fn ($q) => $q->latest('id')->limit(1)]);

        return response()->json(['data' => $this->conversationPayload($conversation, $request->user())], 201);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->requirePermission($request, 'messages.view');
        $state = $this->participantOrFail($conversation, $request->user());
        $messages = $conversation->messages()->with('sender:id,name,first_name,last_name,username,job_title')->orderByDesc('id')->cursorPaginate(30);
        $messages->setCollection($messages->getCollection()->map(fn (Message $message) => $this->messagePayload($message)));

        return response()->json(['data' => $messages->items(), 'next_cursor' => optional($messages->nextCursor())->encode(), 'last_read_message_id' => $state->last_read_message_id]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        $this->requirePermission($request, 'messages.send');
        $this->participantOrFail($conversation, $request->user());
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $body = trim($validated['body']);
        if ($body === '') {
            return response()->json(['message' => 'Message body cannot be empty.', 'errors' => ['body' => ['Message body cannot be empty.']]], 422);
        }

        $message = DB::transaction(function () use ($conversation, $request, $body): Message {
            $message = $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => $body]);
            $conversation->forceFill(['last_message_at' => $message->created_at])->save();
            $conversation->participantStates()->where('user_id', $request->user()->id)->update(['last_read_message_id' => $message->id, 'archived_at' => null]);
            return $message;
        });

        $message->load('sender:id,name,first_name,last_name,username,job_title');
        return response()->json(['data' => $this->messagePayload($message)], 201);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->requirePermission($request, 'messages.view');
        $state = $this->participantOrFail($conversation, $request->user());
        $validated = $request->validate(['message_id' => ['required', 'integer']]);
        $message = $conversation->messages()->whereKey($validated['message_id'])->firstOrFail();
        if (! $state->last_read_message_id || $message->id > $state->last_read_message_id) {
            $state->update(['last_read_message_id' => $message->id]);
        }
        return response()->json(['last_read_message_id' => $state->fresh()->last_read_message_id]);
    }

    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        $this->requirePermission($request, 'messages.view');
        $state = $this->participantOrFail($conversation, $request->user());
        $state->update(['archived_at' => now()]);
        return response()->json(['archived' => true]);
    }

    private function participantOrFail(Conversation $conversation, User $user): ConversationParticipant
    {
        return $conversation->participantStates()->where('user_id', $user->id)->firstOrFail();
    }

    private function conversationPayload(Conversation $conversation, User $viewer): array
    {
        $state = $conversation->participantStates->firstWhere('user_id', $viewer->id);
        $other = $conversation->participants->firstWhere('id', '!=', $viewer->id);
        $last = $conversation->messages->first();
        $unread = Message::query()->where('conversation_id', $conversation->id)->where('sender_id', '!=', $viewer->id)
            ->when($state?->last_read_message_id, fn (Builder $q, $id) => $q->where('id', '>', $id))->count();

        return ['id' => $conversation->id, 'type' => $conversation->type, 'participant' => $other ? $this->userPayload($other) : null,
            'last_message' => $last ? $this->messagePayload($last, false) : null, 'last_message_at' => optional($conversation->last_message_at)?->toISOString(),
            'unread_count' => $unread, 'archived' => (bool) $state?->archived_at];
    }

    private function messagePayload(Message $message, bool $withSender = true): array
    {
        return ['id' => $message->id, 'conversation_id' => $message->conversation_id, 'body' => $message->body,
            'sender' => $withSender && $message->relationLoaded('sender') ? $this->userPayload($message->sender) : null,
            'sender_id' => $message->sender_id, 'created_at' => optional($message->created_at)?->toISOString(), 'edited_at' => optional($message->edited_at)?->toISOString()];
    }

    private function userPayload(User $user): array
    {
        return $user->only(['id', 'name', 'first_name', 'last_name', 'username', 'job_title']);
    }

    private function requirePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403);
    }
}
