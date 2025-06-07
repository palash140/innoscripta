<?php

namespace App\Services;

use App\Models\NewsCategory;
use App\Models\NewsAuthor;
use App\Models\NewsSource;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EntityResolutionService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    /**
     * Simple category resolution - just find or return default
     */
    public function resolveCategoryId(?string $categoryName): ?int
    {
        $category = $this->categoryService->findCategoryByName($categoryName);
        return $category->id;
    }

    public function resolveAuthorId(?string $authorName): ?int
    {
        if (!$authorName || strlen(trim($authorName)) < 2) {
            return null;
        }

        $cacheKey = "author_id:" . md5(strtolower($authorName));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($authorName) {
            $author = NewsAuthor::findOrCreateByName($authorName);
            return $author->id;
        });
    }

    public function resolveSourceId(string $provider, ?string $sourceName = null, ?string $domain = null): ?int
    {
        if (!$sourceName && !$domain) {
            return $this->getDefaultSourceId($provider);
        }

        $cacheKey = "source_id:" . md5($provider . ':' . ($domain ?: $sourceName));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provider, $sourceName, $domain) {
            if ($domain) {
                $source = NewsSource::findOrCreateByDomain($domain, $provider);
            } else {
                $source = NewsSource::findOrCreateByProviderSource($provider, $sourceName);
            }
            return $source->id;
        });
    }

    private function getDefaultSourceId(string $provider): int
    {
        return Cache::remember("default_source_id:{$provider}", self::CACHE_TTL, function () use ($provider) {
            $sourceName = match($provider) {
                'newsapi' => 'NewsAPI',
                'guardian' => 'The Guardian',
                'nytimes' => 'The New York Times',
                default => ucfirst($provider)
            };

            $source = NewsSource::findOrCreateByProviderSource($provider, $sourceName);
            return $source->id;
        });
    }
}
