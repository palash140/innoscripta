<?php

// Simplified NewsItemDTO (back to basic approach)

namespace App\DTOs;

class NewsItemDTO
{
    public function __construct(
        public readonly string $uniqueId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $categoryName,
        public readonly ?string $authorName,
        public readonly ?string $sourceName,
        public readonly ?string $sourceDomain, // For NewsAPI domains
        public readonly string $provider,
        public readonly ?string $sourceUrl,
        public readonly ?\DateTime $publishedAt
    ) {
    }

    public function toApiArray(): array
    {
        return [
            'unique_id' => $this->uniqueId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->categoryName,
            'author' => $this->authorName,
            'source' => $this->sourceName,
            'provider' => $this->provider,
            'source_url' => $this->sourceUrl,
            'published_at' => $this->publishedAt?->format('c'),
        ];
    }

    public function toDatabaseArray(int $categoryId, ?int $authorId, int $sourceId): array
    {
        return [
            'unique_id' => $this->uniqueId,
            'title' => $this->title,
            'description' => $this->description,
            'news_category_id' => $categoryId,
            'news_author_id' => $authorId,
            'news_source_id' => $sourceId,
            'provider' => $this->provider,
            'source_url' => $this->sourceUrl,
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
