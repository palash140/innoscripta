<?php

namespace App\Transformers;

use App\DTOs\NewsItemDTO;
use Illuminate\Support\Collection;

class GuardianTransformer
{
    public function transformToNewsItem(array $article): ?NewsItemDTO
    {
        if (empty($article['webTitle']) || empty($article['webUrl'])) {
            return null;
        }

        return new NewsItemDTO(
            uniqueId: $this->generateUniqueId($article),
            title: $this->extractTitle($article),
            description: $this->extractDescription($article),
            categoryName: $article['sectionName'] ?? null,
            authorName: $this->extractAuthor($article),
            sourceName: 'The Guardian',
            sourceDomain: 'theguardian.com',
            provider: 'guardian',
            sourceUrl: $article['webUrl'],
            publishedAt: $this->parseDate($article['webPublicationDate'] ?? null)
        );
    }

    public function transformCollection(array $rawArticles): Collection
    {
        return collect($rawArticles)
            ->map(fn ($article) => $this->transformToNewsItem($article))
            ->filter()
            ->values();
    }

    public function generateUniqueId(array $article): string
    {
        return 'guardian_' . md5($article['id'] ?? $article['webUrl']);
    }


    private function extractTitle(array $article): string
    {
        return $article['fields']['headline'] ?? $article['webTitle'];
    }

    private function extractDescription(array $article): ?string
    {
        return $article['fields']['trailText'] ??
               $article['fields']['standfirst'] ??
               null;
    }

    private function extractAuthor(array $article): ?string
    {
        $byline = $article['fields']['byline'] ?? null;
        if (!$byline) {
            return null;
        }
        return preg_replace('/^By\s+/i', '', $byline);
    }

    private function parseDate(?string $date): ?\DateTime
    {
        if (!$date) {
            return null;
        }

        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}
