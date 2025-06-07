<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {

        // Daily sync of yesterday's news - smaller batches for reliability
        // $schedule->command('news:sync --yesterday --records=100 --per-page=10')
        //          ->dailyAt('02:00')
        //          ->withoutOverlapping(60) // 60 minute overlap protection
        //          ->runInBackground()
        //          ->emailOutputOnFailure(config('app.admin_email'))
        //          ->appendOutputTo(storage_path('logs/news-sync.log'));

        // // Staggered provider syncs for redundancy
        // $schedule->command('news:sync --provider=newsapi --yesterday --records=50')
        //          ->dailyAt('02:15')
        //          ->withoutOverlapping();

        // $schedule->command('news:sync --provider=guardian --yesterday --records=50')
        //          ->dailyAt('02:30')
        //          ->withoutOverlapping();

        // $schedule->command('news:sync --provider=nytimes --yesterday --records=50')
        //          ->dailyAt('02:45')
        //          ->withoutOverlapping();

        // // Hourly smaller syncs during business hours
        // $schedule->command('news:sync --records=20 --per-page=5')
        //          ->hourlyAt(15) // Every hour at 15 minutes past
        //          ->between('09:00', '18:00')
        //          ->weekdays()
        //          ->withoutOverlapping();

        // // Weekend comprehensive sync
        // $schedule->command('news:sync --records=200 --per-page=20')
        //          ->weeklyOn(0, '03:00') // Sunday at 3 AM
        //          ->withoutOverlapping();

        // // Clean up old sync statuses
        // $schedule->call(function () {
        //     \Illuminate\Support\Facades\Cache::tags(['sync_status'])->flush();
        // })->weekly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
