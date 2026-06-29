<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
