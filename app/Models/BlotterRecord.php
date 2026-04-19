<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlotterRecord extends Model
{
    protected $fillable = [
        'case_number', 'incident_date', 'incident_time', 'incident_type',
        'location', 'complainant_name', 'complainant_address', 'complainant_contact',
        'respondent_name', 'respondent_address', 'respondent_contact', 'witnesses',
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
        $year  = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
