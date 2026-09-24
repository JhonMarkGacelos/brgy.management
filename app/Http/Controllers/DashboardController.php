<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function adminDashboard()
    {
        // Summary statistics
        $totalResidents = Resident::where('status', 'Active')->count();
        $thisMonthResidents = Resident::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        // Awaiting action = not yet issued or rejected (same as Analytics "In Progress").
        $pendingDocuments = DocumentRequest::whereIn('status', ['Pending', 'Pending Official', 'Approved'])->count();
        $resolvedCases = BlotterRecord::where('status', 'Settled')->count(); // amicably settled; referrals aren't resolved here

        // Recent activity
        $activities = [
            'clearances' => DocumentRequest::where('document_type', 'Barangay Clearance')->latest()->take(5)->get(),
            'residents' => Resident::latest()->take(5)->get(),
            'blotter' => BlotterRecord::latest()->take(5)->get(),
            'documents' => DocumentRequest::latest()->take(5)->get(),
            'announcements' => Announcement::latest()->take(5)->get(),
        ];

        // Monthly Barangay Trends (Jan–Dec of the current year)
        $monthlyBarangayTrends = $this->monthlyBarangayTrends();

        // Status breakdown — document requests by status
        $statusBreakdown = [
            'Pending'  => DocumentRequest::whereIn('status', ['Pending', 'Pending Official', 'Approved'])->count(),
            'Issued'   => DocumentRequest::where('status', 'Issued')->count(),
            'Rejected' => DocumentRequest::where('status', 'Rejected')->count(),
        ];

        // Sector Summary counts active residents only; each bar is a share of its own base
        // (4Ps is recorded on the household head, so it's a share of households, not residents).
        $active            = fn () => Resident::where('status', 'Active');
        $activeResidents   = $active()->count();
        $activeHouseholds  = \App\Models\Household::whereHas('residents', fn ($r) => $r->where('status', 'Active'))->count();
        $sectorSummary = [
            ['label' => '4Ps Households',     'count' => $active()->where('is_4ps', true)->where('is_head', true)->count(), 'base' => $activeHouseholds, 'bar' => 'bg-blue-500'],
            ['label' => 'Senior Citizens',    'count' => $active()->where('is_senior_citizen', true)->count(),   'base' => $activeResidents, 'bar' => 'bg-orange-500'],
            ['label' => 'Social Pension',     'count' => $active()->where('is_social_pensioner', true)->count(), 'base' => $activeResidents, 'bar' => 'bg-teal-500'],
            ['label' => 'Pension Candidates', 'count' => $active()->socialPensionCandidates()->count(),          'base' => $activeResidents, 'bar' => 'bg-teal-300'],
            ['label' => 'PWD',                'count' => $active()->where('is_pwd', true)->count(),              'base' => $activeResidents, 'bar' => 'bg-purple-500'],
            ['label' => 'Solo Parents',       'count' => $active()->where('is_solo_parent', true)->count(),      'base' => $activeResidents, 'bar' => 'bg-pink-500'],
            ['label' => 'Pregnant',           'count' => $active()->currentlyPregnant()->count(),                'base' => $activeResidents, 'bar' => 'bg-rose-400'],
        ];

        $psaThresholds = \App\Services\ClassificationService::psaThresholds();
        $psaAssessed   = \App\Models\Household::whereNotNull('psa_status')->count();
        $psaBelow      = \App\Models\Household::whereIn('psa_status', \App\Services\ClassificationService::PSA_POOR)->count();
        $psaSummary    = [
            'configured' => $psaThresholds['poverty'] > 0,
            'assessed'   => $psaAssessed,
            'below'      => $psaBelow,
            'incidence'  => $psaAssessed > 0 ? round($psaBelow / $psaAssessed * 100, 1) : 0,
        ];

        return view('dashboard', compact(
            'totalResidents',
            'thisMonthResidents',
            'pendingDocuments',
            'resolvedCases',
            'activities',
            'monthlyBarangayTrends',
            'statusBreakdown',
            'sectorSummary',
            'psaSummary'
        ));
    }

    public function monthlyTrends()
    {
        return response()->json($this->monthlyBarangayTrends());
    }

    private function monthlyBarangayTrends(): array
    {
        $year = now()->year;

        $docsIssued = DocumentRequest::where('status', 'Issued')
            ->whereYear('issued_at', $year)
            ->get(['issued_at'])
            ->groupBy(fn ($row) => $row->issued_at->month)
            ->map->count();

        $complaints = BlotterRecord::whereYear('created_at', $year)
            ->get(['created_at'])
            ->groupBy(fn ($row) => $row->created_at->month)
            ->map->count();

        $newResidents = Resident::whereYear('created_at', $year)
            ->get(['created_at'])
            ->groupBy(fn ($row) => $row->created_at->month)
            ->map->count();

        $labels = $documentsIssued = $complaintsData = $residentsData = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[]          = \Carbon\Carbon::create($year, $m, 1)->format('M');
            $documentsIssued[] = $docsIssued[$m] ?? 0;
            $complaintsData[]  = $complaints[$m] ?? 0;
            $residentsData[]   = $newResidents[$m] ?? 0;
        }

        return [
            'labels'          => $labels,
            'documentsIssued' => $documentsIssued,
            'complaints'      => $complaintsData,
            'newResidents'    => $residentsData,
        ];
    }

    public function staffDashboard()
    {
        // Staff dashboard statistics
        $docsProcessedToday = DocumentRequest::where('status', 'Issued')->whereDate('issued_at', today())->count();
        $pendingApprovals = DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])->count();
        // "Pending" isn't a blotter status; active = not yet settled or referred.
        $activeBlotterCases = BlotterRecord::whereIn('status', array_merge(BlotterRecord::STATUS_PENDING, BlotterRecord::STATUS_ONGOING))->count();
        $residentsThisMonth = Resident::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // Pending approvals queue
        $pendingQueue = DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])
            ->with('resident')
            ->latest()
            ->take(5)
            ->get();

        // Recent documents
        $recentDocuments = DocumentRequest::latest()->take(5)->get();

        return view('staff.dashboard', compact(
            'docsProcessedToday',
            'pendingApprovals',
            'activeBlotterCases',
            'residentsThisMonth',
            'pendingQueue',
            'recentDocuments'
        ));
    }
}
