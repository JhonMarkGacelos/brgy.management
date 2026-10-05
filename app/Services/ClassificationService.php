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
     * This is the system's only household classification; sector tags (PWD, 4Ps, …) never change it.
     */
    public const PSA_STATUSES = ['Food Poor', 'Poor', 'Low Income', 'Lower Middle', 'Middle', 'Upper Middle', 'High Income'];
    public const PSA_POOR     = ['Food Poor', 'Poor'];
    public const PSA_MIDDLE_UP = ['Middle', 'Upper Middle', 'High Income'];

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

    /**
     * Recompute and persist a household's per-capita income and Poverty Status.
     * The retired Barangay Welfare Score columns (classification, welfare_score) are left as they were.
     */
    public static function refresh(Household $household): void
    {
        $household->load('residents');
        $psa = self::psa($household);

        // Households with no income entered for anyone aren't assessed: storing ₱0 / "Food Poor"
        // for them would inflate poverty counts and pull averages down.
        $household->update($psa['assessed'] ? [
            'per_capita_income' => $psa['per_capita'],
            'psa_status'        => $psa['status'],
        ] : [
            'per_capita_income' => null,
            'psa_status'        => null,
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
    public static function psa(Household $household): array
    {
        $residents  = $household->residents;
        $t          = self::psaThresholds();
        $configured = $t['poverty'] > 0 && ($t['food'] === null || ($t['food'] > 0 && $t['food'] < $t['poverty']));
        $assessed   = self::isAssessed($household);

        // Pension (DSWD Social Pension, SSS/GSIS…) is entered separately from Monthly Income and counts as income.
        $memberCount   = max(1, $residents->count());
        $pensionIncome = (float) $residents->sum('pension_amount');
        $totalIncome   = (float) $residents->sum('monthly_income') + $pensionIncome;
        $perCapita     = round($totalIncome / $memberCount, 2);

        return [
            'configured'     => $configured,
            'assessed'       => $assessed,
            'total_income'   => $totalIncome,
            'pension_income' => $pensionIncome,
            'member_count'   => $memberCount,
            'per_capita'     => $perCapita,
            'food'           => $t['food'],
            'poverty'        => $t['poverty'],
            'source'         => $t['source'],
            'status'         => $configured && $assessed ? self::psaStatusFor($perCapita, $t['food'], $t['poverty']) : null,
            'multiple'       => $configured && $t['poverty'] > 0 ? round($perCapita / $t['poverty'], 2) : null,
            'bands'          => $configured ? self::psaBands($t['food'], $t['poverty']) : [],
        ];
    }
}
