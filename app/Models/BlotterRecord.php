<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class BlotterRecord extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'remarks', 'action_taken', 'incident_type', 'narrative'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('blotter');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match($eventName) {
            'created' => "Complaint case {$this->case_number} filed",
            'updated' => "Complaint case {$this->case_number} updated",
            'deleted' => "Complaint case {$this->case_number} deleted",
            default   => "Complaint case {$this->case_number} {$eventName}",
        };
    }

    protected $fillable = [
        'case_number', 'incident_date', 'incident_time', 'incident_type',
        'location', 'complainant_name', 'complainant_address', 'complainant_contact', 'complainant_email',
        'respondent_name', 'respondent_address', 'respondent_contact', 'respondent_email', 'witnesses',
        'narrative', 'action_taken', 'status', 'remarks', 'resolved_at',
        'filed_by', 'resident_id',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'resolved_at'   => 'datetime',
    ];

    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public static function generateCaseNumber(): string
    {
        $year = date('Y');

        $lastNumber = static::where('case_number', 'like', "{$year}-%")
            ->orderByDesc('case_number')
            ->value('case_number');

        $next = $lastNumber
            ? ((int) substr($lastNumber, strlen($year) + 1)) + 1
            : 1;

        return $year . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
