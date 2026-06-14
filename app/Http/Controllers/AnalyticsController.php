<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Household;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filterMonth  = $request->month;   // "2026-06"
        $filterSector = $request->sector;  // "4Ps", "Pregnant", etc.

        $filterYear     = $filterMonth ? (int) substr($filterMonth, 0, 4) : null;
        $filterMonthNum = $filterMonth ? (int) substr($filterMonth, 5, 2) : null;

        // Carbon instance for the filtered month (used for pregnancy window logic)
        $filterMonthCarbon = ($filterYear && $filterMonthNum)
            ? \Carbon\Carbon::createFromDate($filterYear, $filterMonthNum, 1)
            : null;

        // Pregnancy window condition:
        //   No month filter  → due_date IS NULL or due_date >= today
        //   Month filter M   → due_date IS NULL
        //                      OR (due_date >= M-start  AND  due_date <= M-end + 9 months)
        // The second branch means the filtered month falls inside the ~9-month pregnancy period.
        // We compute the cutoff in PHP (M.end + 9 months) to avoid raw SQL date functions.
        $pregnantDueCond = function ($q) use ($filterMonthCarbon) {
            $q->whereNull('pregnant_due_date');
            if ($filterMonthCarbon) {
                $mStart  = $filterMonthCarbon->copy()->startOfMonth()->toDateString();
                $mCutoff = $filterMonthCarbon->copy()->endOfMonth()->addMonths(9)->toDateString();
                $q->orWhere(function ($q2) use ($mStart, $mCutoff) {
                    $q2->where('pregnant_due_date', '>=', $mStart)
                       ->where('pregnant_due_date', '<=', $mCutoff);
                });
            } else {
                $q->orWhere('pregnant_due_date', '>=', now()->toDateString());
            }
        };

        // Sector column map
        $sectorColMap = [
            '4Ps'           => 'is_4ps',
            'Senior Citizen'=> 'is_senior_citizen',
            'PWD'           => 'is_pwd',
            'Solo Parent'   => 'is_solo_parent',
            'Voter'         => 'is_voter',
            'Indigent'      => 'is_indigent',
            'Pregnant'      => 'is_pregnant',
        ];
        $sectorCol = $filterSector ? ($sectorColMap[$filterSector] ?? null) : null;

        // Base resident query (filtered by sector if set)
        $resBase = Resident::query();
        if ($sectorCol) {
            $resBase->where($sectorCol, true);
            if ($filterSector === 'Pregnant') {
                $resBase->where($pregnantDueCond);
            }
        }

        // Population
        $totalResidents  = (clone $resBase)->count();
        $maleResidents   = (clone $resBase)->where('gender', 'Male')->count();
        $femaleResidents = (clone $resBase)->where('gender', 'Female')->count();
        $seniorCitizens  = (clone $resBase)->where('is_senior_citizen', true)->count();
        $pwds            = (clone $resBase)->where('is_pwd', true)->count();
        $totalHouseholds = Household::count();
        $voters          = (clone $resBase)->where('is_voter', true)->count();
        $soloParents     = (clone $resBase)->where('is_solo_parent', true)->count();
        $fourPs          = (clone $resBase)->where('is_4ps', true)->count();
        $indigent        = (clone $resBase)->where('is_indigent', true)->count();
        $pregnant        = (clone $resBase)->where('is_pregnant', true)
            ->where($pregnantDueCond)
            ->count();

        // Blotter — filtered by month if set
        $blotterBase = BlotterRecord::query();
        if ($filterYear && $filterMonthNum) {
            $blotterBase->whereYear('created_at', $filterYear)
                        ->whereMonth('created_at', $filterMonthNum);
        }
        $totalCases    = (clone $blotterBase)->count();
        $pendingCases  = (clone $blotterBase)->where('status', 'Pending')->count();
        $resolvedCases = (clone $blotterBase)->where('status', 'Resolved')->count();
        $ongoingCases  = (clone $blotterBase)->where('status', 'Ongoing')->count();

        // Documents — filtered by month if set
        $docBase = DocumentRequest::query();
        if ($filterYear && $filterMonthNum) {
            $docBase->whereYear('created_at', $filterYear)
                    ->whereMonth('created_at', $filterMonthNum);
        }
        $totalDocuments    = (clone $docBase)->count();
        $pendingDocuments  = (clone $docBase)->whereIn('status', ['Pending', 'Pending Official'])->count();
        $issuedDocuments   = (clone $docBase)->where('status', 'Issued')->count();
        $rejectedDocuments = (clone $docBase)->where('status', 'Rejected')->count();

        // Document types (filtered by month)
        $documentTypes = (clone $docBase)->selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        // Age groups (sector-filtered)
        $ageGroups = [
            ['label' => 'Children (0–12)',   'min' => 0,  'max' => 12],
            ['label' => 'Teenagers (13–17)', 'min' => 13, 'max' => 17],
            ['label' => 'Adults (18–59)',    'min' => 18, 'max' => 59],
            ['label' => 'Seniors (60+)',     'min' => 60, 'max' => 150],
        ];
        foreach ($ageGroups as &$range) {
            $range['male']   = (clone $resBase)->where('gender', 'Male')
                ->whereBetween('age', [$range['min'], $range['max']])->count();
            $range['female'] = (clone $resBase)->where('gender', 'Female')
                ->whereBetween('age', [$range['min'], $range['max']])->count();
        }
        unset($range);

        // Civil status (sector-filtered)
        $civilStatus = (clone $resBase)->selectRaw('civil_status, COUNT(*) as count')
            ->whereNotNull('civil_status')
            ->groupBy('civil_status')
            ->pluck('count', 'civil_status')
            ->toArray();

        // Employment status (sector-filtered)
        $employmentStatus = (clone $resBase)->selectRaw('employment_status, COUNT(*) as count')
            ->whereNotNull('employment_status')
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status')
            ->toArray();

        // Monthly trend charts — always last 12 months, unfiltered (show full picture)
        $monthlyCases     = [];
        $monthlyDocuments = [];
        $monthLabels      = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthlyCases[]     = BlotterRecord::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
            $monthlyDocuments[] = DocumentRequest::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
            $monthLabels[]      = $m->format('M Y');  // e.g. "Jul 2025"
        }

        // Population by Purok (sector-filtered)
        $populationByPurok = (clone $resBase)
            ->join('households', 'residents.household_id', '=', 'households.id')
            ->selectRaw('households.purok, COUNT(*) as count')
            ->groupBy('households.purok')
            ->orderBy('households.purok')
            ->pluck('count', 'households.purok')
            ->toArray();

        // Welfare classification (not filtered — always shows all households)
        $tierOrder = ['Extremely Poor', 'Poor', 'Near Poor', 'Vulnerable', 'Non-Poor'];
        $rawCounts = Household::whereNotNull('classification')
            ->selectRaw('classification, count(*) as total')
            ->groupBy('classification')
            ->pluck('total', 'classification')
            ->toArray();
        $classificationCounts = [];
        foreach ($tierOrder as $tier) {
            $classificationCounts[$tier] = $rawCounts[$tier] ?? 0;
        }
        $householdsClassified = array_sum($classificationCounts);
        $avgPerCapita         = Household::whereNotNull('per_capita_income')->avg('per_capita_income') ?? 0;
        $belowPovertyLine     = ($classificationCounts['Extremely Poor'] ?? 0) + ($classificationCounts['Poor'] ?? 0);

        // Month options for filter dropdown (last 24 months)
        $availableMonths = [];
        for ($i = 0; $i <= 23; $i++) {
            $m = now()->subMonths($i);
            $availableMonths[$m->format('Y-m')] = $m->format('F Y');
        }

        return view('analytics.index', compact(
            'totalResidents', 'maleResidents', 'femaleResidents',
            'seniorCitizens', 'pwds', 'totalHouseholds',
            'voters', 'soloParents', 'fourPs', 'indigent', 'pregnant',
            'totalCases', 'pendingCases', 'resolvedCases', 'ongoingCases',
            'totalDocuments', 'pendingDocuments', 'issuedDocuments', 'rejectedDocuments',
            'ageGroups', 'civilStatus', 'employmentStatus',
            'monthlyCases', 'monthlyDocuments', 'monthLabels', 'documentTypes', 'populationByPurok',
            'classificationCounts', 'householdsClassified', 'avgPerCapita', 'belowPovertyLine',
            'filterMonth', 'filterSector', 'availableMonths'
        ));
    }
}
