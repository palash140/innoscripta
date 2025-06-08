<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unique_id' => $this->unique_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', function () {
                return new NewsCategoryResource($this->category);
            }),
            'author' => $this->whenLoaded('author', function () {
                return new NewsAuthorResource($this->author);
            }),
            'source' => $this->whenLoaded('source', function () {
                return new NewsSourceResource($this->source);
            }),
            'provider' => $this->provider,
            'source_url' => $this->source_url,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
