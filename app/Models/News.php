<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class News extends Model
{
    protected $fillable = [
        'unique_id', 'title', 'description', 'news_category_id',
        'news_author_id', 'news_source_id', 'provider', 'source_url', 'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(NewsAuthor::class, 'news_author_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    // Scopes
    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('news_category_id', $categoryId);
    }

    /**
     * Full-text search using MySQL MATCH AGAINST
     * Assumes you have a FULLTEXT index on title, description fields
     */
    public function scopeFullTextSearch(Builder $query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->whereRaw(
            "MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$search]
        );
    }

    public function scopeByCategorySlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
    }

    public function scopeByAuthor(Builder $query, $authorId): Builder
    {
        return $query->where('news_author_id', $authorId);
    }

    public function scopeByAuthorSlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('author', fn ($q) => $q->where('slug', $slug));
    }

    public function scopeBySource(Builder $query, $sourceId): Builder
    {
        return $query->where('news_source_id', $sourceId);
    }

    public function scopeBySourceSlug(Builder $query, string $slug): Builder
    {
        return $query->whereHas('source', fn ($q) => $q->where('slug', $slug));
    }

    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc')->orderBy('created_at', 'desc');
    }
}
