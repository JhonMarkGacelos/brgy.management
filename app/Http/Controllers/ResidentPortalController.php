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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ResidentPortalController extends Controller
{
    /** The resident profile linked to the logged-in portal account (matched by email). */
    private function myResident(): ?Resident
    {
        $email = Auth::user()->email;

        return $email ? Resident::where('email', $email)->where('status', 'Active')->first() : null;
    }

    private function myAnnouncements()
    {
        return Announcement::live()->whereIn('audience', Announcement::audiencesFor($this->myResident()));
    }

    public function dashboard()
    {
        $announcements = $this->myAnnouncements()
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
        $announcements = $this->myAnnouncements()
            ->latest('published_at')
            ->paginate(9);

        return view('resident.announcements.index', compact('announcements'));
    }

    public function announcementsShow(string $id)
    {
        $announcement = $this->myAnnouncements()->find($id);

        // Links in older notifications can point to an announcement that has since expired or been removed.
        if (!$announcement) {
            return redirect()->route('resident.announcements.index')
                ->with('info', 'That announcement is no longer available.');
        }

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
        $gcashQrUrl       = Setting::get('gcash_qr_url');
        $gcashNumber      = Setting::get('gcash_number');
        $gcashAccountName = Setting::get('gcash_account_name');
        return view('resident.documents.create', compact('fees', 'gcashQrUrl', 'gcashNumber', 'gcashAccountName'));
    }

    public function documentsStore(Request $request)
    {
        $feeMap = [
            'Barangay Clearance'       => (float) Setting::get('fee_barangay_clearance', 50),
            'Certificate of Residency' => (float) Setting::get('fee_certificate_of_residency', 50),
            'Certificate of Indigency' => (float) Setting::get('fee_certificate_of_indigency', 0),
            'Business Clearance'       => (float) Setting::get('fee_business_clearance', 200),
        ];
        $selectedFee     = $feeMap[$request->document_type] ?? 0;
        $gcashConfigured = (bool) (Setting::get('gcash_number') || Setting::get('gcash_qr_url'));

        $rules = [
            'document_type' => ['required', 'string', Rule::in(array_keys($feeMap))],
            'purpose'       => 'required|string|max:255',
            'last_name'     => 'required|string|max:100',
            'first_name'    => 'required|string|max:100',
            'id_photo'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
        if ($selectedFee > 0 && $gcashConfigured) {
            $rules['payment_receipt'] = 'required|image|mimes:jpg,jpeg,png,webp|max:5120';
        }
        $request->validate($rules);

        // Exact (case-insensitive) match — LIKE would treat "%" or "_" typed into the form as wildcards
        // and could match someone else's record.
        $resident = Resident::whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower(trim($request->last_name))])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower(trim($request->first_name))])
            ->where('status', 'Active')
            ->first();

        if (! $resident) {
            return back()->withInput()->with('resident_not_found', true);
        }

        // The resident profile's email is not touched here: matching is by name only, so writing the
        // requester's email would let anyone attach their address to another person's record. The requester
        // is notified through requested_by when the request is issued or rejected.

        try {
            $upload = app(CloudinaryService::class)->uploadIdPhoto($request->file('id_photo'));
            $photoUrl      = $upload['url'];
            $photoPublicId = $upload['public_id'];
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['id_photo' => 'ID photo upload failed. Please try again or contact barangay staff.']);
        }

        $receiptUrl = $receiptPublicId = null;
        if ($selectedFee > 0 && $gcashConfigured) {
            try {
                $receiptUpload   = app(CloudinaryService::class)->uploadIdPhoto($request->file('payment_receipt'), 'Payment Receipts');
                $receiptUrl      = $receiptUpload['url'];
                $receiptPublicId = $receiptUpload['public_id'];
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['payment_receipt' => 'Payment receipt upload failed. Please try again or contact barangay staff.']);
            }
        }

        $doc = DocumentRequest::create([
            'tracking_number'           => DocumentRequest::generateTrackingNumber(),
            'document_type'             => $request->document_type,
            'purpose'                   => $request->purpose,
            'fee'                       => $selectedFee,
            'status'                    => 'Pending',
            'id_photo_url'              => $photoUrl,
            'id_photo_public_id'        => $photoPublicId,
            'id_verified'               => 'pending',
            'payment_receipt_url'       => $receiptUrl,
            'payment_receipt_public_id' => $receiptPublicId,
            'payment_verified'          => $receiptUrl ? 'pending' : null,
            'resident_id'               => $resident->id,
            'requested_by'              => Auth::id(),
        ]);

        activity('resident_request')
            ->causedBy(Auth::user())
            ->performedOn($doc)
            ->log("Resident submitted {$doc->document_type} request {$doc->tracking_number}");

        // Notify admins of the new request
        $admins = User::where('role', 'admin')->whereNotNull('email')->get();
        try {
            Notification::send($admins, new DocumentRequestSubmitted($doc));
        } catch (\Throwable $e) {
            Log::error('Failed to send DocumentRequestSubmitted notification', [
                'document_id' => $doc->id,
                'error'       => $e->getMessage(),
            ]);
        }

        return redirect()->route('resident.documents.index')
            ->with('success', "Request submitted! Your tracking number is {$doc->tracking_number}.");
    }

    public function documentsTrack(Request $request)
    {
        $request->validate(['tracking_number' => 'required|string']);

        // Only the account that filed the request can track it (tracking numbers are sequential).
        $document = DocumentRequest::where('tracking_number', strtoupper(trim($request->tracking_number)))
            ->where('requested_by', Auth::id())
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
