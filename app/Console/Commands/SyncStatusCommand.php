<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncStatusCommand extends Command
{
    protected $signature = 'news:sync-status
                          {session_id? : Sync session ID to check}
                          {--recent : Show recent sync sessions}';

    protected $description = 'Check status of news sync operations';

    public function handle(): int
    {
        $sessionId = $this->argument('session_id');
        $recent = $this->option('recent');

        if ($recent) {
            return $this->showRecentSessions();
        }

        if (!$sessionId) {
            $this->error("Please provide a session ID or use --recent option");
            return self::FAILURE;
        }

        return $this->showSessionStatus($sessionId);
    }

    private function showSessionStatus(string $sessionId): int
    {
        $this->info("📊 Sync Status for Session: {$sessionId}");
        $this->line("");

        $providers = ['newsapi', 'guardian', 'nytimes'];
        $found = false;

        foreach ($providers as $provider) {
            $statuses = $this->getProviderStatuses($sessionId, $provider);

            if (!empty($statuses)) {
                $found = true;
                $this->displayProviderStatus($provider, $statuses);
            }
        }

        if (!$found) {
            $this->warn("No sync status found for session: {$sessionId}");
            $this->info("Session might be too old or invalid.");
        }

        return self::SUCCESS;
    }

    private function getProviderStatuses(string $sessionId, string $provider): array
    {
        $statuses = [];

        // Check for up to 20 batches (should be enough for most syncs)
        for ($batch = 1; $batch <= 20; $batch++) {
            $status = Cache::get("sync_status:{$sessionId}:{$provider}:{$batch}");
            if ($status) {
                $statuses[$batch] = $status;
            }
        }

        return $statuses;
    }

    private function displayProviderStatus(string $provider, array $statuses): void
    {
        $this->info("🔄 {$provider}");

        $completed = 0;
        $failed = 0;
        $totalItems = 0;

        foreach ($statuses as $batch => $status) {
            $statusIcon = match($status['status']) {
                'completed' => '✅',
                'failed', 'failed_permanently' => '❌',
                default => '⏳'
            };

            $message = "  Batch {$batch}: {$statusIcon} {$status['status']}";

            if (isset($status['data']['created'])) {
                $created = $status['data']['created'];
                $updated = $status['data']['updated'];
                $message .= " (Created: {$created}, Updated: {$updated})";
                $totalItems += $created + $updated;
            }

            if (isset($status['data']['error'])) {
                $message .= " - Error: " . substr($status['data']['error'], 0, 50) . "...";
            }

            $this->line($message);

            if ($status['status'] === 'completed') {
                $completed++;
            } elseif (in_array($status['status'], ['failed', 'failed_permanently'])) {
                $failed++;
            }
        }

        $total = count($statuses);
        $this->line("  Summary: {$completed}/{$total} completed, {$failed} failed, {$totalItems} items processed");
        $this->line("");
    }

    private function showRecentSessions(): int
    {
        $this->info("Recent sync sessions are stored in cache.");
        $this->info("Use specific session ID to get detailed status.");
        return self::SUCCESS;
    }
}
