<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A database backup file stored privately on Cloudinary. */
class Backup extends Model
{
    protected $fillable = [
        'filename', 'public_id', 'size', 'tables', 'rows',
        'trigger', 'status', 'error', 'emailed_to', 'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function getHumanSizeAttribute(): string
    {
        $size = (int) $this->size;
        if ($size < 1024) {
            return $size . ' B';
        }

        return $size < 1048576 ? round($size / 1024, 1) . ' KB' : round($size / 1048576, 2) . ' MB';
    }
}
