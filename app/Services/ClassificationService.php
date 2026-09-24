<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Setting;

class ClassificationService
{
    /**
     * PSA Poverty Status: per capita income against the PSA poverty threshold (and food threshold, if set), with the
     * classes above the poverty line following the PIDS income classes (multiples of the poverty line;
     * Albert, Santos & Vizmanos, 2018 — 12–20x and 20x+ merged into High Income).
     * Kept separate from the Barangay Welfare Score below, which is the barangay's own scoring.
     */
    public const PSA_STATUSES = ['Food Poor', 'Poor', 'Low Income', 'Lower Middle', 'Middle', 'Upper Middle', 'High Income'];
    public const PSA_POOR     = ['Food Poor', 'Poor'];

    /** Lower bound of each class above the poverty line, as a multiple of the poverty line. */
    private const PIDS_MULTIPLES = ['Low Income' => 1, 'Lower Middle' => 2, 'Middle' => 4, 'Upper Middle' => 7, 'High Income' => 12];

    public const PSA_STYLES = [
        'Food Poor'    => 'bg-red-50 text-red-700 ring-1 ring-red-200',
        'Poor'         => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
        'Low Income'   => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
        'Lower Middle' => 'bg-lime-50 text-lime-700 ring-1 ring-lime-200',
        'Middle'       => 'bg-green-50 text-green-700 ring-1 ring-green-200',
        'Upper Middle' => 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',
        'High Income'  => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    ];

    /** Recompute and persist a household's classification, welfare score, PSA status and per-capita income. */
    public static function refresh(Household $household): void
    {
        $household->load(['residents', 'incomeSources']);
        $result = self::classify($household);

        // Households with no income entered for anyone aren't assessed: storing ₱0 / "Extremely Poor"
        // for them would inflate poverty counts and pull averages down.
        $household->update($result['assessed'] ? [
            'classification'   => $result['classification'],
            'welfare_score'    => $result['final_score'],
            'per_capita_income'=> $result['per_capita'],
            'psa_status'       => $result['psa']['status'],
        ] : [
            'classification'   => null,
            'welfare_score'    => null,
            'per_capita_income'=> null,
            'psa_status'       => null,
        ]);
    }

    /**
     * Defaults until the barangay enters its own figures in Settings — PSA RSSO VIII, 2023 full-year poverty
     * statistics, Samar, per month for a family of five ÷ 5 for per capita:
     * poverty ₱12,100 → ₱2,420; food ₱8,320 → ₱1,664.
     */
    public const DEFAULT_PSA_POVERTY = 2420.00;
    public const DEFAULT_PSA_FOOD    = 1664.00;
    public const DEFAULT_PSA_SOURCE  = 'PSA RSSO VIII, 2023 Full Year Poverty Statistics — Samar (poverty ₱12,100 / food ₱8,320 per month for a family of five)';

    /** PSA thresholds (monthly, per capita) from Settings, falling back to the Samar defaults above. */
    public static function psaThresholds(): array
    {
        $food    = Setting::get('psa_food_threshold');
        $poverty = Setting::get('psa_poverty_threshold');
        $source  = Setting::get('psa_threshold_source');

        return [
            'food'       => $food !== null && $food !== '' ? (float) $food : self::DEFAULT_PSA_FOOD,
            'poverty'    => $poverty !== null && $poverty !== '' ? (float) $poverty : self::DEFAULT_PSA_POVERTY,
            'source'     => $source !== null && $source !== '' ? (string) $source : self::DEFAULT_PSA_SOURCE,
            'is_default' => ($poverty === null || $poverty === ''),
        ];
    }

    /** Class ranges in pesos for the given thresholds: [['status', 'min', 'max' (exclusive, null = no cap)], ...]. */
    public static function psaBands(?float $food, float $poverty): array
    {
        $bands = $food
            ? [
                ['status' => 'Food Poor', 'min' => 0,     'max' => $food],
                ['status' => 'Poor',      'min' => $food, 'max' => $poverty],
            ]
            : [['status' => 'Poor', 'min' => 0, 'max' => $poverty]];
        $multiples = array_values(self::PIDS_MULTIPLES);
        foreach (array_keys(self::PIDS_MULTIPLES) as $i => $status) {
            $bands[] = [
                'status' => $status,
                'min'    => $multiples[$i] * $poverty,
                'max'    => isset($multiples[$i + 1]) ? $multiples[$i + 1] * $poverty : null,
            ];
        }

        return $bands;
    }

    public static function psaStatusFor(float $perCapita, ?float $food, float $poverty): string
    {
        foreach (self::psaBands($food, $poverty) as $band) {
            if ($band['max'] === null || $perCapita < $band['max']) {
                return $band['status'];
            }
        }

        return 'High Income';
    }

    /** Assessed = at least one member has Monthly Income or a pension entered (0 is a real, assessable value). */
    public static function isAssessed(Household $household): bool
    {
        return $household->residents->contains(fn ($r) => $r->monthly_income !== null || $r->pension_amount !== null);
    }

    /** PSA Poverty Status for a household; 'status' is null when not configured or not assessed. */
    public static function psa(Household $household, ?float $perCapita = null): array
    {
        $residents  = $household->residents;
        $t          = self::psaThresholds();
        $configured = $t['poverty'] > 0 && ($t['food'] === null || ($t['food'] > 0 && $t['food'] < $t['poverty']));
        $assessed   = self::isAssessed($household);

        if ($perCapita === null) {
            $total     = (float) $residents->sum('monthly_income') + (float) $residents->sum('pension_amount');
            $perCapita = round($total / max(1, $residents->count()), 2);
        }

        return [
            'configured' => $configured,
            'assessed'   => $assessed,
            'per_capita' => $perCapita,
            'food'       => $t['food'],
            'poverty'    => $t['poverty'],
            'source'     => $t['source'],
            'status'     => $configured && $assessed ? self::psaStatusFor($perCapita, $t['food'], $t['poverty']) : null,
            'multiple'   => $configured && $t['poverty'] > 0 ? round($perCapita / $t['poverty'], 2) : null,
            'bands'      => $configured ? self::psaBands($t['food'], $t['poverty']) : [],
        ];
    }

    public static function classify(Household $household): array
    {
        $residents    = $household->residents;
        $memberCount  = max(1, $residents->count());

        // Pension (DSWD Social Pension, SSS/GSIS…) is entered separately from Monthly Income and counts as income.
        $pensionIncome = (float) $residents->sum('pension_amount');
        $totalIncome   = (float) $residents->sum('monthly_income') + $pensionIncome;

        $perCapita    = round($totalIncome / $memberCount, 2);

        // --- Thresholds from settings ---
        // PSA 2021 Region VIII (Eastern Visayas / Samar) per capita monthly thresholds:
        // Food poverty line ≈ ₱1,383 | Total poverty line ≈ ₱1,992
        $t = [
            'extremely_poor' => (float) Setting::get('per_capita_extremely_poor', 1383),
            'poor'           => (float) Setting::get('per_capita_poor',            1992),
            'near_poor'      => (float) Setting::get('per_capita_near_poor',       3500),
            'vulnerable'     => (float) Setting::get('per_capita_vulnerable',      6000),
        ];

        // --- Base score (0–100) mapped linearly across 5 bands ---
        if ($perCapita <= $t['extremely_poor']) {
            $base = $t['extremely_poor'] > 0 ? ($perCapita / $t['extremely_poor']) * 20 : 0;
        } elseif ($perCapita <= $t['poor']) {
            $base = 20 + (($perCapita - $t['extremely_poor']) / ($t['poor'] - $t['extremely_poor'])) * 20;
        } elseif ($perCapita <= $t['near_poor']) {
            $base = 40 + (($perCapita - $t['poor']) / ($t['near_poor'] - $t['poor'])) * 20;
        } elseif ($perCapita <= $t['vulnerable']) {
            $base = 60 + (($perCapita - $t['near_poor']) / ($t['vulnerable'] - $t['near_poor'])) * 20;
        } else {
            $base = 80 + min(20, (($perCapita - $t['vulnerable']) / $t['vulnerable']) * 20);
        }

        // --- Situation modifiers (each deducts from score) ---
        $pwdCount    = $residents->where('is_pwd', true)->count();
        $seniorCount = $residents->where('is_senior_citizen', true)->count();
        $soloParent  = $residents->where('is_solo_parent', true)->count();
        $fourPs      = $residents->where('is_4ps', true)->count() > 0 ? 1 : 0;
        $indigent    = $residents->where('is_indigent', true)->count() > 0 ? 1 : 0;

        $modifiers = [
            ['label' => 'PWD Members',         'count' => $pwdCount,    'per_unit' => -5,  'total' => $pwdCount    * -5],
            ['label' => 'Senior Citizens',      'count' => $seniorCount, 'per_unit' => -3,  'total' => $seniorCount * -3],
            ['label' => 'Solo Parents',         'count' => $soloParent,  'per_unit' => -5,  'total' => $soloParent  * -5],
            ['label' => '4Ps Beneficiary',      'count' => $fourPs,      'per_unit' => -8,  'total' => $fourPs      * -8],
            ['label' => 'Indigent Household',   'count' => $indigent,    'per_unit' => -6,  'total' => $indigent    * -6],
        ];

        $totalModifier = (float) array_sum(array_column($modifiers, 'total'));
        $finalScore    = max(0.0, min(100.0, round($base + $totalModifier, 2)));

        // --- Classification ---
        [$label, $color, $bg] = match(true) {
            $finalScore <= 20 => ['Extremely Poor', 'text-red-700',    'bg-red-50    border-red-200'],
            $finalScore <= 40 => ['Poor',           'text-orange-700', 'bg-orange-50 border-orange-200'],
            $finalScore <= 60 => ['Near Poor',      'text-yellow-700', 'bg-yellow-50 border-yellow-200'],
            $finalScore <= 80 => ['Vulnerable',     'text-blue-700',   'bg-blue-50   border-blue-200'],
            default           => ['Non-Poor',       'text-green-700',  'bg-green-50  border-green-200'],
        };

        return [
            'assessed'      => self::isAssessed($household),
            'total_income'  => $totalIncome,
            'pension_income'=> $pensionIncome,
            'member_count'  => $memberCount,
            'per_capita'    => $perCapita,
            'thresholds'    => $t,
            'base_score'    => round($base, 2),
            'modifiers'     => $modifiers,
            'total_modifier'=> $totalModifier,
            'final_score'   => $finalScore,
            'classification'=> $label,
            'color'         => $color,
            'bg'            => $bg,
            'psa'           => self::psa($household, $perCapita),
        ];
    }
}
