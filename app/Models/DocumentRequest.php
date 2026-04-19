<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    protected $fillable = [
        'tracking_number', 'document_type', 'purpose', 'fee', 'or_number',
        'status', 'remarks', 'issued_at',
        'resident_id', 'requested_by', 'processed_by',
        'business_name', 'business_type', 'business_address',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'fee'       => 'decimal:2',
    ];

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public static function generateTrackingNumber(): string
    {
        $year  = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'DOC-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public static function generateOrNumber(): string
    {
        $year = date('Y');
        do {
            $count = static::whereYear('created_at', $year)->whereNotNull('or_number')->count() + 1;
            $candidate = 'OR-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        } while (static::where('or_number', $candidate)->exists());

        return $candidate;
    }
}
