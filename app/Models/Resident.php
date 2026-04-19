<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resident extends Model
{
    protected $fillable = [
        'household_id', 'first_name', 'middle_name', 'last_name',
        'date_of_birth', 'age', 'gender', 'civil_status', 'nationality',
        'relationship_to_head', 'is_head', 'contact_number', 'email',
        'employment_status', 'occupation', 'education',
        'is_4ps', 'is_senior_citizen', 'is_pwd', 'is_solo_parent',
        'is_voter', 'is_indigent', 'status',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'is_head'          => 'boolean',
        'is_4ps'           => 'boolean',
        'is_senior_citizen'=> 'boolean',
        'is_pwd'           => 'boolean',
        'is_solo_parent'   => 'boolean',
        'is_voter'         => 'boolean',
        'is_indigent'      => 'boolean',
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
        return $sectors;
    }
}
