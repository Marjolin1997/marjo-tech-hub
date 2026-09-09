<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentStoreRequest;
use App\Http\Requests\DocumentUpdateRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'], 'category_id' => ['nullable', 'integer'],
            'tag_id' => ['nullable', 'integer'], 'is_sensitive' => ['nullable', 'boolean'],
            'extension' => ['nullable', 'string', 'max:12'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $request->user();
        $query = $user->documents()->with(['category', 'tags']);
        if (!empty($data['category_id'])) $query->where('category_id', $data['category_id']);
        if (!empty($data['tag_id'])) $query->whereHas('tags', fn ($q) => $q->where('tags.id', $data['tag_id'])->where('tags.user_id', $user->id));
        if (array_key_exists('is_sensitive', $data)) $query->where('is_sensitive', $data['is_sensitive']);
        if (!empty($data['extension'])) $query->where('extension', strtolower($data['extension']));
        if (!empty($data['q'])) { $term = '%'.addcslashes($data['q'], '%_\\').'%'; $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('original_name', 'like', $term)); }
        return DocumentResource::collection($query->latest('updated_at')->paginate($data['per_page'] ?? 20));
    }

    public function store(DocumentStoreRequest $request): JsonResponse
    {
        $data = $request->validated(); $tags = $data['tag_ids'] ?? [];
        $this->assertOwnedCategory($request, $data['category_id'] ?? null); $this->assertOwnedTags($request, $tags);
        $file = $request->file('file'); $extension = strtolower($file->guessExtension() ?: $file->extension());
        $storedName = (string) Str::uuid().'.'.$extension; $relativePath = $request->user()->id.'/'.$storedName;
        $disk = Storage::disk('documents'); $disk->putFileAs((string) $request->user()->id, $file, $storedName);
        try {
            $document = DB::transaction(function () use ($request, $data, $tags, $file, $extension, $storedName, $relativePath) {
                $document = $request->user()->documents()->create([
                    'category_id' => $data['category_id'] ?? null, 'title' => $data['title'], 'slug' => $this->uniqueSlug($request, $data['title']),
                    'description' => $data['description'] ?? null, 'original_name' => basename($file->getClientOriginalName()), 'stored_name' => $storedName,
                    'disk' => 'documents', 'path' => $relativePath, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'extension' => $extension, 'size' => $file->getSize(), 'checksum' => hash_file('sha256', $file->getRealPath()), 'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
                ]); $document->tags()->sync($tags); return $document;
            });
        } catch (\Throwable $e) { $disk->delete($relativePath); throw $e; }
        return (new DocumentResource($document->load(['category', 'tags'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, Document $document): DocumentResource { $this->assertOwner($request, $document); return new DocumentResource($document->load(['category', 'tags'])); }

    public function update(DocumentUpdateRequest $request, Document $document): DocumentResource
    {
        $this->assertOwner($request, $document); $data = $request->validated(); $tags = $data['tag_ids'] ?? [];
        $this->assertOwnedCategory($request, $data['category_id'] ?? null); $this->assertOwnedTags($request, $tags);
        DB::transaction(function () use ($request, $document, $data, $tags) {
            $document->update(['category_id'=>$data['category_id']??null,'title'=>$data['title'],'slug'=>$this->uniqueSlug($request,$data['title'],$document->id),'description'=>$data['description']??null,'is_sensitive'=>(bool)($data['is_sensitive']??false)]);
            $document->tags()->sync($tags);
        });
        return new DocumentResource($document->fresh()->load(['category', 'tags']));
    }

    public function preview(Request $request, Document $document): BinaryFileResponse|StreamedResponse
    {
        $this->assertOwner($request, $document); abort_unless(in_array($document->extension, ['pdf','png','jpg','jpeg','txt','md'], true), 422, 'Preview is not available for this file type.');
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);
        $headers = ['Content-Type'=>$document->mime_type,'Content-Disposition'=>'inline; filename="'.addslashes($document->original_name).'"','X-Content-Type-Options'=>'nosniff'];
        return Storage::disk($document->disk)->response($document->path, $document->original_name, $headers, 'inline');
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->assertOwner($request, $document); abort_unless(Storage::disk($document->disk)->exists($document->path), 404);
        return Storage::disk($document->disk)->download($document->path, $document->original_name, ['X-Content-Type-Options'=>'nosniff']);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->assertOwner($request, $document); $disk = Storage::disk($document->disk);
        if ($disk->exists($document->path) && !$disk->delete($document->path)) abort(500, 'Document storage deletion failed.');
        $document->delete(); return response()->json([], 204);
    }

    private function assertOwner(Request $request, Document $document): void { abort_unless($document->user_id === $request->user()->id, 404); }
    private function assertOwnedCategory(Request $request, ?int $id): void { if ($id !== null) abort_unless($request->user()->categories()->whereKey($id)->exists(), 422, 'The selected category is invalid.'); }
    private function assertOwnedTags(Request $request, array $ids): void { if ($ids) abort_unless($request->user()->tags()->whereKey($ids)->count() === count(array_unique($ids)), 422, 'One or more selected tags are invalid.'); }
    private function uniqueSlug(Request $request, string $title, ?int $ignoreId = null): string { $base=Str::slug($title)?:'document';$slug=$base;$i=2;while($request->user()->documents()->where('slug',$slug)->when($ignoreId,fn($q)=>$q->whereKeyNot($ignoreId))->exists())$slug=$base.'-'.$i++;return $slug; }
}
