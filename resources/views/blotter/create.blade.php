@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', isset($record) ? 'Edit Blotter' : 'File Blotter')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.blotter.index' : 'blotter.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">{{ isset($record) ? 'Edit Blotter Case' : 'File New Blotter' }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ isset($record) ? 'Update case details' : 'Record a new incident or complaint' }}</p>
    </div>
</div>

<form action="{{ isset($record) ? route($isStaff ? 'staff.blotter.update' : 'blotter.update', $record->id) : route($isStaff ? 'staff.blotter.store' : 'blotter.store') }}" method="POST">
@csrf
@if(isset($record))
@method('PUT')
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column (main fields) --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Case Information --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 text-xs">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Case Information</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Case Number</label>
                    <input type="text" name="case_number" value="{{ isset($record) ? $record->case_number : $nextCaseNo }}" readonly
                           class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-400 cursor-not-allowed">
                    <p class="text-[11px] text-gray-400 mt-1">Auto-generated</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Date of Incident <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <input type="date" name="incident_date" value="{{ old('incident_date', isset($record) ? $record->incident_date->format('Y-m-d') : date('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Time of Incident</label>
                    <input type="time" name="incident_time" value="{{ old('incident_time', isset($record) ? $record->incident_time : '') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Incident Type <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <select name="incident_type"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                        <option value="">Select type</option>
                        <option value="Noise Complaint" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Noise Complaint' ? 'selected' : '' }}>Noise Complaint</option>
                        <option value="Physical Altercation" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Physical Altercation' ? 'selected' : '' }}>Physical Altercation</option>
                        <option value="Property Dispute" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Property Dispute' ? 'selected' : '' }}>Property Dispute</option>
                        <option value="Theft" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Theft' ? 'selected' : '' }}>Theft</option>
                        <option value="Domestic" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Domestic' ? 'selected' : '' }}>Domestic</option>
                        <option value="Others" {{ (old('incident_type', isset($record) ? $record->incident_type : '')) === 'Others' ? 'selected' : '' }}>Others</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Location of Incident <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <input type="text" name="location" value="{{ old('location', isset($record) ? $record->location : '') }}"
                           placeholder="e.g. Purok 3, near the basketball court"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                </div>
            </div>
        </div>

        {{-- Complainant & Respondent --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Complainant --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Complainant</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            Full Name <span class="text-red-500 normal-case font-normal">*</span>
                        </label>
                        <input type="text" name="complainant_name" value="{{ old('complainant_name', isset($record) ? $record->complainant_name : '') }}"
                               placeholder="Full name"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Address</label>
                        <input type="text" name="complainant_address" value="{{ old('complainant_address', isset($record) ? $record->complainant_address : '') }}"
                               placeholder="Purok / Sitio"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                        <input type="text" name="complainant_contact" value="{{ old('complainant_contact', isset($record) ? $record->complainant_contact : '') }}"
                               placeholder="09xx-xxx-xxxx"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                </div>
            </div>

            {{-- Respondent --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-500 text-xs">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Respondent</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            Full Name <span class="text-red-500 normal-case font-normal">*</span>
                        </label>
                        <input type="text" name="respondent_name" value="{{ old('respondent_name', isset($record) ? $record->respondent_name : '') }}"
                               placeholder="Full name"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Address</label>
                        <input type="text" name="respondent_address" value="{{ old('respondent_address', isset($record) ? $record->respondent_address : '') }}"
                               placeholder="Purok / Sitio"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                        <input type="text" name="respondent_contact" value="{{ old('respondent_contact', isset($record) ? $record->respondent_contact : '') }}"
                               placeholder="09xx-xxx-xxxx"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                </div>
            </div>
        </div>

        {{-- Incident Details --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 text-xs">
                    <i class="fa-solid fa-paragraph"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Incident Details</p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Narrative / Description <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <textarea name="narrative" rows="5" placeholder="Describe the incident in detail..."
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                     focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all resize-none placeholder-gray-400">{{ old('narrative', isset($record) ? $record->narrative : '') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Witnesses <span class="text-gray-400 normal-case font-normal">(optional)</span>
                    </label>
                    <input type="text" name="witnesses" value="{{ old('witnesses', isset($record) ? $record->witnesses : '') }}"
                           placeholder="Names of witnesses, separated by comma"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Action Taken <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <textarea name="action_taken" rows="3" placeholder="e.g. Parties were called for mediation, verbal warning issued..."
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                     focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all resize-none placeholder-gray-400 @error('action_taken') border-red-400 @enderror">{{ old('action_taken', isset($record) ? $record->action_taken : '') }}</textarea>
                    @error('action_taken')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Remarks <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                    <textarea name="remarks" rows="2" placeholder="Additional remarks..."
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                     focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all resize-none placeholder-gray-400">{{ old('remarks', isset($record) ? $record->remarks : '') }}</textarea>
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Submit Card --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Submit Case</p>
            </div>
            <div class="p-5 space-y-3">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#dc2626;"
                        onmouseover="this.style.backgroundColor='#b91c1c'"
                        onmouseout="this.style.backgroundColor='#dc2626'">
                    <i class="fa-solid fa-shield-halved text-xs"></i> {{ isset($record) ? 'Update Blotter' : 'Submit Blotter' }}
                </button>
                <a href="{{ route($isStaff ? 'staff.blotter.index' : 'blotter.index') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </div>

        {{-- Status Card --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Case Status</p>
            </div>
            <div class="p-5">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Initial Status</label>
                <select name="status"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                               focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    <option value="Open" {{ (old('status', isset($record) ? $record->status : 'Open')) === 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="Under Mediation" {{ (old('status', isset($record) ? $record->status : '')) === 'Under Mediation' ? 'selected' : '' }}>Under Mediation</option>
                    <option value="Settled" {{ (old('status', isset($record) ? $record->status : '')) === 'Settled' ? 'selected' : '' }}>Settled</option>
                    <option value="Referred" {{ (old('status', isset($record) ? $record->status : '')) === 'Referred' ? 'selected' : '' }}>Referred to Higher Authority</option>
                </select>
                <p class="text-[11px] text-gray-400 mt-2">New cases are typically filed as <strong>Open</strong>.</p>
            </div>
        </div>

        {{-- Tips Card --}}
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-lightbulb"></i> Reminders
            </p>
            <ul class="text-xs text-blue-600 space-y-1.5 list-disc list-inside">
                <li>Ensure all required fields are filled.</li>
                <li>Include as much detail in the narrative as possible.</li>
                <li>Both parties must be notified within 24 hours.</li>
                <li>Attach supporting documents if available.</li>
            </ul>
        </div>

    </div>
</div>

</form>
@endsection
