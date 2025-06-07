<?php

namespace App\Transformers;

use App\DTOs\NewsItemDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NewsAPITransformer
{
    public function transformToNewsItem(array $article, ?array $sourcesMapping = null): ?NewsItemDTO
    {
        if (empty($article['title']) || empty($article['url'])) {
            return null;
        }

        // Extract source information
        $sourceId = $article['source']['id'] ?? null;
        $sourceName = $article['source']['name'] ?? null;
        $domain = $this->extractDomain($article['url']);

        // Determine category using sources mapping
        $categoryName = $this->determineCategoryName($sourceId, $sourcesMapping);

        return new NewsItemDTO(
            uniqueId: $this->generateUniqueId($article),
            title: $this->cleanTitle($article['title']),
            description: $this->cleanDescription($article['description']),
            categoryName: $categoryName,
            authorName: $this->cleanAuthor($article['author']),
            sourceName: $sourceName,
            sourceDomain: $domain,
            provider: 'newsapi',
            sourceUrl: $article['url'],
            publishedAt: $this->parseDate($article['publishedAt'])
        );
    }

    public function transformCollection(array $rawArticles, array $sourcesData = []): Collection
    {
        // Build sources mapping once for the entire collection
        $sourcesMapping = $this->buildSourcesMapping($sourcesData);

        Log::info('NewsAPITransformer: Processing articles', [
            'articles_count' => count($rawArticles),
            'sources_count' => count($sourcesData),
            'source_id_mappings' => count($sourcesMapping)
        ]);

        return collect($rawArticles)
            ->map(fn ($article) => $this->transformToNewsItem($article, $sourcesMapping))
            ->filter()
            ->values();
    }

    /**
     * Build simple source ID to category mapping
     */
    private function buildSourcesMapping(array $sources): array
    {
        $mapping = [];

        foreach ($sources as $source) {
            $sourceId = $source['id'] ?? null;
            $category = $this->normalizeCategoryName($source['category'] ?? null);

            if ($sourceId && $category) {
                $mapping[$sourceId] = $category;
            }
        }

        return $mapping;
    }

    /**
     * Determine category using only source ID
     */
    private function determineCategoryName(?string $sourceId, ?array $sourcesMapping): ?string
    {
        // Simple lookup: source ID -> category
        if ($sourceId && $sourcesMapping && isset($sourcesMapping[$sourceId])) {
            return $sourcesMapping[$sourceId];
        }

        return null;
    }





    /**
     * Normalize category names to consistent format
     */
    private function normalizeCategoryName(?string $category): ?string
    {
        if (!$category) {
            return null;
        }

        // Clean and normalize
        $normalized = ucwords(strtolower(trim($category)));

        // Handle specific mappings and aliases
        $categoryMappings = [
            'General' => 'News',
            'Sci-Tech' => 'Technology',
            'Tech' => 'Technology',
            'Biz' => 'Business',
            'Sci-and-tech' => 'Technology',
            'Science-and-technology' => 'Technology',
        ];

        return $categoryMappings[$normalized] ?? $normalized;
    }

    public function generateUniqueId(array $article): string
    {
        return 'newsapi_' . md5($article['url'] ?? $article['title']);
    }

    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;

        if (!$host) {
            return null;
        }

        // Remove www. prefix for consistent matching
        return preg_replace('/^www\./', '', $host);
    }

    private function cleanTitle(string $title): string
    {
        return preg_replace('/ - [A-Z][A-Za-z\s]+$/', '', $title);
    }

    private function cleanDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }
        return preg_replace('/\.\.\.$/', '', trim($description));
    }

    private function cleanAuthor(?string $author): ?string
    {
        if (!$author) {
            return null;
        }
        return preg_replace('/\s*\([^)]*@[^)]*\)/', '', $author);
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
