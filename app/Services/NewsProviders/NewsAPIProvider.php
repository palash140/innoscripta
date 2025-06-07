<?php

// Simple NewsAPI Provider - Loads sources in constructor

namespace App\Services\NewsProviders;

use App\Contracts\NewsProviderInterface;
use App\Transformers\NewsAPITransformer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NewsAPIProvider implements NewsProviderInterface
{
    private const BASE_URL = 'https://newsapi.org/v2';
    private const SOURCES_CACHE_KEY = 'newsapi_sources';
    private const SOURCES_CACHE_TTL = 86400; // 24 hours

    // Popular domains for better quality news
    private const DEFAULT_DOMAINS = [
        'bbc.co.uk',
        'techcrunch.com',
        'engadget.com',
        'reuters.com',
        'bloomberg.com',
        'theverge.com',
        'arstechnica.com',
        'wired.com',
        'cnn.com',
        'forbes.com'
    ];

    private array $sourcesData = [];

    public function __construct(
        private readonly string $apiKey,
        private readonly NewsAPITransformer $transformer,
        private readonly array $domains = self::DEFAULT_DOMAINS
    ) {
        // Load sources data immediately in constructor
        $this->loadSourcesData();
    }

    /**
     * Load sources data from cache or API
     */
    private function loadSourcesData(): void
    {
        $this->sourcesData = Cache::remember(self::SOURCES_CACHE_KEY, self::SOURCES_CACHE_TTL, function () {
            return $this->fetchSourcesFromAPI();
        });

        Log::info('NewsAPI: Sources loaded', [
            'sources_count' => count($this->sourcesData),
            'cached' => Cache::has(self::SOURCES_CACHE_KEY)
        ]);
    }

    /**
     * Fetch sources directly from NewsAPI
     */
    private function fetchSourcesFromAPI(): array
    {
        try {
            Log::info('NewsAPI: Fetching sources from API');

            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL . '/sources', [
                    'apiKey' => $this->apiKey,
                    'language' => 'en'
                ]);

            if (!$response->successful()) {
                Log::error('NewsAPI sources fetch failed', ['response' => $response->body()]);
                return [];
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'error') {
                Log::error('NewsAPI sources error', ['message' => $data['message'] ?? 'Unknown error']);
                return [];
            }

            return $data['sources'] ?? [];

        } catch (\Exception $e) {
            Log::error('NewsAPI sources fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchNews(
        int $page = 1,
        int $perPage = 20,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        try {
            // Default to yesterday if no dates provided
            if (!$from || !$to) {
                $yesterday = Carbon::yesterday();
                $from = $yesterday->copy()->startOfDay();
                $to = $yesterday->copy()->endOfDay();
            }

            $params = [
                'apiKey' => $this->apiKey,
                'page' => $page,
                'pageSize' => $perPage,
                'sortBy' => 'publishedAt',
                'language' => 'en',
                'from' => $from->toISOString(),
                'to' => $to->toISOString(),
                'domains' => implode(',', $this->domains),
                'q' => '*',
            ];

            Log::info("NewsAPI: Fetching news", [
                'page' => $page,
                'domains' => implode(',', $this->domains),
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString()
            ]);

            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL . '/everything', $params);

            if (!$response->successful()) {
                throw new \Exception('NewsAPI request failed: ' . $response->body());
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === 'error') {
                throw new \Exception('NewsAPI error: ' . ($data['message'] ?? 'Unknown error'));
            }

            Log::info("NewsAPI: Received {$data['totalResults']} total results");

            // Pass sources data to transformer
            return $this->transformer->transformCollection($data['articles'] ?? [], $this->sourcesData);

        } catch (\Exception $e) {
            Log::error('NewsAPI fetch error', [
                'error' => $e->getMessage(),
                'page' => $page,
                'provider' => $this->getProviderName()
            ]);

            return collect();
        }
    }

    /**
     * Get sources data
     */
    public function getSourcesData(): array
    {
        return $this->sourcesData;
    }

    /**
     * Clear sources cache and reload
     */
    public function clearSourcesCache(): void
    {
        Cache::forget(self::SOURCES_CACHE_KEY);
        $this->loadSourcesData();
        Log::info('NewsAPI: Sources cache cleared and reloaded');
    }

    public function getProviderName(): string
    {
        return 'newsapi';
    }

    public function getConfiguredDomains(): array
    {
        return $this->domains;
    }

    public function withDomains(array $domains): self
    {
        return new self($this->apiKey, $this->transformer, $domains);
    }
}
