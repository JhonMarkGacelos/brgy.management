<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
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
                    Setting::set($key, $request->$key);
                }
            }
            if ($request->filled('captain_name')) {
                Setting::set('captain_name', $request->captain_name);
            }
            Setting::set('captain_gmail', $request->input('captain_gmail', ''));
        } elseif ($request->has('_signature')) {
            Setting::set('captain_signature_height', (int) $request->input('signature_height', 180));
            if ($request->hasFile('signature_image')) {
                $request->validate(['signature_image' => 'required|image|max:2048']);
                $oldPublicId = Setting::get('captain_signature_public_id');
                if ($oldPublicId) {
                    $cloudinary->delete($oldPublicId);
                }
                $result = $cloudinary->uploadIdPhoto($request->file('signature_image'), 'signatures');
                Setting::set('captain_signature_url',       $result['url']);
                Setting::set('captain_signature_public_id', $result['public_id']);
            }
        } elseif ($request->has('_fees')) {
            $request->validate([
                'fee_barangay_clearance'       => 'required|numeric|min:0',
                'fee_certificate_of_residency' => 'required|numeric|min:0',
                'fee_certificate_of_indigency' => 'required|numeric|min:0',
                'fee_business_clearance'       => 'required|numeric|min:0',
            ]);
            foreach (['fee_barangay_clearance','fee_certificate_of_residency','fee_certificate_of_indigency','fee_business_clearance'] as $key) {
                Setting::set($key, $request->$key);
            }
        } elseif ($request->has('_thresholds')) {
            $request->validate([
                'per_capita_extremely_poor' => 'required|numeric|min:0',
                'per_capita_poor'           => 'required|numeric|min:0',
                'per_capita_near_poor'      => 'required|numeric|min:0',
                'per_capita_vulnerable'     => 'required|numeric|min:0',
            ]);
            foreach (['per_capita_extremely_poor','per_capita_poor','per_capita_near_poor','per_capita_vulnerable'] as $key) {
                Setting::set($key, $request->$key);
            }
        } else {
            $request->validate(['poverty_line' => 'required|numeric|min:0']);
            Setting::set('poverty_line', $request->poverty_line);
        }

        return redirect()->route('settings.index')
                         ->with('success', 'Settings updated successfully.');
    }
}
