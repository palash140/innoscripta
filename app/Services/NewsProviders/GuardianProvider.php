<?php

namespace App\Services\NewsProviders;

use App\Contracts\NewsProviderInterface;
use App\Transformers\GuardianTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class GuardianProvider implements NewsProviderInterface
{
    private const BASE_URL = 'https://content.guardianapis.com';

    public function __construct(
        private readonly string $apiKey,
        private readonly GuardianTransformer $transformer
    ) {
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

            $response = Http::get(self::BASE_URL . '/search', [
                'api-key' => $this->apiKey,
                'page' => $page,
                'from-date' => $from->toDateString(),
                'to-date' => $to->toDateString(),
                'page-size' => $perPage,
                'show-fields' => 'headline,trailText,byline,standfirst',
                'order-by' => 'newest'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Guardian API request failed: ' . $response->body());
            }

            $data = $response->json();

            return $this->transformer->transformCollection($data['response']['results'] ?? []);

        } catch (\Exception $e) {
            Log::error('Guardian API fetch error', [
                'error' => $e->getMessage(),
                'page' => $page,
                'provider' => $this->getProviderName()
            ]);

            return collect();
        }
    }

    public function getProviderName(): string
    {
        return 'guardian';
    }
}
