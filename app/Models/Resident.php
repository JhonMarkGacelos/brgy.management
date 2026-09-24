<?php

namespace App\Models;

use App\Services\ClassificationService;
use Illuminate\Database\Eloquent\Builder;
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
        'fourps_id_url', 'fourps_id_public_id',
        'is_social_pensioner', 'has_other_pension', 'pension_amount',
        'senior_id_url', 'senior_id_public_id',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'is_head'          => 'boolean',
        'monthly_income'   => 'decimal:2',
        'is_4ps'           => 'boolean',
        'is_senior_citizen'=> 'boolean',
        'is_social_pensioner' => 'boolean',
        'has_other_pension'   => 'boolean',
        'pension_amount'      => 'decimal:2',
        'is_pwd'           => 'boolean',
        'is_solo_parent'   => 'boolean',
        'is_voter'           => 'boolean',
        'is_indigent'        => 'boolean',
        'is_pregnant'        => 'boolean',
        'pregnant_due_date'  => 'date',
    ];

    public const SENIOR_AGE = 60;

    protected static function booted(): void
    {
        // Senior Citizen status (RA 9994: 60+) is derived from date of birth, never set by hand.
        static::saving(function (Resident $resident) {
            if ($resident->date_of_birth) {
                $resident->age               = $resident->date_of_birth->age;
                $resident->is_senior_citizen = $resident->age >= self::SENIOR_AGE;
            }

            // Pension flags only apply to seniors (e.g. cleared after a date-of-birth correction).
            if (!$resident->is_senior_citizen) {
                $resident->is_social_pensioner = false;
                $resident->has_other_pension   = false;
            }
            if (!$resident->is_social_pensioner && !$resident->has_other_pension) {
                $resident->pension_amount = null;
            }
        });
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * Seniors who likely qualify for the DSWD Social Pension but aren't receiving it: no pension of any kind,
     * and the household is below the PSA poverty line (Food Poor / Poor). The Indigent tag only counts when
     * no income has been entered for the household (so a tag can't outweigh recorded income).
     */
    public function scopeSocialPensionCandidates(Builder $query): Builder
    {
        return $query->where('is_senior_citizen', true)
            ->where('is_social_pensioner', false)
            ->where('has_other_pension', false)
            ->where(fn (Builder $q) => $q
                ->whereHas('household', fn (Builder $h) => $h->whereIn('psa_status', ClassificationService::PSA_POOR))
                ->orWhere(fn (Builder $q) => $q
                    ->where('is_indigent', true)
                    ->whereHas('household', fn (Builder $h) => $h->whereNull('psa_status'))));
    }

    /** Pregnant and not past the due date (the flag itself isn't cleared automatically after delivery). */
    public function scopeCurrentlyPregnant(Builder $query): Builder
    {
        return $query->where('is_pregnant', true)
            ->where(fn (Builder $q) => $q->whereNull('pregnant_due_date')
                ->orWhereDate('pregnant_due_date', '>=', now()->toDateString()));
    }

    public function getIsSocialPensionCandidateAttribute(): bool
    {
        return $this->is_senior_citizen
            && !$this->is_social_pensioner
            && !$this->has_other_pension
            && (in_array($this->household?->psa_status, ClassificationService::PSA_POOR, true)
                || ($this->is_indigent && $this->household && $this->household->psa_status === null));
    }

    public const PENSION_OPTIONS = ['none', 'social', 'other'];

    /** Map the form's single Pension choice (none / social / other) and amount to the pension columns. */
    public static function pensionFields(?string $pension, mixed $amount): array
    {
        $hasPension = in_array($pension, ['social', 'other'], true);

        return [
            'is_social_pensioner' => $pension === 'social',
            'has_other_pension'   => $pension === 'other',
            'pension_amount'      => $hasPension && $amount !== null && $amount !== '' ? $amount : null,
        ];
    }

    public function getPensionAttribute(): string
    {
        return match (true) {
            $this->is_social_pensioner => 'social',
            $this->has_other_pension   => 'other',
            default                    => 'none',
        };
    }

    /** Age is always current: computed from date of birth; the stored column is only a fallback when it's missing. */
    public function getAgeAttribute($value): ?int
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age;
        }

        return $value !== null ? (int) $value : null;
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
        if ($this->is_social_pensioner) $sectors[] = 'Social Pension';
        if ($this->is_pwd)            $sectors[] = 'PWD';
        if ($this->is_solo_parent)    $sectors[] = 'Solo Parent';
        if ($this->is_voter)          $sectors[] = 'Voter';
        if ($this->is_indigent)       $sectors[] = 'Indigent';
        if ($this->is_pregnant && (!$this->pregnant_due_date || $this->pregnant_due_date->gte(now()->startOfDay()))) {
            $sectors[] = 'Pregnant';
        }
        return $sectors;
    }
}
