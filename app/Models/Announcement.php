<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'content', 'category', 'audience',
        'status', 'published_at', 'expires_at', 'posted_by',
    ];

    protected $casts = [
        'published_at' => 'date',
        'expires_at'   => 'date',
    ];

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'Published');
    }
}
