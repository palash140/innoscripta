<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsAuthorResource;
use App\Http\Resources\NewsCategoryResource;
use App\Http\Resources\NewsResource;
use App\Http\Resources\NewsSourceResource;
use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsCategory;
use App\Models\NewsSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use PDO;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        // Pagination with validation
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        // Date range with proper validation
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        // Ensure from is not after to
        if ($from->gt($to)) {
            $from = $to->copy()->subDays(30);
        }

        // Base query with relationships and date filter
        $query = News::with(['category', 'author', 'source'])
            ->whereBetween('published_at', [$from, $to])
            ->latest('published_at');

        // Apply filters if provided
        $this->applyFilters($query, $request);

        // Apply search if provided
        if ($keyword = $request->filled('keyword')) {
            $query->fullTextSearch($request->keyword);
        }

        // Paginate and preserve query parameters
        $news = $query->paginate($perPage)->appends($request->query());

        return NewsResource::collection($news);
    }

    /**
 * Apply filters to the query
 */
    /**
    * Apply filters to the query
    */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $this->getFilters($request);

        return $query
            ->when(!empty($filters['category_id']), fn ($q) => $q->where('news_category_id', $filters['category_id']))
            ->when(!empty($filters['author_id']), fn ($q) => $q->where('news_author_id', $filters['author_id']))
            ->when(!empty($filters['source_id']), fn ($q) => $q->where('news_source_id', $filters['source_id']))
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->provider));
    }

    /**
     * Get filter values from request or user preferences
     */
    private function getFilters(Request $request): array
    {
        if ($request->boolean('personalized')) {
            $preference = $request->user()?->preference;
            return [
                'category_id' => $preference?->news_category_id,
                'author_id' => $preference?->news_author_id,
                'source_id' => $preference?->news_source_id,
            ];
        }

        return $request->only(['category_id', 'author_id', 'source_id']);
    }



    public function categories(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10, allow custom
        $perPage = min($perPage, 100); // Max 100 items per page
        $categories = NewsCategory::active()
               ->ordered()
               ->withCount('news')
               ->paginate($perPage);

        return NewsCategoryResource::collection($categories);
    }

    public function authors(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10, allow custom
        $perPage = min($perPage, 100); // Max 100 items per page
        $authors = NewsAuthor::withCount('news')
                ->orderBy('news_count', 'desc')
                ->paginate($perPage);

        return NewsAuthorResource::collection($authors);
    }

    public function sources(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10, allow custom
        $perPage = min($perPage, 100); // Max 100 items per page
        $sources = NewsSource::where('is_active', true)
                ->withCount('news')
                ->orderBy('news_count', 'desc')
                ->paginate($perPage);

        return NewsSourceResource::collection($sources);
    }
}
