<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'news_category_id','user_id','news_author_id', 'news_source_id'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(NewsAuthor::class, 'news_author_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }
}
