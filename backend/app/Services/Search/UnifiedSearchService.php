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
        if ($user->can('messages.view')) { $groups[] = $this->conversations($user, $like); $groups[] = $this->messages($user, $like); }
        return ['query' => $query, 'groups' => array_values(array_filter($groups, fn (array $group) => count($group['items']) > 0))];
    }

    private function entries(User $user, string $like): array
    {
        $items=Entry::query()->where('user_id',$user->id)->where(fn(Builder $q)=>$this->likeAny($q,['title','description','language','content'],$like))->with('category:id,name')->orderByDesc('updated_at')->limit(self::LIMIT)->get()->map(fn(Entry $e)=>$this->item('entry',$e->id,$e->title,$e->category?->name??ucfirst($e->type),'/', ['sensitive'=>(bool)$e->is_sensitive]));
        return $this->group('entries','Entries',$items);
    }
    private function categories(User $user,string $like): array
    {
        $items=Category::query()->where('user_id',$user->id)->whereRaw("name LIKE ? ESCAPE '!'",[$like])->orderBy('name')->limit(self::LIMIT)->get()->map(fn(Category $c)=>$this->item('category',$c->id,$c->name,'Knowledge category','/'));
        return $this->group('categories','Categories',$items);
    }
    private function tags(User $user,string $like): array
    {
        $items=Tag::query()->where('user_id',$user->id)->whereRaw("name LIKE ? ESCAPE '!'",[$like])->orderBy('name')->limit(self::LIMIT)->get()->map(fn(Tag $t)=>$this->item('tag',$t->id,$t->name,'Knowledge tag','/'));
        return $this->group('tags','Tags',$items);
    }
    private function documents(User $user,string $like): array
    {
        $items=Document::query()->where('user_id',$user->id)->where(fn(Builder $q)=>$this->likeAny($q,['title','original_name','description'],$like))->with('category:id,name')->orderByDesc('updated_at')->limit(self::LIMIT)->get()->map(fn(Document $d)=>$this->item('document',$d->id,$d->title,$d->category?->name??$d->original_name,'/documents',['sensitive'=>(bool)$d->is_sensitive]));
        return $this->group('documents','Documents',$items);
    }
    private function users(User $user,string $like): array
    {
        $hasPermission = fn (Builder $q, string $permission) => $q->where(fn (Builder $q) => $q->whereHas('permissions', fn (Builder $p) => $p->where('name', $permission))->orWhereHas('roles.permissions', fn (Builder $p) => $p->where('name', $permission)));
        $items=User::query()->whereKeyNot($user->id)
            ->where(fn(Builder $q)=>$this->likeAny($q,['name','username','job_title'],$like))
            ->where(fn(Builder $q)=>$hasPermission($q,'messages.view'))->where(fn(Builder $q)=>$hasPermission($q,'messages.send'))
            ->orderBy('name')->limit(self::LIMIT)->get(['id','name','username','job_title'])
            ->map(fn(User $u)=>$this->item('user',$u->id,$u->name,$u->job_title?:($u->username?'@'.$u->username:'Workspace member'),'/messages'));
        return $this->group('users','People',$items);
    }
    private function conversations(User $user,string $like): array
    {
        $items=Conversation::query()->whereHas('participants',fn(Builder $q)=>$q->where('users.id',$user->id))->whereHas('participants',fn(Builder $q)=>$q->where('users.id','!=',$user->id)->where(fn(Builder $q)=>$this->likeAny($q,['name','username','job_title'],$like)))->with(['participants'=>fn($q)=>$q->where('users.id','!=',$user->id)->select('users.id','name','username','job_title')])->orderByDesc('last_message_at')->limit(self::LIMIT)->get()->map(function(Conversation $c){$other=$c->participants->first();return $this->item('conversation',$c->id,$other?->name??'Conversation',$other?->job_title?:'Private conversation','/messages?conversation='.$c->id);});
        return $this->group('conversations','Conversations',$items);
    }
    private function messages(User $user,string $like): array
    {
        $items=Message::query()->whereHas('conversation.participants',fn(Builder $q)=>$q->where('users.id',$user->id))->whereRaw("body LIKE ? ESCAPE '!'",[$like])->with('sender:id,name')->orderByDesc('id')->limit(self::LIMIT)->get()->map(fn(Message $m)=>$this->item('message',$m->id,Str::limit((string)$m->body,90),$m->sender?->name??'Former workspace member','/messages?conversation='.$m->conversation_id,['conversation_id'=>$m->conversation_id]));
        return $this->group('messages','Messages',$items);
    }
    private function likeAny(Builder $query,array $columns,string $like): void
    {
        foreach ($columns as $index=>$column) {
            $method=$index===0?'whereRaw':'orWhereRaw';
            $query->{$method}("{$column} LIKE ? ESCAPE '!'",[$like]);
        }
    }
    private function group(string $type,string $label,Collection $items): array { return ['type'=>$type,'label'=>$label,'items'=>$items->values()->all()]; }
    private function item(string $type,int $id,string $title,string $subtitle,string $target,array $meta=[]): array { return ['id'=>$id,'resource_type'=>$type,'title'=>$title,'subtitle'=>$subtitle,'target'=>$target,'meta'=>$meta]; }
    private function escapeLike(string $value): string { return str_replace(['!','%','_'],['!!','!%','!_'],$value); }
}
