<?php

namespace App\Services;

use App\DTOs\NewsItemDTO;
use App\Models\News;
use App\Services\EntityResolutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsPersistenceService
{
    public function __construct(
        private readonly EntityResolutionService $entityResolver
    ) {
    }

    public function saveNewsItem(NewsItemDTO $newsItem): ?News
    {
        try {
            // Resolve all entities to IDs
            $categoryId = $this->entityResolver->resolveCategoryId($newsItem->categoryName);
            $authorId = $this->entityResolver->resolveAuthorId($newsItem->authorName);
            $sourceId = $this->entityResolver->resolveSourceId(
                $newsItem->provider,
                $newsItem->sourceName,
                $newsItem->sourceDomain
            );

            $existingNews = News::where('unique_id', $newsItem->uniqueId)->first();

            if (!$existingNews) {
                return News::create($newsItem->toDatabaseArray($categoryId, $authorId, $sourceId));
            } else {
                // Only update if content changed
                if ($this->hasContentChanged($existingNews, $newsItem, $categoryId, $authorId, $sourceId)) {
                    $existingNews->update($newsItem->toDatabaseArray($categoryId, $authorId, $sourceId));
                    return $existingNews->fresh();
                }
                return $existingNews;
            }
        } catch (\Exception $e) {
            Log::error('Error saving news item', [
                'unique_id' => $newsItem->uniqueId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function saveNewsItems(array $newsItems): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        DB::transaction(function () use ($newsItems, &$stats) {
            foreach ($newsItems as $newsItem) {
                if (!$newsItem instanceof NewsItemDTO) {
                    $stats['skipped']++;
                    continue;
                }

                try {
                    // Resolve entities
                    $categoryId = $this->entityResolver->resolveCategoryId($newsItem->categoryName);
                    $authorId = $this->entityResolver->resolveAuthorId($newsItem->authorName);
                    $sourceId = $this->entityResolver->resolveSourceId(
                        $newsItem->provider,
                        $newsItem->sourceName,
                        $newsItem->sourceDomain
                    );

                    $existingNews = News::where('unique_id', $newsItem->uniqueId)->first();

                    if (!$existingNews) {
                        News::create($newsItem->toDatabaseArray($categoryId, $authorId, $sourceId));
                        $stats['created']++;
                    } else {
                        if ($this->hasContentChanged($existingNews, $newsItem, $categoryId, $authorId, $sourceId)) {
                            $existingNews->update($newsItem->toDatabaseArray($categoryId, $authorId, $sourceId));
                            $stats['updated']++;
                        } else {
                            $stats['skipped']++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error saving news item in batch', [
                        'unique_id' => $newsItem->uniqueId,
                        'error' => $e->getMessage()
                    ]);
                    $stats['errors']++;
                }
            }
        });

        return $stats;
    }

    private function hasContentChanged($existingNews, NewsItemDTO $newItem, ?int $categoryId, ?int $authorId, ?int $sourceId): bool
    {
        return $existingNews->title !== $newItem->title ||
               $existingNews->description !== $newItem->description ||
               $existingNews->news_category_id !== $categoryId ||
               $existingNews->news_author_id !== $authorId ||
               $existingNews->news_source_id !== $sourceId;
    }
}
