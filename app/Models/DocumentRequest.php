<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class DocumentRequest extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'or_number', 'purpose', 'remarks', 'id_verified'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('document');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Document request {$this->tracking_number} submitted",
            'updated' => "Document request {$this->tracking_number} updated",
            'deleted' => "Document request {$this->tracking_number} deleted",
            default   => "Document request {$this->tracking_number} {$eventName}",
        };
    }

    protected $fillable = [
        'tracking_number', 'document_type', 'purpose', 'fee', 'or_number',
        'id_photo_url', 'id_photo_public_id', 'id_verified',
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
        $year = date('Y');
        $prefix = "DOC-{$year}-";
        do {
            $last = static::where('tracking_number', 'like', $prefix . '%')->max('tracking_number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
            $candidate = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
        } while (static::where('tracking_number', $candidate)->exists());

        return $candidate;
    }

    public static function generateOrNumber(): string
    {
        $year = date('Y');
        $prefix = "OR-{$year}-";
        do {
            $last = static::whereNotNull('or_number')
                ->where('or_number', 'like', $prefix . '%')
                ->max('or_number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
            $candidate = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        } while (static::where('or_number', $candidate)->exists());

        return $candidate;
    }
}
