<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $povertyLine = Setting::get('poverty_line', 10957);
        $docFees = [
            'fee_barangay_clearance'       => Setting::get('fee_barangay_clearance', 50),
            'fee_certificate_of_residency' => Setting::get('fee_certificate_of_residency', 50),
            'fee_certificate_of_indigency' => Setting::get('fee_certificate_of_indigency', 0),
            'fee_business_clearance'       => Setting::get('fee_business_clearance', 200),
        ];
        return view('settings.index', compact('povertyLine', 'docFees'));
    }

    public function update(Request $request)
    {
        if ($request->has('_fees')) {
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
