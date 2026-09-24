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
            <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            <input type="hidden" name="_brgy_info" value="1">
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Barangay Name</label>
                    <input type="text" name="brgy_name" value="{{ old('brgy_name', $brgyInfo['brgy_name']) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Municipality</label>
                    <input type="text" name="brgy_municipality" value="{{ old('brgy_municipality', $brgyInfo['brgy_municipality']) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Province</label>
                    <input type="text" name="brgy_province" value="{{ old('brgy_province', $brgyInfo['brgy_province']) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Region</label>
                    <input type="text" name="brgy_region" value="{{ old('brgy_region', $brgyInfo['brgy_region']) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Barangay Captain</label>
                    <input type="text" name="captain_name" value="{{ old('captain_name', $captainName) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                    <input type="text" name="brgy_contact" value="{{ old('brgy_contact', $brgyInfo['brgy_contact']) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Captain's Gmail</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-brands fa-google"></i>
                        </span>
                        <input type="email" name="captain_gmail" value="{{ old('captain_gmail', $captainGmail) }}"
                               placeholder="captainname@gmail.com"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Barangay Logo</label>
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <div class="h-14 w-14 rounded-full bg-white p-0.5 ring-2 ring-gray-200 shrink-0">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-full w-full rounded-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Brgy. {{ $brgyInfo['brgy_name'] }} Official Seal</p>
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

        {{-- Captain's Signature --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 text-xs">
                    <i class="fa-solid fa-signature"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Barangay Captain's Signature</p>
                    <p class="text-xs text-gray-400 mt-0.5">Displayed on all issued documents</p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data"
                  x-data="{ sigHeight: {{ $captainSignatureHeight }} }">
                @csrf
                <input type="hidden" name="_signature" value="1">
                <input type="hidden" name="signature_height" :value="sigHeight">
                <div class="p-5 space-y-4">

                    {{-- How-to instructions --}}
                    <div class="rounded-xl bg-blue-50 border border-blue-100 p-4 space-y-2.5">
                        <p class="text-xs font-semibold text-blue-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info"></i> How to prepare your signature image
                        </p>
                        <ol class="text-xs text-blue-700 space-y-1.5 list-none">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">1</span>
                                <span>Sign your name on <strong>white paper</strong> using a dark pen (black or dark blue).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">2</span>
                                <span>Take a clear photo or scan it.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">3</span>
                                <span><strong>Remove the white background</strong> using a free tool — go to <strong class="text-blue-800">remove.bg</strong>, <strong class="text-blue-800">Canva</strong> (Effects → Background Remover), or <strong class="text-blue-800">Adobe Express</strong>, upload your photo, and download the result as PNG.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">4</span>
                                <span>Upload the <strong>PNG file</strong> here. The transparent background makes the signature look natural on documents.</span>
                            </li>
                        </ol>
                        <div class="flex items-center gap-2 pt-1 border-t border-blue-200 mt-1">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xs"></i>
                            <p class="text-[11px] text-blue-600">A white background will show as a white box on the printed document.</p>
                        </div>
                    </div>

                    {{-- Upload --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Signature Image</label>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 space-y-3">
                            @if($captainSignature)
                            <p class="text-xs text-green-600 font-medium flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check"></i> Signature uploaded
                            </p>
                            @else
                            <div class="flex items-center gap-3 text-gray-400">
                                <i class="fa-solid fa-image-portrait text-2xl"></i>
                                <p class="text-xs">No signature uploaded yet. Follow the instructions above to prepare your image.</p>
                            </div>
                            @endif
                            <div>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 cursor-pointer hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-upload text-xs"></i> {{ $captainSignature ? 'Replace Signature' : 'Upload Signature' }}
                                    <input type="file" name="signature_image" class="hidden" accept="image/*">
                                </label>
                                <p class="mt-1.5 text-[11px] text-gray-400">PNG with transparent background · Max 2MB</p>
                            </div>
                        </div>
                    </div>

                    @if($captainSignature)
                    {{-- Size slider --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Signature Size</label>
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] text-gray-400 w-10">Small</span>
                            <input type="range" min="40" max="500" step="5" x-model="sigHeight"
                                   class="flex-1 h-1.5 rounded-full cursor-pointer accent-green-700">
                            <span class="text-[11px] text-gray-400 w-10 text-right">Large</span>
                            <span class="text-xs font-mono font-semibold text-gray-700 w-12 text-right" x-text="sigHeight + 'px'"></span>
                        </div>
                    </div>

                    {{-- Document preview --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Document Preview</label>
                        <div class="rounded-xl border border-gray-200 bg-white p-5">
                            <p class="text-[10px] text-gray-400 mb-4 uppercase tracking-wide font-medium">As it appears on issued documents:</p>
                            <div class="flex justify-end">
                                <div class="text-center" style="min-width:220px; font-family:'Times New Roman',serif;">
                                    <div :style="'position:relative; z-index:2; margin-bottom:-'+Math.round(sigHeight*0.6)+'px; text-align:center;'">
                                        <img src="{{ $captainSignature }}" alt="Signature"
                                             :style="'max-height:'+sigHeight+'px; max-width:'+(sigHeight*3)+'px; width:auto; height:auto;'">
                                    </div>
                                    <div style="position:relative; z-index:1; font-weight:bold; font-size:13pt; letter-spacing:1px; border-top:1.5px solid #111; padding-top:4px;">
                                        {{ $captainName }}
                                    </div>
                                    <div style="font-size:11pt;">Barangay Captain</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
                <div class="px-5 pb-5">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save Signature
                    </button>
                </div>
            </form>
        </div>

        {{-- PSA Poverty Thresholds --}}
        @php
            $psaPreview = ($psa['poverty'] > 0 && (!$psa['food'] || $psa['food'] < $psa['poverty']))
                ? \App\Services\ClassificationService::psaBands($psa['food'], $psa['poverty']) : [];
        @endphp
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-50 text-sky-600 text-xs">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">PSA Poverty Thresholds</p>
                    <p class="text-xs text-gray-400 mt-0.5">Official PSA figures used for each household's PSA Poverty Status</p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="_psa" value="1">
                <div class="p-5 space-y-4">
                    @if($psa['is_default'])
                    <div class="rounded-xl bg-sky-50 border border-sky-100 px-4 py-3 text-xs text-sky-800">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Pre-filled with the PSA 2023 poverty and food thresholds for <strong>Samar</strong>. Update them from PSA RSSO VIII when a newer release is available.
                    </div>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach(['psa_food_threshold' => ['Food Threshold', 'food'], 'psa_poverty_threshold' => ['Poverty Threshold', 'poverty']] as $field => [$label, $key])
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                                {{ $label }} (₱ per person / month)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm font-semibold">₱</span>
                                <input type="number" name="{{ $field }}" value="{{ old($field, $psa[$key]) }}"
                                       min="0" step="0.01" required
                                       class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-8 pr-3.5 py-2.5 text-sm text-gray-900
                                              focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all
                                              @error($field) border-red-400 @enderror">
                            </div>
                            @error($field)
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        @endforeach
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Source</label>
                            <input type="text" name="psa_threshold_source" value="{{ old('psa_threshold_source', $psa['source']) }}"
                                   placeholder="e.g. PSA 2023 Full-Year Official Poverty Statistics, Samar" maxlength="150" required
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                          focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all
                                          @error('psa_threshold_source') border-red-400 @enderror">
                            @error('psa_threshold_source')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="rounded-xl bg-sky-50 border border-sky-100 px-4 py-3 text-xs text-sky-800 space-y-1">
                        <p class="font-semibold flex items-center gap-1.5"><i class="fa-solid fa-circle-info"></i> Where to get these</p>
                        <p>From PSA's official poverty statistics (<span class="font-semibold">psa.gov.ph</span>) for your province. PSA publishes <strong>annual per capita</strong> thresholds: divide by 12. If you only have the monthly figure for a family of five, divide it by 5.</p>
                        <p>Food Poor = below the food threshold · Poor = below the poverty threshold · higher classes are multiples of the poverty threshold (PIDS income classes).</p>
                    </div>
                    @if($psaPreview)
                    <div class="rounded-xl border border-gray-100 overflow-hidden">
                        <table class="w-full text-xs">
                            @foreach($psaPreview as $band)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-3 py-1.5"><span class="inline-flex rounded-md px-2 py-0.5 font-medium {{ \App\Services\ClassificationService::PSA_STYLES[$band['status']] }}">{{ $band['status'] }}</span></td>
                                <td class="px-3 py-1.5 text-right text-gray-600 font-mono">
                                    @if($band['max'] === null) ₱{{ number_format($band['min']) }} and above
                                    @elseif($band['min'] == 0) below ₱{{ number_format($band['max']) }}
                                    @else ₱{{ number_format($band['min']) }} – below ₱{{ number_format($band['max']) }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </table>
                    </div>
                    @endif
                </div>
                <div class="px-5 pb-5">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save PSA Thresholds
                    </button>
                </div>
            </form>
        </div>

        {{-- Per Capita Thresholds --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 text-xs">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Barangay Welfare Score Thresholds</p>
                    <p class="text-xs text-gray-400 mt-0.5">Monthly per-person income ceiling for each tier of the barangay's own welfare score (not PSA)</p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="_thresholds" value="1">
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                        @php
                            $tiers = [
                                ['key'=>'per_capita_extremely_poor','label'=>'Extremely Poor','color'=>'bg-red-500',   'default'=>1500],
                                ['key'=>'per_capita_poor',          'label'=>'Poor',          'color'=>'bg-orange-500','default'=>2500],
                                ['key'=>'per_capita_near_poor',     'label'=>'Near Poor',     'color'=>'bg-yellow-500','default'=>3500],
                                ['key'=>'per_capita_vulnerable',    'label'=>'Vulnerable',    'color'=>'bg-blue-500',  'default'=>5000],
                            ];
                        @endphp
                        @foreach($tiers as $tier)
                        <div>
                            <label class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                                <span class="h-2 w-2 rounded-full {{ $tier['color'] }}"></span>
                                {{ $tier['label'] }} — up to (₱)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm font-semibold">₱</span>
                                <input type="number" name="{{ $tier['key'] }}"
                                       value="{{ old($tier['key'], \App\Models\Setting::get($tier['key'], $tier['default'])) }}"
                                       min="0" step="1" required
                                       class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-8 pr-3.5 py-2.5 text-sm text-gray-900
                                              focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="rounded-xl bg-purple-50 border border-purple-100 px-4 py-3 text-xs text-purple-700 mb-4">
                        <p class="font-semibold flex items-center gap-1.5 mb-1"><i class="fa-solid fa-circle-info"></i> How it works</p>
                        <p>Per Capita = Total Household Income ÷ No. of Members. Each tier's threshold is the <strong>upper limit</strong> — families at or below that value fall into that category. Non-Poor means above the Vulnerable threshold.</p>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save Thresholds
                    </button>
                </div>
            </form>
        </div>

        {{-- GCash Payment Details --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600 text-xs">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">GCash Payment Details</p>
                    <p class="text-xs text-gray-400 mt-0.5">Shown to residents when requesting a paid document</p>
                </div>
            </div>
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_gcash" value="1">
                <div class="p-5 space-y-4">

                    {{-- How-to instructions --}}
                    <div class="rounded-xl bg-blue-50 border border-blue-100 p-4 space-y-2.5">
                        <p class="text-xs font-semibold text-blue-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-info"></i> What residents will see
                        </p>
                        <ol class="text-xs text-blue-700 space-y-1.5 list-none">
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">1</span>
                                <span>Your GCash QR code and/or number when they request a document with a fee.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">2</span>
                                <span>They scan the QR or send payment manually, then save their GCash payment confirmation.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="flex-shrink-0 flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-800 mt-0.5">3</span>
                                <span>They upload that screenshot as proof of payment, for you to verify here in Documents.</span>
                            </li>
                        </ol>
                    </div>

                    {{-- QR upload --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">QR Code Image</label>
                        <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 space-y-3">
                            @if($gcashQrUrl)
                            <div class="flex items-center gap-3">
                                <img src="{{ $gcashQrUrl }}" alt="GCash QR Code" class="h-16 w-16 rounded-lg border border-gray-200 bg-white object-contain">
                                <p class="text-xs text-green-600 font-medium flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check"></i> QR code uploaded
                                </p>
                            </div>
                            @else
                            <div class="flex items-center gap-3 text-gray-400">
                                <i class="fa-solid fa-qrcode text-2xl"></i>
                                <p class="text-xs">No QR code uploaded yet. Residents will only see your GCash number below.</p>
                            </div>
                            @endif
                            <div>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 cursor-pointer hover:bg-gray-50 transition-colors">
                                    <i class="fa-solid fa-upload text-xs"></i> {{ $gcashQrUrl ? 'Replace QR Code' : 'Upload QR Code' }}
                                    <input type="file" name="gcash_qr_image" class="hidden" accept="image/*">
                                </label>
                                <p class="mt-1.5 text-[11px] text-gray-400">PNG or JPG · Max 2MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- GCash number / account name --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">GCash Number</label>
                            <input type="text" name="gcash_number" value="{{ old('gcash_number', $gcashNumber) }}"
                                   placeholder="09XX XXX XXXX"
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                          focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Account Name</label>
                            <input type="text" name="gcash_account_name" value="{{ old('gcash_account_name', $gcashAccountName) }}"
                                   placeholder="As registered on GCash"
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                          focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                        </div>
                    </div>

                </div>
                <div class="px-5 pb-5">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save GCash Details
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
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="_fees" value="1">
                @php
                    $feeRows = [
                        ['key' => 'fee_barangay_clearance',       'type' => 'Barangay Clearance',       'icon' => 'fa-file-shield',        'color' => 'bg-brand-50 text-brand-600'],
                        ['key' => 'fee_certificate_of_residency', 'type' => 'Certificate of Residency', 'icon' => 'fa-house-flag',         'color' => 'bg-blue-50 text-blue-600'],
                        ['key' => 'fee_certificate_of_indigency', 'type' => 'Certificate of Indigency', 'icon' => 'fa-hand-holding-heart', 'color' => 'bg-orange-50 text-orange-500'],
                        ['key' => 'fee_business_clearance',       'type' => 'Business Clearance',       'icon' => 'fa-briefcase',          'color' => 'bg-purple-50 text-purple-600'],
                    ];
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                                <th class="px-5 py-3.5">Document Type</th>
                                <th class="px-5 py-3.5">Fee (₱)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($feeRows as $f)
                            <tr class="odd:bg-white even:bg-gray-50/70 border-b border-gray-100 last:border-0">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-lg {{ $f['color'] }} text-xs">
                                            <i class="fa-solid {{ $f['icon'] }}"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-800">{{ $f['type'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="relative w-32">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-sm font-semibold">₱</span>
                                        <input type="number" name="{{ $f['key'] }}"
                                               value="{{ old($f['key'], $docFees[$f['key']]) }}"
                                               min="0" step="0.01" required
                                               class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-7 pr-3 py-1.5 text-sm text-gray-900
                                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save Document Fees
                    </button>
                </div>
            </form>
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
                @foreach($systemInfo as $i)
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
