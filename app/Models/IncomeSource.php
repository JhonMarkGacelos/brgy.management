<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeSource extends Model
{
    protected $fillable = ['household_id', 'type', 'description', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public static function types(): array
    {
        return ['Employment', 'Business', 'Remittance', 'Pension', '4Ps', 'Others'];
    }
}
