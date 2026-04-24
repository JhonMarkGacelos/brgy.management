<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidentPortalController extends Controller
{
    public function dashboard()
    {
        $announcements = Announcement::published()
            ->where(function ($q) {
                $q->where('audience', 'All Residents')
                  ->orWhere('audience', 'All');
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        $myRequests = DocumentRequest::where('requested_by', Auth::id())
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'pending'  => DocumentRequest::where('requested_by', Auth::id())->whereIn('status', ['Pending', 'Pending Official'])->count(),
            'approved' => DocumentRequest::where('requested_by', Auth::id())->where('status', 'Approved')->count(),
            'issued'   => DocumentRequest::where('requested_by', Auth::id())->where('status', 'Issued')->count(),
        ];

        return view('resident.dashboard', compact('announcements', 'myRequests', 'stats'));
    }

    // ── ANNOUNCEMENTS ─────────────────────────────────────────────

    public function announcementsIndex()
    {
        $announcements = Announcement::published()
            ->where(function ($q) {
                $q->where('audience', 'All Residents')
                  ->orWhere('audience', 'All');
            })
            ->latest('published_at')
            ->paginate(9);

        return view('resident.announcements.index', compact('announcements'));
    }

    public function announcementsShow(string $id)
    {
        $announcement = Announcement::published()->findOrFail($id);

        return view('resident.announcements.show', compact('announcement'));
    }

    // ── DOCUMENT REQUESTS ─────────────────────────────────────────

    public function documentsIndex()
    {
        $myRequests = DocumentRequest::where('requested_by', Auth::id())
            ->latest()
            ->paginate(10);

        return view('resident.documents.index', compact('myRequests'));
    }

    public function documentsCreate()
    {
        return view('resident.documents.create');
    }

    public function documentsStore(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'purpose'       => 'required|string|max:255',
            'last_name'     => 'required|string|max:100',
            'first_name'    => 'required|string|max:100',
            'id_photo'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $resident = Resident::where('last_name', 'like', $request->last_name)
            ->where('first_name', 'like', $request->first_name)
            ->where('status', 'Active')
            ->first();

        if (! $resident) {
            return back()->withInput()->with('resident_not_found', true);
        }

        $upload = (new CloudinaryService)->uploadIdPhoto($request->file('id_photo'));

        $doc = DocumentRequest::create([
            'tracking_number'   => DocumentRequest::generateTrackingNumber(),
            'document_type'     => $request->document_type,
            'purpose'           => $request->purpose,
            'fee'               => 0,
            'status'            => 'Pending',
            'id_photo_url'      => $upload['url'],
            'id_photo_public_id'=> $upload['public_id'],
            'id_verified'       => 'pending',
            'resident_id'       => $resident->id,
            'requested_by'      => Auth::id(),
        ]);

        return redirect()->route('resident.documents.index')
            ->with('success', "Request submitted! Your tracking number is {$doc->tracking_number}.");
    }

    public function documentsTrack(Request $request)
    {
        $request->validate(['tracking_number' => 'required|string']);

        $document = DocumentRequest::where('tracking_number', $request->tracking_number)
            ->with(['resident'])
            ->first();

        return view('resident.documents.track', compact('document'));
    }
}
