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

        $document = DocumentRequest::with(['resident', 'processedBy'])
            ->where('or_number', $code)
            ->orWhere('tracking_number', $code)
            ->first();

        return view('verify', compact('document', 'code'));
    }
}
