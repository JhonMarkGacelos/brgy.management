<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;

class DocumentVerifyController extends Controller
{
    public function index()
    {
        return view('verify');
    }

    public function check(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));

        // This page is public: only issued documents are confirmed. Pending/rejected requests (and their
        // requester's name and purpose) must not be viewable by anyone who types in a tracking number.
        $document = DocumentRequest::with(['resident', 'processedBy'])
            ->where('status', 'Issued')
            ->where(fn ($q) => $q->where('or_number', $code)->orWhere('tracking_number', $code))
            ->first();

        return view('verify', compact('document', 'code'));
    }
}
