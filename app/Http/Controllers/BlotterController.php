<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View as IlluminateView;

class BlotterController extends Controller
{
    private function isStaff(): bool
    {
        return Auth::user()->role === 'staff';
    }

    private function v(string $name, array $data = []): IlluminateView
    {
        $prefix = $this->isStaff() ? 'staff.' : '';
        return view($prefix . $name, $data);
    }

    private function r(string $name, mixed $params = []): string
    {
        $prefix = $this->isStaff() ? 'staff.' : '';
        return route($prefix . $name, $params);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $query = BlotterRecord::with('filedBy');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('respondent_name', 'like', "%{$search}%")
                  ->orWhere('incident_type', 'like', "%{$search}%");
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $records = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        $stats = [
            'this_month'      => BlotterRecord::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'open'            => BlotterRecord::where('status', 'Open')->count(),
            'pending'         => BlotterRecord::where('status', 'Pending Official')->count(),
            'mediation'       => BlotterRecord::where('status', 'Under Mediation')->count(),
            'settled'         => BlotterRecord::whereMonth('created_at', now()->month)->where('status', 'Settled')->count(),
            'referred'        => BlotterRecord::where('status', 'Referred')->count(),
            'returned'        => BlotterRecord::where('status', 'Returned w/ Remarks')->count(),
        ];

        return $this->v('blotter.index', compact('records', 'stats'));
    }

    public function create()
    {
        $nextCaseNo = BlotterRecord::generateCaseNumber();
        return $this->v('blotter.create', compact('nextCaseNo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'incident_date'     => 'required|date',
            'incident_type'     => 'required|string',
            'complainant_name'  => 'required|string|max:150',
            'respondent_name'   => 'required|string|max:150',
            'narrative'         => 'required|string',
            'action_taken'      => 'required|string',
            'status'            => 'required|string',
        ]);

        BlotterRecord::create([
            'case_number'          => BlotterRecord::generateCaseNumber(),
            'incident_date'        => $request->incident_date,
            'incident_time'        => $request->incident_time,
            'incident_type'        => $request->incident_type,
            'location'             => $request->location,
            'complainant_name'     => $request->complainant_name,
            'complainant_address'  => $request->complainant_address,
            'complainant_contact'  => $request->complainant_contact,
            'respondent_name'      => $request->respondent_name,
            'respondent_address'   => $request->respondent_address,
            'respondent_contact'   => $request->respondent_contact,
            'witnesses'            => $request->witnesses,
            'narrative'            => $request->narrative,
            'action_taken'         => $request->action_taken,
            'status'               => $request->status,
            'filed_by'             => Auth::id(),
        ]);

        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter case filed successfully.');
    }

    public function show(string $id)
    {
        $record = BlotterRecord::with('filedBy')->findOrFail($id);
        return $this->v('blotter.show', compact('record'));
    }

    public function edit(string $id)
    {
        $record = BlotterRecord::findOrFail($id);
        return $this->v('blotter.create', compact('record'));
    }

    public function update(Request $request, string $id)
    {
        $record = BlotterRecord::findOrFail($id);

        $record->update([
            'incident_date'       => $request->incident_date,
            'incident_time'       => $request->incident_time,
            'incident_type'       => $request->incident_type,
            'location'            => $request->location,
            'complainant_name'    => $request->complainant_name,
            'complainant_address' => $request->complainant_address,
            'complainant_contact' => $request->complainant_contact,
            'respondent_name'     => $request->respondent_name,
            'respondent_address'  => $request->respondent_address,
            'respondent_contact'  => $request->respondent_contact,
            'witnesses'           => $request->witnesses,
            'narrative'           => $request->narrative,
            'action_taken'        => $request->action_taken,
            'status'              => $request->status,
            'remarks'             => $request->remarks,
            'resolved_at'         => in_array($request->status, ['Settled', 'Referred']) ? now() : null,
        ]);

        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter record updated.');
    }

    public function destroy(string $id)
    {
        BlotterRecord::findOrFail($id)->delete();
        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter record deleted.');
    }
}
