<?php

namespace App\Services\Search;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\Entry;
use App\Models\Message;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UnifiedSearchService
{
    private const LIMIT = 6;

    public function search(User $user, string $query): array
    {
        $like = '%'.$this->escapeLike($query).'%';
        $groups = [];

        if ($user->can('entries.view')) $groups[] = $this->entries($user, $like);
        if ($user->can('categories.view')) $groups[] = $this->categories($user, $like);
        if ($user->can('tags.view')) $groups[] = $this->tags($user, $like);
        if ($user->can('documents.view')) $groups[] = $this->documents($user, $like);
        if ($user->can('messages.send')) $groups[] = $this->users($user, $like);
        if ($user->can('messages.view')) {
            $groups[] = $this->conversations($user, $like);
            $groups[] = $this->messages($user, $like);
        }

        return ['query' => $query, 'groups' => array_values(array_filter($groups, fn (array $group) => count($group['items']) > 0))];
    }

    private function entries(User $user, string $like): array
    {
        $items = Entry::query()->where('user_id', $user->id)
            ->where(fn (Builder $q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('language', 'like', $like)->orWhere('content', 'like', $like))
            ->with('category:id,name')->orderByDesc('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (Entry $entry) => $this->item('entry', $entry->id, $entry->title, $entry->category?->name ?? ucfirst($entry->type), '/', ['sensitive' => (bool) $entry->is_sensitive]));
        return $this->group('entries', 'Entries', $items);
    }

    private function categories(User $user, string $like): array
    {
        $items = Category::query()->where('user_id', $user->id)->where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get()
            ->map(fn (Category $category) => $this->item('category', $category->id, $category->name, 'Knowledge category', '/'));
        return $this->group('categories', 'Categories', $items);
    }

    private function tags(User $user, string $like): array
    {
        $items = Tag::query()->where('user_id', $user->id)->where('name', 'like', $like)->orderBy('name')->limit(self::LIMIT)->get()
            ->map(fn (Tag $tag) => $this->item('tag', $tag->id, $tag->name, 'Knowledge tag', '/'));
        return $this->group('tags', 'Tags', $items);
    }

    private function documents(User $user, string $like): array
    {
        $items = Document::query()->where('user_id', $user->id)
            ->where(fn (Builder $q) => $q->where('title', 'like', $like)->orWhere('original_name', 'like', $like)->orWhere('description', 'like', $like))
            ->with('category:id,name')->orderByDesc('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (Document $document) => $this->item('document', $document->id, $document->title, $document->category?->name ?? $document->original_name, '/documents', ['sensitive' => (bool) $document->is_sensitive]));
        return $this->group('documents', 'Documents', $items);
    }

    private function users(User $user, string $like): array
    {
        $items = User::query()->whereKeyNot($user->id)
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('username', 'like', $like)->orWhere('job_title', 'like', $like))
            ->orderBy('name')->limit(self::LIMIT)->get(['id','name','username','job_title'])
            ->map(fn (User $member) => $this->item('user', $member->id, $member->name, $member->job_title ?: ($member->username ? '@'.$member->username : 'Workspace member'), '/messages'));
        return $this->group('users', 'People', $items);
    }

    private function conversations(User $user, string $like): array
    {
        $items = Conversation::query()
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', $user->id))
            ->whereHas('participants', fn (Builder $q) => $q->where('users.id', '!=', $user->id)->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('username', 'like', $like)->orWhere('job_title', 'like', $like)))
            ->with(['participants' => fn ($q) => $q->where('users.id', '!=', $user->id)->select('users.id','name','username','job_title')])
            ->orderByDesc('last_message_at')->limit(self::LIMIT)->get()
            ->map(function (Conversation $conversation) {
                $other = $conversation->participants->first();
                return $this->item('conversation', $conversation->id, $other?->name ?? 'Conversation', $other?->job_title ?: 'Private conversation', '/messages?conversation='.$conversation->id);
            });
        return $this->group('conversations', 'Conversations', $items);
    }

    private function messages(User $user, string $like): array
    {
        $items = Message::query()
            ->whereHas('conversation.participants', fn (Builder $q) => $q->where('users.id', $user->id))
            ->where('body', 'like', $like)
            ->with('sender:id,name')->orderByDesc('id')->limit(self::LIMIT)->get()
            ->map(fn (Message $message) => $this->item('message', $message->id, Str::limit((string) $message->body, 90), $message->sender?->name ?? 'Former workspace member', '/messages?conversation='.$message->conversation_id, ['conversation_id' => $message->conversation_id]));
        return $this->group('messages', 'Messages', $items);
    }

    private function group(string $type, string $label, Collection $items): array
    {
        return ['type' => $type, 'label' => $label, 'items' => $items->values()->all()];
    }

    private function item(string $type, int $id, string $title, string $subtitle, string $target, array $meta = []): array
    {
        return ['id' => $id, 'resource_type' => $type, 'title' => $title, 'subtitle' => $subtitle, 'target' => $target, 'meta' => $meta];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
