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
        $totalResidents = Resident::count();
        $thisMonthResidents = Resident::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $pendingDocuments = DocumentRequest::where('status', 'Pending')->count();
        $resolvedCases = BlotterRecord::whereIn('status', ['Settled', 'Resolved'])->count();

        // Recent activity
        $activities = [
            'clearances' => DocumentRequest::where('document_type', 'Barangay Clearance')->latest()->take(5)->get(),
            'residents' => Resident::latest()->take(5)->get(),
            'blotter' => BlotterRecord::latest()->take(5)->get(),
            'documents' => DocumentRequest::latest()->take(5)->get(),
            'announcements' => Announcement::latest()->take(5)->get(),
        ];

        // Blotter trend (last 6 months)
        $blotterTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $blotterTrend[] = BlotterRecord::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // Status breakdown — document requests by status
        $statusBreakdown = [
            'Pending'  => DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])->count(),
            'Issued'   => DocumentRequest::where('status', 'Issued')->count(),
            'Rejected' => DocumentRequest::where('status', 'Rejected')->count(),
        ];

        $sectorSummary = [
            ['label' => '4Ps Members',    'count' => Resident::where('is_4ps', true)->count(),          'bar' => 'bg-blue-500'],
            ['label' => 'Senior Citizens','count' => Resident::where('is_senior_citizen', true)->count(),'bar' => 'bg-orange-500'],
            ['label' => 'PWD',            'count' => Resident::where('is_pwd', true)->count(),           'bar' => 'bg-purple-500'],
            ['label' => 'Solo Parents',   'count' => Resident::where('is_solo_parent', true)->count(),   'bar' => 'bg-pink-500'],
            ['label' => 'Pregnant',       'count' => Resident::where('is_pregnant', true)->count(),      'bar' => 'bg-rose-400'],
        ];

        return view('dashboard', compact(
            'totalResidents',
            'thisMonthResidents',
            'pendingDocuments',
            'resolvedCases',
            'activities',
            'blotterTrend',
            'statusBreakdown',
            'sectorSummary'
        ));
    }

    public function staffDashboard()
    {
        // Staff dashboard statistics
        $docsProcessedToday = DocumentRequest::whereDate('created_at', today())->count();
        $pendingApprovals = DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])->count();
        $activeBlotterCases = BlotterRecord::where('status', 'Pending')->count();
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
