<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Resident extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'middle_name', 'last_name', 'status', 'email', 'contact_number'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resident');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $name = "{$this->first_name} {$this->last_name}";
        return match($eventName) {
            'created' => "Resident {$name} added",
            'updated' => "Resident {$name} updated",
            'deleted' => "Resident {$name} deleted",
            default   => "Resident {$name} {$eventName}",
        };
    }

    protected $fillable = [
        'household_id', 'first_name', 'middle_name', 'last_name',
        'date_of_birth', 'age', 'gender', 'civil_status', 'nationality',
        'relationship_to_head', 'is_head', 'contact_number', 'email',
        'employment_status', 'monthly_income', 'occupation', 'education',
        'is_4ps', 'is_senior_citizen', 'is_pwd', 'is_solo_parent',
        'is_voter', 'is_indigent', 'is_pregnant', 'pregnant_due_date', 'status',
        'pwd_id_url', 'pwd_id_public_id', 'solo_parent_id_url', 'solo_parent_id_public_id',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'is_head'          => 'boolean',
        'monthly_income'   => 'decimal:2',
        'is_4ps'           => 'boolean',
        'is_senior_citizen'=> 'boolean',
        'is_pwd'           => 'boolean',
        'is_solo_parent'   => 'boolean',
        'is_voter'           => 'boolean',
        'is_indigent'        => 'boolean',
        'is_pregnant'        => 'boolean',
        'pregnant_due_date'  => 'date',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getSectorsAttribute(): array
    {
        $sectors = [];
        if ($this->is_4ps)           $sectors[] = '4Ps';
        if ($this->is_senior_citizen) $sectors[] = 'Senior';
        if ($this->is_pwd)            $sectors[] = 'PWD';
        if ($this->is_solo_parent)    $sectors[] = 'Solo Parent';
        if ($this->is_voter)          $sectors[] = 'Voter';
        if ($this->is_pregnant && (!$this->pregnant_due_date || $this->pregnant_due_date->gte(now()->startOfDay()))) {
            $sectors[] = 'Pregnant';
        }
        return $sectors;
    }
}
