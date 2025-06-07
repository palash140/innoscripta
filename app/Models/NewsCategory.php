<?php

// News Category Model

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'color', 'is_active', 'sort_order', 'aliases'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'aliases' => 'array', // Store alternative names as JSON array
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'news_category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Auto-generate slug on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Helper methods
    public function addAlias(string $alias): void
    {
        $aliases = $this->aliases ?? [];
        $normalizedAlias = strtolower(trim($alias));

        if (!in_array($normalizedAlias, $aliases)) {
            $aliases[] = $normalizedAlias;
            $this->update(['aliases' => $aliases]);
        }
    }

    public function hasAlias(string $alias): bool
    {
        $aliases = $this->aliases ?? [];
        return in_array(strtolower(trim($alias)), $aliases);
    }

    public function getNewsCount(): int
    {
        return $this->news()->count();
    }

    // Static helper to find by name or alias
    public static function findByNameOrAlias(string $name): ?self
    {
        $normalized = strtolower(trim($name));

        // Try exact name match first
        $category = static::where('name', $name)
            ->orWhere('slug', Str::slug($name))
            ->first();

        if ($category) {
            return $category;
        }

        // Try alias match
        return static::whereJsonContains('aliases', $normalized)->first();
    }

    // Create category with initial aliases
    public static function createWithAliases(string $name, array $aliases = []): self
    {
        $normalizedAliases = array_map(fn ($alias) => strtolower(trim($alias)), $aliases);

        return static::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'aliases' => array_unique($normalizedAliases),
            'color' => static::generateColor($name),
            'sort_order' => static::max('sort_order') + 1,
        ]);
    }

    private static function generateColor(string $name): string
    {
        $colors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#EF4444',
            '#8B5CF6', '#6366F1', '#06B6D4', '#84CC16', '#F97316'
        ];

        $hash = crc32(strtolower($name));
        return $colors[abs($hash) % count($colors)];
    }
}
