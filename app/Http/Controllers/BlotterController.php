<?php

namespace App\Http\Controllers;

use App\Models\BlotterRecord;
use App\Notifications\BlotterStatusUpdated;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View as IlluminateView;

class BlotterController extends Controller
{
    private const CATEGORIES = [
        'Noise Complaint',
        'Physical Fight',
        'Property Dispute',
        'Theft',
        'Family / Domestic Dispute',
        'Others',
    ];

    /**
     * Send a notification without letting a mail/SMTP failure bubble up and
     * fail the controller action after the related DB write already succeeded.
     */
    private function safeNotify(object $notifiable, object $notification, array $context = []): void
    {
        try {
            $notifiable->notify($notification);
        } catch (\Throwable $e) {
            Log::error('Failed to send notification: ' . get_class($notification), array_merge($context, [
                'error' => $e->getMessage(),
            ]));
        }
    }

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
            // Settled this month = closing date in the current month (not filing date, and not any year's same month).
            'settled'         => BlotterRecord::where('status', 'Settled')
                ->whereYear('resolved_at', now()->year)->whereMonth('resolved_at', now()->month)->count(),
            'referred'        => BlotterRecord::where('status', 'Referred')->count(),
            'returned'        => BlotterRecord::where('status', 'Returned w/ Remarks')->count(),
        ];

        return view('blotter.index', compact('records', 'stats'));
    }

    public function create()
    {
        $nextCaseNo = BlotterRecord::generateCaseNumber();
        return view('blotter.create', compact('nextCaseNo'));
    }

    /** Same rules for filing and editing a case (editing used to skip them, so blanks could be saved). */
    private function rules(): array
    {
        return [
            'incident_date'       => 'required|date',
            'incident_type'       => ['required', 'string', Rule::in(self::CATEGORIES)],
            'incident_type_other' => 'required_if:incident_type,Others|nullable|string|max:255',
            'complainant_name'    => 'required|string|max:150',
            'respondent_name'     => 'required|string|max:150',
            'narrative'           => 'required|string',
            'action_taken'        => 'required|string',
            'status'              => ['required', Rule::in(BlotterRecord::STATUSES)],
            'hearing_date'        => 'nullable|date',
            // Stored times come back as HH:MM:SS; accept both so an unchanged time never blocks a save.
            'hearing_time'        => 'nullable|date_format:H:i,H:i:s',
            'incident_time'       => 'nullable|date_format:H:i,H:i:s',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());

        $attributes = [
            'incident_date'        => $request->incident_date,
            'incident_time'        => $request->incident_time,
            'incident_type'        => $request->incident_type,
            'incident_type_other'  => $request->incident_type === 'Others' ? $request->incident_type_other : null,
            'location'             => $request->location,
            'complainant_name'     => $request->complainant_name,
            'complainant_address'  => $request->complainant_address,
            'complainant_contact'  => $request->complainant_contact,
            'complainant_email'    => $request->complainant_email,
            'respondent_name'      => $request->respondent_name,
            'respondent_address'   => $request->respondent_address,
            'respondent_contact'   => $request->respondent_contact,
            'respondent_email'     => $request->respondent_email,
            'witnesses'            => $request->witnesses,
            'narrative'            => $request->narrative,
            'action_taken'         => $request->action_taken,
            'status'               => $request->status,
            'hearing_date'         => $request->hearing_date,
            'hearing_time'         => $request->hearing_time,
            'filed_by'             => Auth::id(),
            'resolved_at'          => in_array($request->status, BlotterRecord::STATUS_RESOLVED) ? now() : null,
        ];

        // generateCaseNumber() isn't lock-protected, so two near-simultaneous
        // submissions can compute the same number; retry with a fresh one
        // instead of 500ing on the unique constraint.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                BlotterRecord::create(['case_number' => BlotterRecord::generateCaseNumber()] + $attributes);
                break;
            } catch (QueryException $e) {
                if (! str_starts_with($e->getCode(), '23') || $attempt === 3) {
                    throw $e;
                }
            }
        }

        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter case filed successfully.');
    }

    public function show(string $id)
    {
        $record = BlotterRecord::with('filedBy')->findOrFail($id);
        return view('blotter.show', compact('record'));
    }

    public function edit(string $id)
    {
        $record = BlotterRecord::findOrFail($id);
        return view('blotter.create', compact('record'));
    }

    public function update(Request $request, string $id)
    {
        $record = BlotterRecord::findOrFail($id);

        $request->validate($this->rules());

        $oldStatus = $record->status;
        $isClosed  = in_array($request->status, BlotterRecord::STATUS_RESOLVED);
        // Keep the original closing date when a closed case is only being edited.
        $resolvedAt = $isClosed
            ? ($record->resolved_at && in_array($oldStatus, BlotterRecord::STATUS_RESOLVED) ? $record->resolved_at : now())
            : null;

        $record->update([
            'incident_date'       => $request->incident_date,
            'incident_time'       => $request->incident_time,
            'incident_type'       => $request->incident_type,
            'incident_type_other' => $request->incident_type === 'Others' ? $request->incident_type_other : null,
            'location'            => $request->location,
            'complainant_name'    => $request->complainant_name,
            'complainant_address' => $request->complainant_address,
            'complainant_contact' => $request->complainant_contact,
            'complainant_email'   => $request->complainant_email ?? $record->complainant_email,
            'respondent_name'     => $request->respondent_name,
            'respondent_address'  => $request->respondent_address,
            'respondent_contact'  => $request->respondent_contact,
            'respondent_email'    => $request->respondent_email ?? $record->respondent_email,
            'witnesses'           => $request->witnesses,
            'narrative'           => $request->narrative,
            'action_taken'        => $request->action_taken,
            'status'              => $request->status,
            'hearing_date'        => $request->hearing_date,
            'hearing_time'        => $request->hearing_time,
            'remarks'             => $request->remarks,
            'resolved_at'         => $resolvedAt,
        ]);

        // Notify both parties when status changes
        if ($oldStatus !== $request->status) {
            if ($record->complainant_email) {
                $user = \App\Models\User::where('email', $record->complainant_email)->first();
                $notifiable = $user ?? Notification::route('mail', $record->complainant_email);
                $this->safeNotify($notifiable, new BlotterStatusUpdated($record, 'complainant'), ['record_id' => $record->id, 'email' => $record->complainant_email]);
            }
            if ($record->respondent_email) {
                $user = \App\Models\User::where('email', $record->respondent_email)->first();
                $notifiable = $user ?? Notification::route('mail', $record->respondent_email);
                $this->safeNotify($notifiable, new BlotterStatusUpdated($record, 'respondent'), ['record_id' => $record->id, 'email' => $record->respondent_email]);
            }
        }

        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter record updated.');
    }

    public function destroy(string $id)
    {
        BlotterRecord::findOrFail($id)->delete();
        return redirect()->to($this->r('blotter.index'))
            ->with('success', 'Blotter record deleted.');
    }

    /**
     * Generate a single PDF containing both the Complainant and Respondent
     * summon letters (one page each) for a case, once its hearing schedule
     * has been set via the edit form.
     */
    public function printSummon(string $id)
    {
        $record = BlotterRecord::findOrFail($id);

        if (! $record->hearing_date || ! $record->hearing_time) {
            return redirect()->to($this->r('blotter.show', $record->id))
                ->with('error', 'Please set the hearing date and time (via Edit Case) before printing the summon.');
        }

        $pdf = Pdf::loadView('blotter.print.summon', compact('record'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("Summon-{$record->case_number}.pdf");
    }
}
