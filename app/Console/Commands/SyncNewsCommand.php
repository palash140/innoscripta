<?php

namespace App\Console\Commands;

use App\Services\NewsProviders\NewsAPIProvider;
use App\Services\NewsProviders\GuardianProvider;
use App\Services\NewsProviders\NYTimesProvider;
use App\Jobs\SyncNewsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncNewsCommand extends Command
{
    protected $signature = 'news:sync
                          {--provider= : Specific provider to sync (newsapi, guardian, nytimes)}
                          {--records=50 : Total number of records to sync (default: 50)}
                          {--per-page=10 : Records per page/batch (default: 10)}
                          {--from= : Start date (YYYY-MM-DD)}
                          {--to= : End date (YYYY-MM-DD)}
                          {--yesterday : Sync yesterday only}
                          {--dry-run : Show what would be synced without dispatching jobs}
                          {--immediate : Run jobs synchronously instead of queuing}';

    protected $description = 'Fetch news from APIs and dispatch sync jobs';

    public function handle(): int
    {
        $provider = $this->option('provider');
        $totalRecords = (int) $this->option('records');
        $perPage = (int) $this->option('per-page');
        $dryRun = $this->option('dry-run');
        $immediate = $this->option('immediate');

        // Validate inputs
        if ($totalRecords < 1 || $totalRecords > 1000) {
            $this->error("Records must be between 1 and 1000. Got: {$totalRecords}");
            return self::FAILURE;
        }

        if ($perPage < 1 || $perPage > 100) {
            $this->error("Per-page must be between 1 and 100. Got: {$perPage}");
            return self::FAILURE;
        }

        // Parse date options
        $dateRange = $this->parseDateOptions();
        if (!$dateRange) {
            return self::FAILURE;
        }
        [$from, $to] = $dateRange;

        // Calculate pagination
        $totalPages = (int) ceil($totalRecords / $perPage);
        $syncSessionId = Str::uuid()->toString();

        $this->info("📊 Sync Configuration");
        $this->info("Session ID: {$syncSessionId}");
        $this->info("Total records to sync: {$totalRecords}");
        $this->info("Records per batch: {$perPage}");
        $this->info("Total batches: {$totalPages}");
        $this->info("Date range: {$from->format('Y-m-d')} to {$to->format('Y-m-d')}");

        if ($dryRun) {
            $this->warn("🧪 DRY RUN - No jobs will be dispatched");
        }

        if ($immediate) {
            $this->warn("⚡ IMMEDIATE MODE - Jobs will run synchronously");
        }

        $providers = $this->getProviders();

        if ($provider && !isset($providers[$provider])) {
            $this->error("Unknown provider: {$provider}");
            $this->info("Available providers: " . implode(', ', array_keys($providers)));
            return self::FAILURE;
        }

        $selectedProviders = $provider ? [$provider => $providers[$provider]] : $providers;

        $totalJobsDispatched = 0;
        $totalItemsFetched = 0;

        foreach ($selectedProviders as $providerName => $providerInstance) {
            $this->info("\n🔄 Processing {$providerName}...");

            $providerStats = $this->processProvider(
                $providerInstance,
                $providerName,
                $totalPages,
                $perPage,
                $from,
                $to,
                $syncSessionId,
                $dryRun,
                $immediate
            );

            $totalJobsDispatched += $providerStats['jobs_dispatched'];
            $totalItemsFetched += $providerStats['items_fetched'];

            $this->displayProviderStats($providerName, $providerStats);
        }

        $this->info("\n📈 Summary");
        $this->info("Total providers processed: " . count($selectedProviders));
        $this->info("Total items fetched: {$totalItemsFetched}");
        $this->info("Total jobs dispatched: {$totalJobsDispatched}");
        $this->info("Session ID: {$syncSessionId}");

        if (!$dryRun) {
            $this->info("\n💡 Monitor progress with: php artisan queue:work");
            $this->info("💡 Check status with: php artisan news:sync-status {$syncSessionId}");
        }

        return self::SUCCESS;
    }

    private function processProvider(
        $provider,
        string $providerName,
        int $totalPages,
        int $perPage,
        Carbon $from,
        Carbon $to,
        string $syncSessionId,
        bool $dryRun,
        bool $immediate
    ): array {
        $stats = [
            'provider' => $providerName,
            'pages_fetched' => 0,
            'items_fetched' => 0,
            'jobs_dispatched' => 0,
            'errors' => []
        ];

        for ($page = 1; $page <= $totalPages; $page++) {
            try {
                $this->line("  📄 Fetching page {$page}/{$totalPages}...");

                // Fetch data from API
                $newsItems = $provider->fetchNews($page, $perPage, $from, $to);

                if ($newsItems->isEmpty()) {
                    $this->line("  ⚠️  No more items available from {$providerName}");
                    break;
                }

                $stats['pages_fetched']++;
                $stats['items_fetched'] += $newsItems->count();

                $this->line("  ✅ Fetched {$newsItems->count()} items");

                if ($dryRun) {
                    $this->line("  🧪 [DRY RUN] Would dispatch sync job for {$newsItems->count()} items");
                    continue;
                }

                // Dispatch sync job
                // $syncJob = new SyncNewsJob(
                //     $newsItems,
                //     $providerName,
                //     $page,
                //     $syncSessionId
                // );

                if ($immediate) {
                    $this->line("  ⚡ Running sync job immediately...");
                    SyncNewsJob::dispatchSync($newsItems, $providerName, $page, $syncSessionId);
                } else {
                    // $syncJob->dispatch();
                    SyncNewsJob::dispatch($newsItems, $providerName, $page, $syncSessionId);
                    $this->line("  📤 Dispatched sync job to queue");
                }

                $stats['jobs_dispatched']++;

                // Rate limiting between API calls
                if ($page < $totalPages) {
                    sleep(1);
                }

            } catch (\Exception $e) {
                $this->error("  ❌ Error on page {$page}: {$e->getMessage()}");
                $stats['errors'][] = "Page {$page}: " . $e->getMessage();
            }
        }

        return $stats;
    }

    private function getProviders(): array
    {
        return [
            'newsapi' => app(NewsAPIProvider::class),
            'guardian' => app(GuardianProvider::class),
            'nytimes' => app(NYTimesProvider::class),
        ];
    }

    private function parseDateOptions(): ?array
    {
        $yesterday = $this->option('yesterday');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        try {
            if ($yesterday) {
                $date = Carbon::yesterday();
                return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            }

            if ($fromOption || $toOption) {
                $from = $fromOption ? Carbon::parse($fromOption)->startOfDay() : Carbon::yesterday()->startOfDay();
                $to = $toOption ? Carbon::parse($toOption)->endOfDay() : Carbon::yesterday()->endOfDay();

                if ($from->gt($to)) {
                    $this->error("Start date cannot be after end date");
                    return null;
                }

                return [$from, $to];
            }

            // Default to yesterday
            $date = Carbon::yesterday();
            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];

        } catch (\Exception $e) {
            $this->error("Invalid date format. Use YYYY-MM-DD format. Error: " . $e->getMessage());
            return null;
        }
    }

    private function displayProviderStats(string $provider, array $stats): void
    {
        $this->info("  📊 {$provider} Stats:");
        $this->info("    - Pages fetched: {$stats['pages_fetched']}");
        $this->info("    - Items fetched: {$stats['items_fetched']}");
        $this->info("    - Jobs dispatched: {$stats['jobs_dispatched']}");

        if (!empty($stats['errors'])) {
            $this->warn("    - Errors: " . count($stats['errors']));
            foreach ($stats['errors'] as $error) {
                $this->line("      • {$error}");
            }
        }
    }
}
