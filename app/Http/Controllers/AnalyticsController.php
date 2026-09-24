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

        // Population figures are a snapshot "as of" the end of the selected month (or today):
        // everyone registered by then, with ages and senior status computed at that date.
        $asOf = $filterMonthCarbon ? $filterMonthCarbon->copy()->endOfMonth() : now();
        if ($asOf->isFuture()) {
            $asOf = now();
        }
        $asOfDate = $asOf->toDateString();
        $seniorCutoff = $asOf->copy()->subYears(Resident::SENIOR_AGE)->toDateString();
        // Senior as of $asOf: from date of birth, falling back to the stored flag when no date of birth is on file.
        $seniorCond = fn ($q) => $q->where(fn ($q) => $q
            ->where(fn ($q) => $q->whereNotNull('date_of_birth')->whereDate('date_of_birth', '<=', $seniorCutoff))
            ->orWhere(fn ($q) => $q->whereNull('date_of_birth')->where('is_senior_citizen', true)));

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
            'Social Pension'=> 'is_social_pensioner',
            'PWD'           => 'is_pwd',
            'Solo Parent'   => 'is_solo_parent',
            'Voter'         => 'is_voter',
            'Indigent'      => 'is_indigent',
            'Pregnant'      => 'is_pregnant',
        ];
        $sectorCol = $filterSector ? ($sectorColMap[$filterSector] ?? null) : null;

        // Base resident query (filtered by sector and/or month if set)
        // Only active residents are counted (same as the dashboard).
        $sectorFilter = function ($r) use ($sectorCol, $filterSector, $pregnantDueCond, $seniorCond) {
            if ($filterSector === 'Senior Citizen') {
                $seniorCond($r);
            } elseif ($sectorCol) {
                $r->where($sectorCol, true);
                if ($filterSector === 'Pregnant') {
                    $r->where($pregnantDueCond);
                }
            }
        };
        $resBase = Resident::where('residents.status', 'Active')
            ->whereDate('residents.created_at', '<=', $asOfDate);
        $sectorFilter($resBase);

        // Registered during the selected month (only shown when a month is picked).
        $newResidents = $filterMonthCarbon
            ? (clone $resBase)->whereDate('residents.created_at', '>=', $filterMonthCarbon->copy()->startOfMonth()->toDateString())->count()
            : null;

        // Population
        $totalResidents  = (clone $resBase)->count();
        $maleResidents   = (clone $resBase)->where('gender', 'Male')->count();
        $femaleResidents = (clone $resBase)->where('gender', 'Female')->count();
        $genderUnknown   = (clone $resBase)->where(fn ($q) => $q->whereNull('gender')->orWhereNotIn('gender', ['Male', 'Female']))->count();
        $seniorCitizens  = (clone $resBase)->where($seniorCond)->count();
        $socialPensioners = (clone $resBase)->where('is_social_pensioner', true)->count();
        $pwds            = (clone $resBase)->where('is_pwd', true)->count();
        // Households with at least one resident in the current population (respects the sector filter).
        $householdBase = Household::whereDate('created_at', '<=', $asOfDate)
            ->whereHas('residents', function ($r) use ($sectorFilter, $asOfDate) {
                $r->where('status', 'Active')->whereDate('created_at', '<=', $asOfDate);
                $sectorFilter($r);
            });
        $totalHouseholds = (clone $householdBase)->count();
        $newHouseholds   = $filterMonthCarbon
            ? (clone $householdBase)->whereDate('created_at', '>=', $filterMonthCarbon->copy()->startOfMonth()->toDateString())->count()
            : null;
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
        $pendingCases  = (clone $blotterBase)->whereIn('status', BlotterRecord::STATUS_PENDING)->count();
        $ongoingCases  = (clone $blotterBase)->whereIn('status', BlotterRecord::STATUS_ONGOING)->count();
        // Settled = resolved at the barangay; Referred = closed here but sent on (police, court via CFA, other agency).
        $settledCases  = (clone $blotterBase)->where('status', 'Settled')->count();
        $referredCases = (clone $blotterBase)->where('status', 'Referred')->count();
        $settlementRate = ($settledCases + $referredCases) > 0
            ? round($settledCases / ($settledCases + $referredCases) * 100, 1) : null;

        // Documents — filtered by month if set
        $docBase = DocumentRequest::query();
        if ($filterYear && $filterMonthNum) {
            $docBase->whereYear('created_at', $filterYear)
                    ->whereMonth('created_at', $filterMonthNum);
        }
        $totalDocuments    = (clone $docBase)->count();
        // "In progress" = not yet issued or rejected (includes Approved = paid, awaiting release).
        $pendingDocuments  = (clone $docBase)->whereIn('status', ['Pending', 'Pending Official', 'Approved'])->count();
        $issuedDocuments   = (clone $docBase)->where('status', 'Issued')->count();
        $rejectedDocuments = (clone $docBase)->where('status', 'Rejected')->count();

        // Document types (filtered by month)
        $documentTypes = (clone $docBase)->selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        // Revenue — "collected" = an OR number was generated (Approved or Issued)
        $revenueBase           = (clone $docBase)->whereNotNull('or_number');
        $totalRevenue          = (clone $revenueBase)->sum('fee');
        // Free documents (₱0, e.g. Certificate of Indigency) get an OR number too but aren't paid requests.
        $paidDocumentsCount    = (clone $revenueBase)->where('fee', '>', 0)->count();
        $avgRevenuePerDocument = $paidDocumentsCount > 0 ? $totalRevenue / $paidDocumentsCount : 0;
        $revenueByType         = (clone $revenueBase)
            ->selectRaw('document_type, SUM(fee) as total, SUM(CASE WHEN fee > 0 THEN 1 ELSE 0 END) as count')
            ->groupBy('document_type')
            ->get()
            ->keyBy('document_type');

        // Age groups (sector-filtered)
        $ageGroups = [
            ['label' => 'Children (0–12)',   'min' => 0,  'max' => 12],
            ['label' => 'Teenagers (13–17)', 'min' => 13, 'max' => 17],
            ['label' => 'Adults (18–59)',    'min' => 18, 'max' => 59],
            ['label' => 'Seniors (60+)',     'min' => 60, 'max' => 150],
        ];
        // Ages come from date of birth (the stored age column is only updated when a record is saved).
        $ageBetween = function ($q, int $min, int $max) use ($asOf) {
            $youngestDob = $asOf->copy()->subYears($min)->toDateString();              // turned $min by $asOf
            $oldestDob   = $asOf->copy()->subYears($max + 1)->addDay()->toDateString(); // not yet $max + 1
            $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNotNull('date_of_birth')
                    ->whereDate('date_of_birth', '>=', $oldestDob)->whereDate('date_of_birth', '<=', $youngestDob))
                ->orWhere(fn ($q) => $q->whereNull('date_of_birth')->whereBetween('age', [$min, $max])));
        };
        foreach ($ageGroups as &$range) {
            $inRange = fn () => (clone $resBase)->where(fn ($q) => $ageBetween($q, $range['min'], $range['max']));
            $range['male']        = $inRange()->where('gender', 'Male')->count();
            $range['female']      = $inRange()->where('gender', 'Female')->count();
            $range['unspecified'] = $inRange()->where(fn ($q) => $q->whereNull('gender')->orWhereNotIn('gender', ['Male', 'Female']))->count();
        }
        unset($range);
        // Residents with neither a date of birth nor an age can't be placed in any group.
        $ageUnknown = (clone $resBase)->whereNull('date_of_birth')->whereNull('age')->count();

        // Civil status (sector-filtered)
        $civilStatus = (clone $resBase)->selectRaw('civil_status, COUNT(*) as count')
            ->whereNotNull('civil_status')
            ->groupBy('civil_status')
            ->pluck('count', 'civil_status')
            ->toArray();
        if ($missing = (clone $resBase)->whereNull('civil_status')->count()) {
            $civilStatus['Not recorded'] = $missing;
        }

        // Employment status (sector-filtered)
        $employmentStatus = (clone $resBase)->selectRaw('employment_status, COUNT(*) as count')
            ->whereNotNull('employment_status')
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status')
            ->toArray();
        if ($missing = (clone $resBase)->whereNull('employment_status')->count()) {
            $employmentStatus['Not recorded'] = $missing;
        }

        // Monthly trend charts — always last 12 months, unfiltered (show full picture)
        $monthlyCases     = [];
        $monthlyDocuments = [];
        $monthlyRevenue   = [];
        $monthLabels      = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $monthlyCases[]     = BlotterRecord::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
            $monthlyDocuments[] = DocumentRequest::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count();
            $monthlyRevenue[]   = (float) DocumentRequest::whereNotNull('or_number')
                ->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)
                ->sum('fee');
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

        // Welfare classification — filtered by month when a month filter is active
        $welfareBase = clone $householdBase;
        $tierOrder = ['Extremely Poor', 'Poor', 'Near Poor', 'Vulnerable', 'Non-Poor'];
        $rawCounts = (clone $welfareBase)->whereNotNull('classification')
            ->selectRaw('classification, count(*) as total')
            ->groupBy('classification')
            ->pluck('total', 'classification')
            ->toArray();
        $classificationCounts = [];
        foreach ($tierOrder as $tier) {
            $classificationCounts[$tier] = $rawCounts[$tier] ?? 0;
        }
        $householdsClassified = array_sum($classificationCounts);
        $avgPerCapita         = (clone $welfareBase)->whereNotNull('per_capita_income')->avg('per_capita_income') ?? 0;
        $belowPovertyLine     = ($classificationCounts['Extremely Poor'] ?? 0) + ($classificationCounts['Poor'] ?? 0);

        // PSA Poverty Status (income-only, PSA thresholds + PIDS classes) — separate from the barangay score above
        $psaRaw = (clone $welfareBase)->whereNotNull('psa_status')
            ->selectRaw('psa_status, count(*) as total')
            ->groupBy('psa_status')
            ->pluck('total', 'psa_status')
            ->toArray();
        $psaThresholds = \App\Services\ClassificationService::psaThresholds();
        $psaCounts = [];
        foreach (\App\Services\ClassificationService::PSA_STATUSES as $status) {
            // Food Poor is only a separate class when a food threshold has been entered.
            if ($status === 'Food Poor' && !$psaThresholds['food'] && empty($psaRaw['Food Poor'])) {
                continue;
            }
            $psaCounts[$status] = $psaRaw[$status] ?? 0;
        }
        $psaAssessed  = array_sum($psaCounts);
        $psaBelowLine = ($psaCounts['Food Poor'] ?? 0) + ($psaCounts['Poor'] ?? 0);

        // PSA reports incidence two ways: among families (households here) and among population (people in them).
        $psaPeopleAssessed = (int) (clone $welfareBase)->whereNotNull('psa_status')->withCount('residents')->get()->sum('residents_count');
        $psaPeopleBelow    = (int) (clone $welfareBase)->whereIn('psa_status', \App\Services\ClassificationService::PSA_POOR)
            ->withCount('residents')->get()->sum('residents_count');
        $psaFamilyIncidence     = $psaAssessed > 0 ? round($psaBelowLine / $psaAssessed * 100, 1) : 0;
        $psaPopulationIncidence = $psaPeopleAssessed > 0 ? round($psaPeopleBelow / $psaPeopleAssessed * 100, 1) : 0;

        // Month options for filter dropdown (last 24 months)
        $availableMonths = [];
        for ($i = 0; $i <= 23; $i++) {
            $m = now()->startOfMonth()->subMonthsNoOverflow($i);
            $availableMonths[$m->format('Y-m')] = $m->format('F Y');
        }

        return view('analytics.index', compact(
            'totalResidents', 'maleResidents', 'femaleResidents', 'genderUnknown', 'ageUnknown',
            'seniorCitizens', 'socialPensioners', 'pwds', 'totalHouseholds',
            'voters', 'soloParents', 'fourPs', 'indigent', 'pregnant',
            'totalCases', 'pendingCases', 'ongoingCases', 'settledCases', 'referredCases', 'settlementRate',
            'totalDocuments', 'pendingDocuments', 'issuedDocuments', 'rejectedDocuments',
            'totalRevenue', 'paidDocumentsCount', 'avgRevenuePerDocument', 'revenueByType',
            'ageGroups', 'civilStatus', 'employmentStatus',
            'monthlyCases', 'monthlyDocuments', 'monthlyRevenue', 'monthLabels', 'documentTypes', 'populationByPurok',
            'classificationCounts', 'householdsClassified', 'avgPerCapita', 'belowPovertyLine',
            'psaCounts', 'psaAssessed', 'psaBelowLine', 'psaThresholds',
            'psaPeopleAssessed', 'psaPeopleBelow', 'psaFamilyIncidence', 'psaPopulationIncidence',
            'filterMonth', 'filterSector', 'availableMonths', 'asOf', 'newResidents', 'newHouseholds'
        ));
    }
}
