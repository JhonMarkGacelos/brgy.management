<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Household;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Population
        $totalResidents  = Resident::count();
        $maleResidents   = Resident::where('gender', 'Male')->count();
        $femaleResidents = Resident::where('gender', 'Female')->count();
        $seniorCitizens  = Resident::where('is_senior_citizen', true)->count();
        $pwds            = Resident::where('is_pwd', true)->count();
        $totalHouseholds = Household::count();
        $voters          = Resident::where('is_voter', true)->count();
        $soloParents     = Resident::where('is_solo_parent', true)->count();
        $fourPs          = Resident::where('is_4ps', true)->count();
        $indigent        = Resident::where('is_indigent', true)->count();

        // Blotter
        $totalCases    = BlotterRecord::count();
        $pendingCases  = BlotterRecord::where('status', 'Pending')->count();
        $resolvedCases = BlotterRecord::where('status', 'Resolved')->count();
        $ongoingCases  = BlotterRecord::where('status', 'Ongoing')->count();

        // Documents
        $totalDocuments   = DocumentRequest::count();
        $pendingDocuments = DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])->count();
        $issuedDocuments  = DocumentRequest::where('status', 'Issued')->count();
        $rejectedDocuments = DocumentRequest::where('status', 'Rejected')->count();

        // Age groups (using stored age field)
        $ageGroups = [
            ['label' => 'Children (0–12)',   'min' => 0,  'max' => 12],
            ['label' => 'Teenagers (13–17)', 'min' => 13, 'max' => 17],
            ['label' => 'Adults (18–59)',    'min' => 18, 'max' => 59],
            ['label' => 'Seniors (60+)',     'min' => 60, 'max' => 150],
        ];
        foreach ($ageGroups as &$range) {
            $range['male']   = Resident::where('gender', 'Male')
                ->whereBetween('age', [$range['min'], $range['max']])->count();
            $range['female'] = Resident::where('gender', 'Female')
                ->whereBetween('age', [$range['min'], $range['max']])->count();
        }
        unset($range);

        // Civil status breakdown
        $civilStatus = Resident::selectRaw('civil_status, COUNT(*) as count')
            ->whereNotNull('civil_status')
            ->groupBy('civil_status')
            ->pluck('count', 'civil_status')
            ->toArray();

        // Employment status breakdown
        $employmentStatus = Resident::selectRaw('employment_status, COUNT(*) as count')
            ->whereNotNull('employment_status')
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status')
            ->toArray();

        // Monthly blotter cases (last 12 months)
        $monthlyCases = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyCases[] = BlotterRecord::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)->count();
        }

        // Monthly documents issued (last 12 months)
        $monthlyDocuments = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyDocuments[] = DocumentRequest::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)->count();
        }

        // Document types breakdown
        $documentTypes = DocumentRequest::selectRaw('document_type, COUNT(*) as count')
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        // Residents by Purok (via household)
        $populationByPurok = Resident::join('households', 'residents.household_id', '=', 'households.id')
            ->selectRaw('households.purok, COUNT(*) as count')
            ->groupBy('households.purok')
            ->orderBy('households.purok')
            ->pluck('count', 'households.purok')
            ->toArray();

        // Welfare classification
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

        return view('analytics.index', compact(
            'totalResidents', 'maleResidents', 'femaleResidents',
            'seniorCitizens', 'pwds', 'totalHouseholds',
            'voters', 'soloParents', 'fourPs', 'indigent',
            'totalCases', 'pendingCases', 'resolvedCases', 'ongoingCases',
            'totalDocuments', 'pendingDocuments', 'issuedDocuments', 'rejectedDocuments',
            'ageGroups', 'civilStatus', 'employmentStatus',
            'monthlyCases', 'monthlyDocuments', 'documentTypes', 'populationByPurok',
            'classificationCounts', 'householdsClassified', 'avgPerCapita', 'belowPovertyLine'
        ));
    }
}
