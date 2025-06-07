<?php

namespace App\Services;

use App\Models\NewsCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    private const CACHE_TTL = 3600;

    /**
     * Find category by name or alias - no mapping logic
     * Returns found category or default category
     */
    public function findCategoryByName(?string $categoryName): NewsCategory
    {
        if (!$categoryName) {
            return $this->getDefaultCategory();
        }

        $cacheKey = "find_category:" . md5(strtolower($categoryName));

        $category = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($categoryName) {
            return NewsCategory::findByNameOrAlias($categoryName);
        });

        if ($category) {
            Log::debug("Found category", [
                'search_term' => $categoryName,
                'found_category' => $category->name
            ]);
            return $category;
        }

        // Log when we can't find a category (for monitoring new categories)
        Log::info("Category not found, using default", [
            'search_term' => $categoryName,
            'default_category' => 'General'
        ]);

        return $this->getDefaultCategory();
    }

    public function getDefaultCategory(): NewsCategory
    {
        return Cache::remember('default_category', self::CACHE_TTL, function () {
            return NewsCategory::firstOrCreate(
                ['slug' => 'general'],
                [
                    'name' => 'General',
                    'description' => 'General news articles',
                    'color' => '#6B7280',
                    'sort_order' => 999,
                    'aliases' => ['general', 'misc', 'other', 'news']
                ]
            );
        });
    }

    public function clearCache(): void
    {
        Cache::forget('default_category');
        // Clear find_category cache (would need pattern-based clearing in production)
    }
}
