<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Announcement extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'category', 'audience'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('announcement');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Announcement \"{$this->title}\" created",
            'updated' => "Announcement \"{$this->title}\" updated",
            'deleted' => "Announcement \"{$this->title}\" deleted",
            default   => "Announcement \"{$this->title}\" {$eventName}",
        };
    }

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
