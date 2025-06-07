<?php

namespace App\Services\NewsProviders;

use App\Transformers\NYTimesTransformer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NYTimesProvider
{
    private const BASE_URL = 'https://api.nytimes.com/svc';

    public function __construct(
        private readonly string $apiKey,
        private readonly NYTimesTransformer $transformer
    ) {
    }

    public function fetchNews(
        int $page = 1,
        int $perPage = 10,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        try {
            // Default to yesterday if no dates provided
            if (!$from || !$to) {
                $yesterday = Carbon::yesterday();
                $from = $yesterday->copy()->startOfDay();
                $to = Carbon::now()->startOfDay();
            }

            $params = [
                'api-key' => $this->apiKey,
                'page' => $page - 1, // NYTimes uses 0-based pagination
                'sort' => 'newest',
                // NYTimes date format: YYYYMMDD
                'begin_date' => $from->format('Ymd'),
                'end_date' => $to->format('Ymd'),
            ];

            Log::info("NYTimes: Fetching news", [
                'page' => $page,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'begin_date' => $params['begin_date'],
                'end_date' => $params['end_date']
            ]);

            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL . '/search/v2/articlesearch.json', $params);

            if (!$response->successful()) {
                throw new \Exception('NYTimes API request failed: ' . $response->body());
            }

            $data = $response->json();

            // Check for API errors
            if (isset($data['fault'])) {
                throw new \Exception('NYTimes API error: ' . ($data['fault']['faultstring'] ?? 'Unknown error'));
            }

            // Check for status errors
            if (isset($data['status']) && $data['status'] !== 'OK') {
                throw new \Exception('NYTimes API status error: ' . ($data['message'] ?? 'Unknown error'));
            }

            $docs = $data['response']['docs'] ?? [];
            Log::info("NYTimes: Received " . count($docs) . " articles");

            return $this->transformer->transformCollection($docs);

        } catch (\Exception $e) {
            Log::error('NYTimes API fetch error', [
                'error' => $e->getMessage(),
                'page' => $page,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'provider' => $this->getProviderName()
            ]);

            return collect();
        }
    }

    public function getProviderName(): string
    {
        return 'nytimes';
    }

    // Enhanced section fetch with date range
    public function fetchNewsBySection(string $section, int $limit = 20, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        try {
            // For section API, we'll use the search API with section filter and date range
            if (!$from || !$to) {
                $yesterday = Carbon::yesterday();
                $from = $yesterday->copy()->startOfDay();
                $to = $yesterday->copy()->endOfDay();
            }

            $params = [
                'api-key' => $this->apiKey,
                'fq' => "section_name:\"{$section}\" AND document_type:(\"article\")", // Filter by section and articles only
                'sort' => 'newest',
                'fl' => 'headline,abstract,byline,pub_date,web_url,_id,section_name,lead_paragraph,snippet',
                'begin_date' => $from->format('Ymd'),
                'end_date' => $to->format('Ymd'),
                'page' => 0, // Start from first page
            ];

            Log::info("NYTimes: Fetching section news", [
                'section' => $section,
                'from' => $from->toDateString(),
                'to' => $to->toDateString()
            ]);

            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get(self::BASE_URL . '/search/v2/articlesearch.json', $params);

            if (!$response->successful()) {
                throw new \Exception("NYTimes section API request failed: " . $response->body());
            }

            $data = $response->json();

            if (isset($data['fault'])) {
                throw new \Exception('NYTimes API error: ' . ($data['fault']['faultstring'] ?? 'Unknown error'));
            }

            $docs = collect($data['response']['docs'] ?? [])->take($limit)->toArray();

            return $this->transformer->transformCollection($docs);

        } catch (\Exception $e) {
            Log::error('NYTimes section fetch error', [
                'error' => $e->getMessage(),
                'section' => $section,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString()
            ]);

            return collect();
        }
    }

    // Get available sections
    public function getAvailableSections(): array
    {
        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . '/news/v3/content/section-list.json', [
                    'api-key' => $this->apiKey
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['results'] ?? [])
                    ->pluck('section')
                    ->filter() // Remove empty sections
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch NYTimes sections', ['error' => $e->getMessage()]);
        }

        // Fallback to common sections
        return [
            'world', 'us', 'politics', 'business', 'technology',
            'science', 'health', 'sports', 'arts', 'books',
            'movies', 'theater', 'opinion', 'food', 'travel'
        ];
    }

    // Helper method for yesterday's range
    public function getYesterdayDateRange(): array
    {
        $yesterday = Carbon::yesterday();
        return [
            'from' => $yesterday->copy()->startOfDay(),
            'to' => $yesterday->copy()->endOfDay(),
        ];
    }

    // Get most popular articles (different endpoint)
    public function getMostPopular(string $period = 'viewed', int $days = 1): Collection
    {
        try {
            $validPeriods = ['viewed', 'shared', 'emailed'];
            $validDays = [1, 7, 30];

            if (!in_array($period, $validPeriods)) {
                $period = 'viewed';
            }

            if (!in_array($days, $validDays)) {
                $days = 1;
            }

            $response = Http::timeout(15)
                ->get(self::BASE_URL . "/mostpopular/v2/{$period}/{$days}.json", [
                    'api-key' => $this->apiKey
                ]);

            if (!$response->successful()) {
                throw new \Exception('NYTimes Most Popular API request failed: ' . $response->body());
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                throw new \Exception('NYTimes Most Popular API error: ' . ($data['fault']['faultstring'] ?? 'Unknown error'));
            }

            // Transform most popular format to search format for consistency
            $articles = collect($data['results'] ?? [])->map(function ($article) {
                return [
                    '_id' => $article['uri'] ?? $article['id'] ?? null,
                    'web_url' => $article['url'] ?? null,
                    'headline' => ['main' => $article['title'] ?? ''],
                    'abstract' => $article['abstract'] ?? null,
                    'byline' => ['original' => $article['byline'] ?? null],
                    'pub_date' => $article['published_date'] ?? null,
                    'section_name' => $article['section'] ?? null,
                    'lead_paragraph' => $article['abstract'] ?? null,
                ];
            })->toArray();

            return $this->transformer->transformCollection($articles);

        } catch (\Exception $e) {
            Log::error('NYTimes Most Popular fetch error', [
                'error' => $e->getMessage(),
                'period' => $period,
                'days' => $days
            ]);

            return collect();
        }
    }
}





// use App\Contracts\NewsProviderInterface;
// use App\Transformers\NYTimesTransformer;
// use Carbon\Carbon;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Collection;

// class NYTimesProvider implements NewsProviderInterface
// {
//     private const BASE_URL = 'https://api.nytimes.com/svc';

//     public function __construct(
//         private readonly string $apiKey,
//         private readonly NYTimesTransformer $transformer
//     ) {
//     }

//     public function fetchNews(
//         int $page = 1,
//         int $perPage = 20,
//         ?Carbon $from = null,
//         ?Carbon $to = null
//     ): Collection {
//         try {

//             // Default to yesterday if no dates provided
//             if (!$from || !$to) {
//                 $yesterday = Carbon::yesterday();
//                 $from = $yesterday->copy()->startOfDay();
//                 $to = Carbon::now()->startOfDay();
//             }

//             $response = Http::timeout(30)
//                 ->retry(3, 1000)
//                 ->get(self::BASE_URL . '/search/v2/articlesearch.json', [
//                     'api-key' => $this->apiKey,
//                     'page' => $page - 1, // NYTimes uses 0-based pagination
//                     'sort' => 'newest',
//                     'begin_date' => $from->format('Ymd'),
//                     'end_date' => $to->format('Ymd'),
//                     'fl' => 'headline,abstract,byline,pub_date,web_url,_id,section_name,lead_paragraph,snippet'
//                 ]);

//             if (!$response->successful()) {
//                 throw new \Exception('NYTimes API request failed: ' . $response->body());
//             }

//             $data = $response->json();

//             // Check for API errors
//             if (isset($data['fault'])) {
//                 throw new \Exception('NYTimes API error: ' . ($data['fault']['faultstring'] ?? 'Unknown error'));
//             }

//             return $this->transformer->transformCollection($data['response']['docs'] ?? []);

//         } catch (\Exception $e) {
//             Log::error('NYTimes API fetch error', [
//                 'error' => $e->getMessage(),
//                 'page' => $page,
//                 'provider' => $this->getProviderName()
//             ]);

//             return collect();
//         }
//     }

//     public function getProviderName(): string
//     {
//         return 'nytimes';
//     }

// }
