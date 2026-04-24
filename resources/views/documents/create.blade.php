@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', isset($document) ? 'Edit Document' : 'Issue Document')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">{{ isset($document) ? 'Edit Document Request' : 'Issue New Document' }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ isset($document) ? 'Update document details' : 'Fill in the form to issue a barangay document' }}</p>
    </div>
</div>

<form action="{{ isset($document) ? route($isStaff ? 'staff.documents.update' : 'documents.update', $document->id) : route($isStaff ? 'staff.documents.store' : 'documents.store') }}" method="POST">
@csrf
@if(isset($document))
@method('PUT')
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Document Type --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Document Details</p>
            </div>
            <div class="p-5 space-y-4">

                {{-- Document Type Selector Cards --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Document Type <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @php
                            $docTypes = [
                                ['value' => 'Barangay Clearance',       'fee' => 50,  'icon' => 'fa-file-shield',   'color' => 'bg-brand-50 text-brand-600 border-brand-200'],
                                ['value' => 'Certificate of Residency', 'fee' => 50,  'icon' => 'fa-house-flag',    'color' => 'bg-blue-50 text-blue-600 border-blue-200'],
                                ['value' => 'Certificate of Indigency', 'fee' => 0,   'icon' => 'fa-hand-holding-heart', 'color' => 'bg-orange-50 text-orange-600 border-orange-200'],
                                ['value' => 'Business Clearance',       'fee' => 200, 'icon' => 'fa-briefcase',     'color' => 'bg-purple-50 text-purple-600 border-purple-200'],
                            ];
                        @endphp
                        @foreach($docTypes as $dt)
                        <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-gray-200 bg-white p-3.5 cursor-pointer transition-all
                                      has-[:checked]:border-green-500 has-[:checked]:bg-green-50 hover:border-gray-300">
                            <input type="radio" name="document_type" value="{{ $dt['value'] }}" data-fee="{{ $dt['fee'] }}"
                                   class="sr-only" {{ (old('document_type', isset($document) ? $document->document_type : '')) === $dt['value'] ? 'checked' : '' }}>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $dt['color'] }} border text-sm transition-all">
                                <i class="fa-solid {{ $dt['icon'] }}"></i>
                            </div>
                            <span class="text-[11px] font-semibold text-gray-700 text-center leading-tight group-has-[:checked]:text-green-700">
                                {{ $dt['value'] }}
                            </span>
                            <span class="text-[10px] text-gray-400 group-has-[:checked]:text-green-600">
                                ₱{{ number_format($dt['fee']) }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    {{-- Hidden select fallback for form submission --}}
                    <select name="document_type_select" id="document_type" class="hidden">
                        @foreach($docTypes as $dt)
                        <option value="{{ $dt['value'] }}" data-fee="{{ $dt['fee'] }}">{{ $dt['value'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Resident Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Resident Name <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm pointer-events-none z-10">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="resident_search" placeholder="Search resident by name..."
                               value="{{ isset($document) ? ($document->resident?->full_name ?? '') : '' }}"
                               autocomplete="off"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                        <input type="hidden" name="resident_id" id="resident_id"
                               value="{{ old('resident_id', isset($document) ? $document->resident_id : '') }}">
                        <ul id="resident_dropdown"
                            class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg hidden text-sm"></ul>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Type to search from registered residents</p>
                </div>

                {{-- Business Fields (shown only for Business Clearance) --}}
                <div id="business_fields" class="{{ (old('document_type', isset($document) ? $document->document_type : '') === 'Business Clearance') ? '' : 'hidden' }} space-y-4 border border-purple-100 bg-purple-50/40 rounded-xl p-4">
                    <p class="text-xs font-bold text-purple-700 uppercase tracking-wide">Business Information</p>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            Business Name <span class="text-red-500 normal-case font-normal">*</span>
                        </label>
                        <input type="text" name="business_name" value="{{ old('business_name', isset($document) ? $document->business_name : '') }}"
                               placeholder="e.g. Juan's Sari-sari Store"
                               class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            Type of Business <span class="text-red-500 normal-case font-normal">*</span>
                        </label>
                        <select name="business_type"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900
                                       focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition-all">
                            <option value="">Select type…</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Retail/Sari-sari Store' ? 'selected' : '' }}>Retail/Sari-sari Store</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Food & Beverage' ? 'selected' : '' }}>Food & Beverage</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Service' ? 'selected' : '' }}>Service</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Agriculture' ? 'selected' : '' }}>Agriculture</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Trading' ? 'selected' : '' }}>Trading</option>
                            <option {{ old('business_type', $document->business_type ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Business Address</label>
                        <input type="text" name="business_address" value="{{ old('business_address', isset($document) ? $document->business_address : '') }}"
                               placeholder="e.g. Purok 2, Barangay Caranas"
                               class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition-all placeholder-gray-400">
                    </div>
                </div>

                {{-- Purpose --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Purpose <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <input type="text" name="purpose" value="{{ old('purpose', isset($document) ? $document->purpose : '') }}"
                           placeholder="e.g. For employment, For travel, For school"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                </div>
            </div>
        </div>

        {{-- Payment & Records --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-50 text-green-600 text-xs">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Payment & Records</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">OR Number</label>
                    <div class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm flex items-center gap-2">
                        @if(isset($document) && $document->or_number)
                            <i class="fa-solid fa-receipt text-green-600 text-xs"></i>
                            <span class="font-semibold text-gray-800">{{ $document->or_number }}</span>
                        @else
                            <i class="fa-solid fa-clock-rotate-left text-gray-400 text-xs"></i>
                            <span class="text-gray-400 italic">Auto-generated upon issuance</span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Fee Amount (₱)</label>
                    <input type="number" name="fee" id="fee_amount" value="{{ old('fee', isset($document) ? $document->fee : '0') }}" min="0" step="0.01"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    <p class="text-[11px] text-gray-400 mt-1">Auto-filled based on document type</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Issued By</label>
                    <input type="text" name="issued_by" value="{{ Auth::user()->name ?? 'Admin' }}" readonly
                           class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-400 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date Issued</label>
                    <input type="date" name="date_issued" value="{{ old('date_issued', isset($document) ? $document->issued_at?->format('Y-m-d') : date('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Action Card --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-print"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Issue Document</p>
            </div>
            <div class="p-5 space-y-3">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-print text-xs"></i> {{ isset($document) ? 'Update Document' : 'Issue & Print' }}
                </button>
                <a href="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </div>

        {{-- Fee Summary --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Fee Summary</p>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-500">Document Fee</span>
                    <span class="text-sm font-bold text-gray-900" id="fee_display">₱0.00</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-xs text-gray-500">Processing Fee</span>
                    <span class="text-sm text-gray-400">₱0.00</span>
                </div>
                <div class="flex items-center justify-between pt-3 mt-1 border-t border-gray-100">
                    <span class="text-xs font-semibold text-gray-700">Total</span>
                    <span class="text-base font-bold text-brand-600" id="total_display">₱0.00</span>
                </div>
            </div>
        </div>

        {{-- Info --}}
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info"></i> Note
            </p>
            <ul class="text-xs text-blue-600 space-y-1.5 list-disc list-inside">
                <li>Certificate of Indigency is free of charge.</li>
                <li>Official receipts must be issued for all paid documents.</li>
                <li>Documents are valid for 6 months from issue date.</li>
            </ul>
        </div>

    </div>
</div>

</form>

<script>
// Update fee + show/hide business fields when radio card is selected
document.querySelectorAll('input[name="document_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const fee = parseFloat(this.getAttribute('data-fee') || 0);
        document.getElementById('fee_amount').value = fee;
        document.getElementById('fee_display').textContent = '₱' + fee.toFixed(2);
        document.getElementById('total_display').textContent = '₱' + fee.toFixed(2);

        const businessFields = document.getElementById('business_fields');
        businessFields.classList.toggle('hidden', this.value !== 'Business Clearance');
    });
});

// Resident autocomplete
const residents = @json($residents->map(fn($r) => ['id' => $r->id, 'name' => $r->full_name]));
const searchInput  = document.getElementById('resident_search');
const hiddenId     = document.getElementById('resident_id');
const dropdown     = document.getElementById('resident_dropdown');

searchInput.addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    hiddenId.value = '';
    if (!q) { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; return; }
    const matches = residents.filter(r => r.name.toLowerCase().includes(q)).slice(0, 10);
    if (!matches.length) { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; return; }
    dropdown.innerHTML = matches.map(r =>
        `<li class="px-4 py-2.5 cursor-pointer hover:bg-green-50 hover:text-green-700 transition-colors"
             data-id="${r.id}" data-name="${r.name}">${r.name}</li>`
    ).join('');
    dropdown.classList.remove('hidden');
});

dropdown.addEventListener('click', function (e) {
    const li = e.target.closest('li');
    if (!li) return;
    searchInput.value = li.dataset.name;
    hiddenId.value    = li.dataset.id;
    dropdown.classList.add('hidden');
    dropdown.innerHTML = '';
});

document.addEventListener('click', function (e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>

@endsection
