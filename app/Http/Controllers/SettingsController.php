<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $povertyLine = Setting::get('poverty_line', 10957);
        return view('settings.index', compact('povertyLine'));
    }

    public function update(Request $request)
    {
        if ($request->has('_thresholds')) {
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
