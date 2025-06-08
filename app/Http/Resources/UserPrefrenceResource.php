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
         'category' => $this->category ? new NewsCategoryResource($this->category) : null,
         'source' => $this->source ? new NewsSourceResource($this->source) : null,
         'author' => $this->author ? new NewsAuthorResource($this->author) : null,
        ];
    }
}
