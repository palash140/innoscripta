<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsSourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
                'id' => $this->id,
                'name' => $this->name,
                'slug' => $this->slug,
                'domain' => $this->domain,
                'provider' => $this->provider,
                'news_count' => $this->news_count,
                'logo_url' => $this->logo_url,
            ];

    }
}
