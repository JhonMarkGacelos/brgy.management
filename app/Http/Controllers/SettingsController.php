<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Setting's primary key is a string, which the shared activity_log table's
     * subject_id column (unsignedBigInteger) can't store, so it can't use the
     * LogsActivity trait like the other models. Log manually instead, without
     * performedOn() (subject stays null), same as the resident_request calls
     * elsewhere in the app.
     */
    private array $settingsOld = [];
    private array $settingsNew = [];

    private function setAndTrack(string $key, mixed $value): void
    {
        $this->settingsOld[$key] = Setting::get($key);
        $this->settingsNew[$key] = $value;
        Setting::set($key, $value);
    }

    private function logSettingsChange(string $description): void
    {
        if (empty($this->settingsNew)) {
            return;
        }

        activity('setting')
            ->causedBy(Auth::user())
            ->withChanges(['old' => $this->settingsOld, 'attributes' => $this->settingsNew])
            ->log($description);
    }

    public function index()
    {
        $povertyLine      = Setting::get('poverty_line', 10957);
        $captainName             = Setting::get('captain_name', 'HON. PUNONG BARANGAY');
        $captainGmail            = Setting::get('captain_gmail', '');
        $captainSignature        = Setting::get('captain_signature_url');
        $captainSignatureHeight  = (int) Setting::get('captain_signature_height', 80);
        $brgyInfo = [
            'brgy_name'         => Setting::get('brgy_name',        'Caranas'),
            'brgy_municipality' => Setting::get('brgy_municipality', 'Motiong'),
            'brgy_province'     => Setting::get('brgy_province',     'Samar'),
            'brgy_region'       => Setting::get('brgy_region',       'Region VIII — Eastern Visayas'),
            'brgy_contact'      => Setting::get('brgy_contact',      '+63 (55) 000-0000'),
        ];
        $docFees = [
            'fee_barangay_clearance'       => Setting::get('fee_barangay_clearance', 50),
            'fee_certificate_of_residency' => Setting::get('fee_certificate_of_residency', 50),
            'fee_certificate_of_indigency' => Setting::get('fee_certificate_of_indigency', 0),
            'fee_business_clearance'       => Setting::get('fee_business_clearance', 200),
        ];
        $dbDriver = config('database.default');
        $dbLabel  = match($dbDriver) {
            'pgsql'  => 'PostgreSQL',
            'mysql'  => 'MySQL',
            'sqlite' => 'SQLite',
            default  => ucfirst($dbDriver),
        };
        try {
            $raw = DB::select('SELECT version()')[0]->version ?? '';
            if ($dbDriver === 'pgsql' && preg_match('/PostgreSQL ([\d.]+)/', $raw, $m)) {
                $dbLabel = 'PostgreSQL ' . $m[1];
            } elseif ($dbDriver === 'mysql' && preg_match('/([\d.]+)/', $raw, $m)) {
                $dbLabel = 'MySQL ' . $m[1];
            }
        } catch (\Exception) {}

        $systemInfo = [
            ['label' => 'System Version', 'value' => 'v1.0.0'],
            ['label' => 'Laravel',        'value' => 'v' . app()->version()],
            ['label' => 'PHP Version',    'value' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION],
            ['label' => 'Database',       'value' => $dbLabel],
            ['label' => 'Last Updated',   'value' => \Carbon\Carbon::createFromTimestamp(filemtime(base_path('composer.lock')))->format('M d, Y')],
        ];

        return view('settings.index', compact('povertyLine', 'captainName', 'captainGmail', 'captainSignature', 'captainSignatureHeight', 'brgyInfo', 'docFees', 'systemInfo'));
    }

    public function update(Request $request, CloudinaryService $cloudinary)
    {
        if ($request->has('_brgy_info')) {
            foreach (['brgy_name', 'brgy_municipality', 'brgy_province', 'brgy_region', 'brgy_contact'] as $key) {
                if ($request->filled($key)) {
                    $this->setAndTrack($key, $request->$key);
                }
            }
            if ($request->filled('captain_name')) {
                $this->setAndTrack('captain_name', $request->captain_name);
            }
            $this->setAndTrack('captain_gmail', $request->input('captain_gmail', ''));
            $this->logSettingsChange('Barangay info updated');
        } elseif ($request->has('_signature')) {
            $this->setAndTrack('captain_signature_height', (int) $request->input('signature_height', 180));
            if ($request->hasFile('signature_image')) {
                $request->validate(['signature_image' => 'required|image|max:2048']);
                $oldPublicId = Setting::get('captain_signature_public_id');
                if ($oldPublicId) {
                    $cloudinary->delete($oldPublicId);
                }
                $result = $cloudinary->uploadIdPhoto($request->file('signature_image'), 'signatures');
                $this->setAndTrack('captain_signature_url',       $result['url']);
                $this->setAndTrack('captain_signature_public_id', $result['public_id']);
            }
            $this->logSettingsChange('Captain signature updated');
        } elseif ($request->has('_fees')) {
            $request->validate([
                'fee_barangay_clearance'       => 'required|numeric|min:0',
                'fee_certificate_of_residency' => 'required|numeric|min:0',
                'fee_certificate_of_indigency' => 'required|numeric|min:0',
                'fee_business_clearance'       => 'required|numeric|min:0',
            ]);
            foreach (['fee_barangay_clearance','fee_certificate_of_residency','fee_certificate_of_indigency','fee_business_clearance'] as $key) {
                $this->setAndTrack($key, $request->$key);
            }
            $this->logSettingsChange('Document fees updated');
        } elseif ($request->has('_thresholds')) {
            $request->validate([
                'per_capita_extremely_poor' => 'required|numeric|min:0',
                'per_capita_poor'           => 'required|numeric|min:0',
                'per_capita_near_poor'      => 'required|numeric|min:0',
                'per_capita_vulnerable'     => 'required|numeric|min:0',
            ]);
            foreach (['per_capita_extremely_poor','per_capita_poor','per_capita_near_poor','per_capita_vulnerable'] as $key) {
                $this->setAndTrack($key, $request->$key);
            }
            $this->logSettingsChange('Poverty thresholds updated');
        } else {
            $request->validate(['poverty_line' => 'required|numeric|min:0']);
            $this->setAndTrack('poverty_line', $request->poverty_line);
            $this->logSettingsChange('Poverty line updated');
        }

        return redirect()->route('settings.index')
                         ->with('success', 'Settings updated successfully.');
    }
}
