<?php

// News Author Model

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsAuthor extends Model
{
    protected $fillable = [
        'name', 'slug', 'email', 'bio', 'avatar_url', 'social_links', 'is_verified'
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_verified' => 'boolean',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($author) {
            if (empty($author->slug)) {
                $author->slug = static::generateUniqueSlug($author->name);
            }
        });
    }

    public static function findOrCreateByName(string $name): self
    {
        // Clean the author name
        $cleanName = static::cleanAuthorName($name);
        $slug = static::generateUniqueSlug($cleanName);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $cleanName]
        );
    }

    private static function cleanAuthorName(string $name): string
    {
        // Remove common prefixes/suffixes
        $name = preg_replace('/^(By\s+|Author:\s*)/i', '', $name);
        $name = preg_replace('/\s*\([^)]*@[^)]*\)/', '', $name); // Remove emails
        $name = preg_replace('/\s*\|\s*.*$/', '', $name); // Remove "| CNN" etc

        return trim($name);
    }

    private static function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
