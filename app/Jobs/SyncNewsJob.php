<?php

namespace App\Jobs;

use App\Services\NewsPersistenceService;
use App\DTOs\NewsItemDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SyncNewsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 30;

    public function __construct(
        private readonly Collection $newsItems, // Collection of NewsItemDTO
        private readonly string $provider,
        private readonly int $batchNumber,
        private readonly string $syncSessionId
    ) {
        // Use specific queue based on provider
        $this->onQueue("sync_{$provider}");
    }

    public function handle(NewsPersistenceService $persistenceService): void
    {
        $startTime = microtime(true);

        Log::info("Starting news sync job", [
            'provider' => $this->provider,
            'batch_number' => $this->batchNumber,
            'items_count' => $this->newsItems->count(),
            'sync_session_id' => $this->syncSessionId
        ]);

        try {
            // Convert collection items to array for processing
            $newsItemsArray = $this->newsItems->map(function ($item) {
                // Ensure we have NewsItemDTO objects
                if (is_array($item)) {
                    return new NewsItemDTO(...array_values($item));
                }
                return $item;
            })->toArray();

            // Persist news items to database
            $stats = $persistenceService->saveNewsItems($newsItemsArray);

            $duration = microtime(true) - $startTime;

            Log::info("News sync job completed", [
                'provider' => $this->provider,
                'batch_number' => $this->batchNumber,
                'sync_session_id' => $this->syncSessionId,
                'stats' => $stats,
                'duration_seconds' => round($duration, 2),
                'items_per_second' => round($this->newsItems->count() / $duration, 2)
            ]);

            // Update sync status if needed
            $this->updateSyncStatus('completed', $stats);

        } catch (\Exception $e) {
            Log::error("News sync job failed", [
                'provider' => $this->provider,
                'batch_number' => $this->batchNumber,
                'sync_session_id' => $this->syncSessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateSyncStatus('failed', ['error' => $e->getMessage()]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("News sync job failed permanently", [
            'provider' => $this->provider,
            'batch_number' => $this->batchNumber,
            'sync_session_id' => $this->syncSessionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->updateSyncStatus('failed_permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    private function updateSyncStatus(string $status, array $data = []): void
    {
        // Optional: Update sync status table if you have one
        // This helps track job progress across the system
        try {
            \Illuminate\Support\Facades\Cache::put(
                "sync_status:{$this->syncSessionId}:{$this->provider}:{$this->batchNumber}",
                [
                    'status' => $status,
                    'data' => $data,
                    'updated_at' => now(),
                ],
                3600 // 1 hour
            );
        } catch (\Exception $e) {
            // Don't fail the job if status update fails
            Log::warning("Failed to update sync status", ['error' => $e->getMessage()]);
        }
    }
}
