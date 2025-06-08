<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPrefrenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category' => new NewsCategoryResource($this->category),
            'source' => new NewsSourceResource($this->source),
            'author' => new NewsAuthorResource($this->author)
        ];
    }
}
