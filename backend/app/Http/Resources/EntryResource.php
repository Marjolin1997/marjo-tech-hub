<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'language' => $this->language,
            'description' => $this->description,
            'content' => $this->content,
            'is_sensitive' => $this->is_sensitive,
            'sort_order' => $this->sort_order,
            'updated_at' => $this->updated_at?->toISOString(),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
