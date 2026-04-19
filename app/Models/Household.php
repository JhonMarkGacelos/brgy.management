<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Household extends Model
{
    protected $fillable = ['house_no', 'street', 'purok'];

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function head(): HasOne
    {
        return $this->hasOne(Resident::class)->where('is_head', true);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Resident::class)->where('is_head', false);
    }

    public function getFullAddressAttribute(): string
    {
        $houseNo = (!empty($this->house_no) && strtoupper(trim($this->house_no)) !== 'N/A')
            ? 'House No.: ' . $this->house_no
            : null;

        return collect([$houseNo, $this->street, $this->purok])
            ->filter(fn($v) => !empty($v) && strtoupper(trim($v)) !== 'N/A')
            ->implode(', ');
    }
}
