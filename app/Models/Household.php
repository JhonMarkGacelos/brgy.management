<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\IncomeSource;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Household extends Model
{
    use LogsActivity;

    protected $fillable = ['house_no', 'street', 'purok', 'classification', 'welfare_score', 'per_capita_income', 'psa_status'];

    protected $casts = [
        'welfare_score'    => 'decimal:2',
        'per_capita_income'=> 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        // welfare_score / per_capita_income are excluded: they're auto-recomputed
        // derived numbers that shift on nearly every related edit, so logging them
        // would be noise rather than a meaningful change.
        return LogOptions::defaults()
            ->logOnly(['house_no', 'street', 'purok', 'classification'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('household');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $address = $this->full_address ?: "Household #{$this->id}";
        return match($eventName) {
            'created' => "Household at {$address} registered",
            'updated' => "Household at {$address} updated",
            'deleted' => "Household at {$address} deleted",
            default   => "Household at {$address} {$eventName}",
        };
    }

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

    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class);
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

    /** PSA classes above the poverty line: a poverty-program tag on these households contradicts the recorded income. */
    public const NOT_POOR_PSA = ['Lower Middle', 'Middle', 'Upper Middle', 'High Income'];

    /**
     * Advisory warnings for records that contradict each other (never blocks saving).
     * Kept in step with scopeNeedsReview().
     */
    public function reviewFlags(): array
    {
        $flags = [];
        $residents = $this->residents;

        $tags = collect([
            '4Ps'            => $residents->contains('is_4ps', true),
            'Indigent'       => $residents->contains('is_indigent', true),
            'Social Pension' => $residents->contains('is_social_pensioner', true),
        ])->filter()->keys();
        if ($tags->isNotEmpty() && in_array($this->psa_status, self::NOT_POOR_PSA, true)) {
            $flags[] = "Tagged {$tags->implode(', ')}, but the recorded income puts this household at \"{$this->psa_status}\" "
                . '(above the PSA poverty line). Check the monthly income or the tags.';
        }

        foreach ($residents->filter(fn ($r) => !in_array($r->gender, ['Male', 'Female'], true)) as $r) {
            $flags[] = "{$r->full_name} has no gender recorded.";
        }

        return $flags;
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $q) => $q
                ->whereIn('psa_status', self::NOT_POOR_PSA)
                ->whereHas('residents', fn (Builder $r) => $r->where(fn ($r) => $r
                    ->where('is_4ps', true)->orWhere('is_indigent', true)->orWhere('is_social_pensioner', true))))
            ->orWhereHas('residents', fn (Builder $r) => $r->where(fn ($r) => $r
                ->whereNull('gender')->orWhereNotIn('gender', ['Male', 'Female']))));
    }
}
