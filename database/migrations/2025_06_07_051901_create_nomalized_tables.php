<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Categories table
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // "Technology", "Business", etc.
            $table->string('slug')->unique(); // "technology", "business", etc.
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            // Store provider aliases as JSON for flexible mapping
            $table->json('aliases')->nullable(); // ["tech", "technology", "sci-tech"]
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
        });

        // Authors table
        Schema::create('news_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('bio', 500)->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('social_links')->nullable(); // Twitter, LinkedIn, etc.
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('slug');
            $table->index(['name', 'is_verified']);
        });

        // News sources table
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "BBC", "TechCrunch"
            $table->string('slug')->unique(); // e.g., "bbc", "techcrunch"
            $table->string('domain'); // e.g., "bbc.co.uk", "techcrunch.com"
            $table->string('provider'); // newsapi, guardian, nytimes
            $table->string('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('country', 2)->nullable(); // ISO country code
            $table->string('language', 2)->default('en');
            $table->boolean('is_active')->default(true);
            $table->json('categories')->nullable(); // Which categories this source typically covers
            $table->timestamps();

            $table->unique(['domain', 'provider']);
            $table->index(['provider', 'is_active']);
            $table->index('domain');
            $table->index('slug');
        });

        // Updated news table
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->onDelete('set null');
            $table->foreignId('news_author_id')->nullable()->constrained('news_authors')->onDelete('set null');
            $table->foreignId('news_source_id')->nullable()->constrained('news_sources')->onDelete('set null');
            $table->string('provider'); // newsapi, guardian, nytimes (for reference)
            $table->string('source_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['news_category_id', 'published_at']);
            $table->index(['news_source_id', 'published_at']);
            $table->index(['news_author_id', 'published_at']);
            $table->index(['provider', 'published_at']);
            $table->index('published_at');
        });

        DB::statement('ALTER TABLE news ADD FULLTEXT search_index (title, description)');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
        Schema::dropIfExists('news_sources');
        Schema::dropIfExists('news_authors');
        Schema::dropIfExists('news_categories');
    }
};
