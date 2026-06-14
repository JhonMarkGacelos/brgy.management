<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = DocumentRequest::with(['resident', 'requestedBy']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('or_number', 'like', "%{$search}%")
                  ->orWhere('document_type', 'like', "%{$search}%")
                  ->orWhereHas('resident', fn($r) => $r->where('first_name', 'like', "%{$search}%")
                                                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->type) {
            $query->where('document_type', $type);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $documents = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $stats = [
            'pending'  => DocumentRequest::whereIn('status', ['Pending', 'Pending Official'])->count(),
            'issued'   => DocumentRequest::where('status', 'Issued')->count(),
            'rejected' => DocumentRequest::where('status', 'Rejected')->count(),
        ];

        $byType = DocumentRequest::selectRaw('document_type, COUNT(*) as count')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->groupBy('document_type')
            ->pluck('count', 'document_type')
            ->toArray();

        return view('documents.index', compact('documents', 'stats', 'byType'));
    }

    private function docFees(): array
    {
        return [
            'Barangay Clearance'       => (float) Setting::get('fee_barangay_clearance', 50),
            'Certificate of Residency' => (float) Setting::get('fee_certificate_of_residency', 50),
            'Certificate of Indigency' => (float) Setting::get('fee_certificate_of_indigency', 0),
            'Business Clearance'       => (float) Setting::get('fee_business_clearance', 200),
        ];
    }

    public function create()
    {
        $residents = Resident::where('status', 'Active')->orderBy('last_name')->get();
        $nextTracking = DocumentRequest::generateTrackingNumber();
        $fees = $this->docFees();
        return view('documents.create', compact('residents', 'nextTracking', 'fees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'purpose'       => 'required|string|max:255',
            'resident_id'   => 'nullable|exists:residents,id',
        ]);

        $document = DocumentRequest::create([
            'tracking_number'  => DocumentRequest::generateTrackingNumber(),
            'document_type'    => $request->document_type,
            'purpose'          => $request->purpose,
            'fee'              => $request->fee ?? 0,
            'or_number'        => $request->filled('or_number') ? $request->or_number : null,
            'status'           => 'Pending',
            'resident_id'      => $request->resident_id,
            'requested_by'     => Auth::id(),
            'business_name'    => $request->business_name,
            'business_type'    => $request->business_type,
            'business_address' => $request->business_address,
        ]);

        $printRoute = Auth::user()->role === 'staff' ? 'staff.documents.print' : 'documents.print';
        return redirect()->route($printRoute, $document->id);
    }

    public function show(string $id)
    {
        $document = DocumentRequest::with(['resident', 'requestedBy', 'processedBy'])->findOrFail($id);
        return view('documents.show', compact('document'));
    }

    public function edit(string $id)
    {
        $document  = DocumentRequest::findOrFail($id);
        $residents = Resident::where('status', 'Active')->orderBy('last_name')->get();
        $fees = $this->docFees();
        return view('documents.create', compact('document', 'residents', 'fees'));
    }

    public function update(Request $request, string $id)
    {
        $document = DocumentRequest::findOrFail($id);

        // Manual OR number entry takes priority; otherwise auto-generate when approving/issuing
        if ($request->has('or_number') && $request->filled('or_number')) {
            $orNumber = $request->or_number;
        } elseif ($document->or_number) {
            $orNumber = $document->or_number;
        } elseif (in_array($request->status, ['Approved', 'Issued'])) {
            $orNumber = DocumentRequest::generateOrNumber();
        } else {
            $orNumber = null;
        }

        $updateData = [
            'status'       => $request->status,
            'remarks'      => $request->remarks,
            'or_number'    => $orNumber,
            'processed_by' => Auth::id(),
            'issued_at'    => $request->status === 'Issued' ? now() : $document->issued_at,
        ];

        if ($request->filled('purpose')) {
            $updateData['purpose'] = $request->purpose;
        }

        $document->update($updateData);

        // Delete ID photo from Cloudinary once document is issued (privacy cleanup)
        if ($request->status === 'Issued' && $document->id_photo_public_id) {
            try {
                (new \App\Services\CloudinaryService)->delete($document->id_photo_public_id);
                $document->update(['id_photo_url' => null, 'id_photo_public_id' => null]);
            } catch (\Throwable) {}
        }

        $successMsg = 'Document updated.' . ($orNumber ? ' OR No: ' . $orNumber : '');

        // Redirect back to show page when saving details inline; otherwise go to index
        if ($request->has('edit_details')) {
            $showRoute = Auth::user()->role === 'staff' ? 'staff.documents.show' : 'documents.show';
            return redirect()->route($showRoute, $id)->with('success', $successMsg);
        }

        $route = Auth::user()->role === 'staff' ? 'staff.documents.index' : 'documents.index';
        return redirect()->route($route)->with('success', $successMsg);
    }

    public function verifyId(Request $request, string $id)
    {
        $document = DocumentRequest::findOrFail($id);
        $document->update([
            'id_verified'  => $request->action === 'verify' ? 'verified' : 'rejected',
            'processed_by' => Auth::id(),
        ]);

        $label = $request->action === 'verify' ? 'ID verified.' : 'ID rejected.';
        $route = Auth::user()->role === 'staff' ? 'staff.documents.show' : 'documents.show';
        return redirect()->route($route, $id)->with('success', $label);
    }

    public function destroy(string $id)
    {
        DocumentRequest::findOrFail($id)->delete();
        return redirect()->route('documents.index')->with('success', 'Document request deleted.');
    }

    public function print(string $id)
    {
        $document = DocumentRequest::with(['resident.household'])->findOrFail($id);

        // Auto-issue: generate OR and mark as Issued on first print
        if (empty($document->or_number)) {
            $document->or_number = DocumentRequest::generateOrNumber();
        }
        if ($document->status !== 'Issued') {
            $document->status       = 'Issued';
            $document->issued_at    = now();
            $document->processed_by = Auth::id();
        }
        $document->save();

        // Delete ID photo from Cloudinary after issuing (privacy cleanup)
        if ($document->id_photo_public_id) {
            try {
                (new \App\Services\CloudinaryService)->delete($document->id_photo_public_id);
                $document->update(['id_photo_url' => null, 'id_photo_public_id' => null]);
            } catch (\Throwable) {}
        }

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
