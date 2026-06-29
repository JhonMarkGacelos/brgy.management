<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DocumentRequestSubmitted;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

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
        $fees = [
            'Barangay Clearance'       => (float) Setting::get('fee_barangay_clearance', 50),
            'Certificate of Residency' => (float) Setting::get('fee_certificate_of_residency', 50),
            'Certificate of Indigency' => (float) Setting::get('fee_certificate_of_indigency', 0),
            'Business Clearance'       => (float) Setting::get('fee_business_clearance', 200),
        ];
        return view('resident.documents.create', compact('fees'));
    }

    public function documentsStore(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'purpose'       => 'required|string|max:255',
            'last_name'     => 'required|string|max:100',
            'first_name'    => 'required|string|max:100',
            'email'         => 'nullable|email|max:255',
            'id_photo'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $resident = Resident::where('last_name', 'like', $request->last_name)
            ->where('first_name', 'like', $request->first_name)
            ->where('status', 'Active')
            ->first();

        if (! $resident) {
            return back()->withInput()->with('resident_not_found', true);
        }

        // Save email to resident profile if provided and not already set
        if ($request->filled('email') && empty($resident->email)) {
            $resident->update(['email' => $request->email]);
        }

        try {
            $upload = (new CloudinaryService)->uploadIdPhoto($request->file('id_photo'));
            $photoUrl      = $upload['url'];
            $photoPublicId = $upload['public_id'];
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['id_photo' => 'ID photo upload failed. Please try again or contact barangay staff.']);
        }

        $feeMap = [
            'Barangay Clearance'       => (float) Setting::get('fee_barangay_clearance', 50),
            'Certificate of Residency' => (float) Setting::get('fee_certificate_of_residency', 50),
            'Certificate of Indigency' => (float) Setting::get('fee_certificate_of_indigency', 0),
            'Business Clearance'       => (float) Setting::get('fee_business_clearance', 200),
        ];

        $doc = DocumentRequest::create([
            'tracking_number'   => DocumentRequest::generateTrackingNumber(),
            'document_type'     => $request->document_type,
            'purpose'           => $request->purpose,
            'fee'               => $feeMap[$request->document_type] ?? 0,
            'status'            => 'Pending',
            'id_photo_url'      => $photoUrl,
            'id_photo_public_id'=> $photoPublicId,
            'id_verified'       => 'pending',
            'resident_id'       => $resident->id,
            'requested_by'      => Auth::id(),
        ]);

        activity('resident_request')
            ->causedBy(Auth::user())
            ->performedOn($doc)
            ->log("Resident submitted {$doc->document_type} request {$doc->tracking_number}");

        // Notify admins of the new request
        $admins = User::where('role', 'admin')->whereNotNull('email')->get();
        Notification::send($admins, new DocumentRequestSubmitted($doc));

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

    public function documentsPrint(string $id)
    {
        $document = DocumentRequest::with(['resident.household'])
            ->where('requested_by', Auth::id())
            ->where('status', 'Issued')
            ->findOrFail($id);

        $viewMap = [
            'Barangay Clearance'       => 'documents.print.clearance',
            'Certificate of Residency' => 'documents.print.residency',
            'Certificate of Indigency' => 'documents.print.indigency',
            'Business Clearance'       => 'documents.print.business',
        ];

        $view = $viewMap[$document->document_type] ?? 'documents.print.clearance';

        return view($view, compact('document'));
    }
}
