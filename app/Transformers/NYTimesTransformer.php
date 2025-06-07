<?php

namespace App\Transformers;

use App\DTOs\NewsItemDTO;
use Illuminate\Support\Collection;

class NYTimesTransformer
{
    public function transformToNewsItem(array $article): ?NewsItemDTO
    {
        if (empty($article['headline']['main']) || empty($article['web_url'])) {
            return null;
        }

        return new NewsItemDTO(
            uniqueId: $this->generateUniqueId($article),
            title: $article['headline']['main'],
            description: $this->extractDescription($article),
            categoryName: $article['section_name'] ?? null,
            authorName: $this->extractAuthor($article['byline'] ?? []),
            sourceName: 'The New York Times',
            sourceDomain: 'nytimes.com',
            provider: 'nytimes',
            sourceUrl: $article['web_url'],
            publishedAt: $this->parseDate($article['pub_date'] ?? null)
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
        return 'nytimes_' . md5($article['_id'] ?? $article['web_url']);
    }

    private function extractDescription(array $article): ?string
    {
        return $article['abstract'] ??
               $article['lead_paragraph'] ??
               $article['snippet'] ??
               null;
    }

    private function extractAuthor(array $byline): ?string
    {
        if (isset($byline['person']) && is_array($byline['person'])) {
            $authors = collect($byline['person'])
                ->map(function ($person) {
                    $first = $person['firstname'] ?? '';
                    $last = $person['lastname'] ?? '';
                    return trim("$first $last");
                })
                ->filter()
                ->take(3);

            return $authors->isEmpty() ? null : $authors->implode(', ');
        }

        $author = $byline['original'] ?? null;
        $cleaned = str_replace("By ", "", $author);
        return $cleaned;
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
