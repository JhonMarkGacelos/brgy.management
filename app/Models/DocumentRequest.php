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
            ->logOnly(['status', 'or_number', 'purpose', 'remarks', 'id_verified', 'payment_verified'])
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
        'payment_receipt_url', 'payment_receipt_public_id', 'payment_verified',
        'status', 'remarks', 'issued_at', 'paid_at',
        'resident_id', 'requested_by', 'processed_by',
        'business_name', 'business_type', 'business_address',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'paid_at'   => 'datetime',
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

    public const TYPES    = ['Barangay Clearance', 'Certificate of Indigency', 'Certificate of Residency', 'Business Clearance'];
    public const STATUSES = ['Pending', 'Pending Official', 'Approved', 'Issued', 'Rejected'];

    /** Current fee per document type (Settings), shared by staff and portal request forms. */
    public static function currentFees(): array
    {
        return [
            'Barangay Clearance'       => (float) Setting::get('fee_barangay_clearance', 50),
            'Certificate of Residency' => (float) Setting::get('fee_certificate_of_residency', 50),
            'Certificate of Indigency' => (float) Setting::get('fee_certificate_of_indigency', 0),
            'Business Clearance'       => (float) Setting::get('fee_business_clearance', 200),
        ];
    }

    /** Paid document types recorded with an OR number but a ₱0 fee — usually a missed fee. Advisory only. */
    public function scopeNeedsReview($query)
    {
        $paidTypes = array_keys(array_filter(self::currentFees(), fn ($fee) => $fee > 0));

        return $query->whereNotNull('or_number')->where('fee', '<=', 0)->whereIn('document_type', $paidTypes);
    }

    public function getReviewFlagAttribute(): ?string
    {
        $fee = self::currentFees()[$this->document_type] ?? 0;

        return $this->or_number && (float) $this->fee <= 0 && $fee > 0
            ? "Recorded with OR {$this->or_number} but a ₱0 fee, although a {$this->document_type} currently costs ₱" . number_format($fee, 2) . '. Check whether the fee was waived or missed.'
            : null;
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
