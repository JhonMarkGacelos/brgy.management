<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function update(Request $request)
    {
        // TODO: save settings
        return redirect()->route('settings.index')
                         ->with('success', 'Settings updated successfully.');
    }
}
