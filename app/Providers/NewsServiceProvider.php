<?php

namespace App\Providers;

use App\Services\NewsProviders\GuardianProvider;
use App\Services\NewsProviders\NewsAPIProvider;
use App\Services\NewsProviders\NYTimesProvider;
use App\Transformers\GuardianTransformer;
use App\Transformers\NewsAPITransformer;
use App\Transformers\NYTimesTransformer;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        $this->app->singleton(NewsAPITransformer::class);
        $this->app->singleton(GuardianTransformer::class);
        $this->app->singleton(NYTimesTransformer::class);

        $this->app->bind(NewsAPIProvider::class, function ($app) {
            return new NewsAPIProvider(
                apiKey: config('news_providers.newsapi.key'),
                transformer: $app->make(NewsAPITransformer::class)
            );
        });

        $this->app->bind(GuardianProvider::class, function ($app) {
            return new GuardianProvider(
                apiKey:config('news_providers.guardian.key'),
                transformer: $app->make(GuardianTransformer::class)
            );
        });

        $this->app->bind(NYTimesProvider::class, function ($app) {
            return new  NYTimesProvider(
                apiKey:config('news_providers.nytimes.key'),
                transformer:$app->make(NYTimesTransformer::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
