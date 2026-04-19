@extends('layouts.app')
@section('title', 'Settings')

@section('content')

{{-- Page Header --}}
<div class="mb-6">
    <h2 class="text-base font-semibold text-gray-900">Settings</h2>
    <p class="text-xs text-gray-400 mt-0.5">Manage barangay information, fees, and system configuration</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Barangay Information --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Barangay Information</p>
            </div>
            <form>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Barangay Name</label>
                    <input type="text" value="Caranas"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Municipality</label>
                    <input type="text" value="Motiong"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Province</label>
                    <input type="text" value="Samar"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Region</label>
                    <input type="text" value="Region VIII — Eastern Visayas"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Punong Barangay</label>
                    <input type="text" value="Hon. Juan dela Cruz"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                    <input type="text" value="+63 (55) 000-0000"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Barangay Logo</label>
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <div class="h-14 w-14 rounded-full bg-white p-0.5 ring-2 ring-gray-200 shrink-0">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-full w-full rounded-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Brgy. Caranas Official Seal</p>
                            <p class="text-xs text-gray-400 mt-0.5 mb-2">PNG, JPG up to 2MB</p>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 cursor-pointer hover:bg-gray-50 transition-colors">
                                <i class="fa-solid fa-upload text-xs"></i> Upload New Logo
                                <input type="file" class="hidden" accept="image/*">
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-5 pb-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> Save Changes
                </button>
            </div>
            </form>
        </div>

        {{-- Document Fees --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-50 text-green-600 text-xs">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Document Fees</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            <th class="px-5 py-3.5">Document Type</th>
                            <th class="px-5 py-3.5">Fee (₱)</th>
                            <th class="px-5 py-3.5">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $fees = [
                                ['type' => 'Barangay Clearance',       'fee' => 50,  'icon' => 'fa-file-shield',       'color' => 'bg-brand-50 text-brand-600'],
                                ['type' => 'Certificate of Residency', 'fee' => 50,  'icon' => 'fa-house-flag',        'color' => 'bg-blue-50 text-blue-600'],
                                ['type' => 'Certificate of Indigency', 'fee' => 0,   'icon' => 'fa-hand-holding-heart','color' => 'bg-orange-50 text-orange-500'],
                                ['type' => 'Business Clearance',       'fee' => 200, 'icon' => 'fa-briefcase',         'color' => 'bg-purple-50 text-purple-600'],
                            ];
                        @endphp
                        @foreach($fees as $f)
                        <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/20 transition-colors border-b border-gray-100 last:border-0">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-lg {{ $f['color'] }} text-xs">
                                        <i class="fa-solid {{ $f['icon'] }}"></i>
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ $f['type'] }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <input type="number" value="{{ $f['fee'] }}" min="0"
                                       class="w-28 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-900
                                              focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                            </td>
                            <td class="px-5 py-3.5">
                                <button class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-colors"
                                        style="background-color:#1a4731;"
                                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                                        onmouseout="this.style.backgroundColor='#1a4731'">
                                    <i class="fa-solid fa-floppy-disk"></i> Save
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- System Info --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-500 text-xs">
                    <i class="fa-solid fa-server"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">System Info</p>
            </div>
            <div class="p-5 space-y-3">
                @php
                    $info = [
                        ['label' => 'System Version',  'value' => 'v1.0.0'],
                        ['label' => 'Laravel',         'value' => '13.x'],
                        ['label' => 'PHP Version',     'value' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION],
                        ['label' => 'Database',        'value' => 'MySQL 8.0'],
                        ['label' => 'Last Updated',    'value' => 'Apr 15, 2025'],
                    ];
                @endphp
                @foreach($info as $i)
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400">{{ $i['label'] }}</span>
                    <span class="text-xs font-semibold text-gray-700 font-mono">{{ $i['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Database Backup --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-database"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Database Backup</p>
            </div>
            <div class="p-5 space-y-4">
                <div class="rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <p class="text-xs text-gray-500 mb-0.5">Last Backup</p>
                    <p class="text-sm font-semibold text-gray-800">April 14, 2025</p>
                    <p class="text-xs text-gray-400">11:59 PM</p>
                </div>
                <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fa-solid fa-download text-xs"></i> Download Backup
                </button>
                <p class="text-[11px] text-gray-400 flex items-start gap-1.5">
                    <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
                    Regular backups are recommended. Keep copies in a secure location.
                </p>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="rounded-2xl border border-red-200 bg-red-50 overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-red-200">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-100 text-red-500 text-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <p class="text-sm font-semibold text-red-700">Danger Zone</p>
            </div>
            <div class="p-5 space-y-3">
                <p class="text-xs text-red-500">These actions are irreversible. Proceed with caution.</p>
                <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 border border-red-300 bg-white hover:bg-red-100 transition-colors">
                    <i class="fa-solid fa-trash text-xs"></i> Clear All Records
                </button>
                <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 border border-red-300 bg-white hover:bg-red-100 transition-colors">
                    <i class="fa-solid fa-rotate-left text-xs"></i> Reset to Defaults
                </button>
            </div>
        </div>

    </div>
</div>

@endsection
