<?php

// News Source Model

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsSource extends Model
{
    protected $fillable = [
        'name', 'slug', 'domain', 'provider', 'description', 'logo_url',
        'website_url', 'country', 'language', 'is_active', 'categories'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'categories' => 'array',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($source) {
            if (empty($source->slug)) {
                $source->slug = Str::slug($source->name);
            }
        });
    }

    public static function findOrCreateByDomain(string $domain, string $provider): self
    {
        return static::firstOrCreate(
            ['domain' => $domain, 'provider' => $provider],
            [
                'name' => static::generateNameFromDomain($domain),
                'website_url' => 'https://' . $domain,
            ]
        );
    }

    public static function findOrCreateByProviderSource(string $provider, string $sourceName): self
    {
        $slug = Str::slug($sourceName);

        return static::firstOrCreate(
            ['slug' => $slug, 'provider' => $provider],
            [
                'name' => $sourceName,
                'domain' => static::generateDomainFromName($sourceName),
            ]
        );
    }

    private static function generateNameFromDomain(string $domain): string
    {
        // Convert domain to readable name
        $name = str_replace(['www.', '.com', '.co.uk', '.org', '.net'], '', $domain);

        // Handle special cases
        $specialCases = [
            'bbc' => 'BBC',
            'cnn' => 'CNN',
            'nytimes' => 'The New York Times',
            'theguardian' => 'The Guardian',
            'techcrunch' => 'TechCrunch',
            'engadget' => 'Engadget',
            'reuters' => 'Reuters',
            'bloomberg' => 'Bloomberg',
        ];

        $cleanDomain = strtolower($name);

        return $specialCases[$cleanDomain] ?? ucwords(str_replace(['-', '_'], ' ', $name));
    }

    private static function generateDomainFromName(string $name): string
    {
        // Simple mapping for common sources
        $domainMap = [
            'The Guardian' => 'theguardian.com',
            'The New York Times' => 'nytimes.com',
            'BBC' => 'bbc.co.uk',
            'CNN' => 'cnn.com',
            'Reuters' => 'reuters.com',
        ];

        return $domainMap[$name] ?? strtolower(str_replace(' ', '', $name)) . '.com';
    }
}
