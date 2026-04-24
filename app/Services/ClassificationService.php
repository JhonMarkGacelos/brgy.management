<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Setting;

class ClassificationService
{
    public static function classify(Household $household): array
    {
        $residents    = $household->residents;
        $memberCount  = max(1, $residents->count());

        $totalIncome = (float) $residents->sum('monthly_income');

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
            'total_income'  => $totalIncome,
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
        ];
    }
}
