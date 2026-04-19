@extends('layouts.staff')
@section('title', 'Request Document')

@section('content')

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('staff.documents.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">New Document Request</h2>
        <p class="text-xs text-gray-400 mt-0.5">Look up the resident and submit for official approval</p>
    </div>
</div>

<form action="{{ route('staff.documents.store') }}" method="POST">
@csrf

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Step 1: Document Type --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold">1</div>
                <p class="text-sm font-semibold text-gray-800">Select Document Type</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    @php
                        $docTypes = [
                            ['value' => 'Barangay Clearance',       'fee' => 50,  'icon' => 'fa-file-shield',        'color' => 'bg-brand-50 text-brand-600 border-brand-200'],
                            ['value' => 'Certificate of Residency', 'fee' => 50,  'icon' => 'fa-house-flag',         'color' => 'bg-blue-50 text-blue-600 border-blue-200'],
                            ['value' => 'Certificate of Indigency', 'fee' => 0,   'icon' => 'fa-hand-holding-heart', 'color' => 'bg-orange-50 text-orange-600 border-orange-200'],
                            ['value' => 'Business Clearance',       'fee' => 200, 'icon' => 'fa-briefcase',          'color' => 'bg-purple-50 text-purple-600 border-purple-200'],
                        ];
                    @endphp
                    @foreach($docTypes as $dt)
                    <label class="group relative flex flex-col items-center gap-2 rounded-xl border-2 border-gray-200 bg-white p-3.5 cursor-pointer transition-all
                                  has-[:checked]:border-green-600 has-[:checked]:bg-green-50 hover:border-gray-300">
                        <input type="radio" name="document_type" value="{{ $dt['value'] }}" data-fee="{{ $dt['fee'] }}"
                               class="sr-only" {{ old('document_type') === $dt['value'] ? 'checked' : '' }}>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $dt['color'] }} border text-sm transition-all">
                            <i class="fa-solid {{ $dt['icon'] }}"></i>
                        </div>
                        <span class="text-[11px] font-semibold text-gray-700 text-center leading-tight group-has-[:checked]:text-green-700">
                            {{ $dt['value'] }}
                        </span>
                        <span class="text-[10px] text-gray-400 group-has-[:checked]:text-green-600">
                            {{ $dt['fee'] > 0 ? '₱'.number_format($dt['fee']) : 'Free' }}
                        </span>
                        <i class="fa-solid fa-circle-check absolute top-2 right-2 text-green-600 text-xs opacity-0 group-has-[:checked]:opacity-100 transition-opacity"></i>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Step 2: Resident Lookup --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold">2</div>
                <p class="text-sm font-semibold text-gray-800">Look Up Resident in Profiling Database</p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Resident Name <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" name="resident_name" id="resident_search" value="{{ old('resident_name') }}"
                               placeholder="Type to search from registered residents..."
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400" required>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">
                        <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i>
                        If the resident is not found, go to <a href="{{ route('staff.residents.create') }}" class="text-green-700 hover:underline font-medium">Residents</a> to register them first.
                    </p>
                </div>

                {{-- Resident Found Preview (shown after search) --}}
                <div id="resident_preview" class="hidden rounded-xl border border-green-200 bg-green-50 p-3.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-200 text-green-700 text-sm font-bold">JC</div>
                        <div>
                            <p class="text-sm font-semibold text-green-900">Juan dela Cruz</p>
                            <p class="text-xs text-green-700">Purok 1 &middot; Male &middot; Age 34 &middot; Voter</p>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-200 text-green-800 text-[10px] font-bold px-2 py-0.5">
                                <i class="fa-solid fa-circle-check text-[9px]"></i> Resident Found
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Purpose <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <input type="text" name="purpose" value="{{ old('purpose') }}"
                           placeholder="e.g. For employment, For travel, For school enrollment"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400" required>
                </div>
            </div>
        </div>

        {{-- Step 3: Request Details --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold">3</div>
                <p class="text-sm font-semibold text-gray-800">Request Details</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Tracking Number</label>
                    <input type="text" name="tracking_number" value="{{ old('tracking_number', 'OR-2024-088') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-400 cursor-not-allowed font-mono">
                    <p class="text-[11px] text-gray-400 mt-1">Auto-generated</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Fee Amount (₱)</label>
                    <input type="number" name="fee" id="fee_amount" value="{{ old('fee', '0') }}" min="0" step="0.01"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    <p class="text-[11px] text-gray-400 mt-1">Auto-filled based on document type</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Requested By (Staff)</label>
                    <input type="text" name="requested_by" value="{{ Auth::user()->name ?? 'Staff' }}" readonly
                           class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3.5 py-2.5 text-sm text-gray-400 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date of Request</label>
                    <input type="date" name="date_requested" value="{{ old('date_requested', date('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Submit for Approval --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Submit Request</p>
            </div>
            <div class="p-5 space-y-3">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-paper-plane text-xs"></i> Submit for Official Approval
                </button>
                <a href="{{ route('staff.documents.index') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
                <p class="text-[11px] text-gray-400 text-center leading-relaxed pt-1">
                    Request will be set to <strong class="text-amber-600">Pending Official</strong>. You'll be notified once approved.
                </p>
            </div>
        </div>

        {{-- Approval Workflow --}}
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
            <p class="text-xs font-semibold text-amber-700 mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-diagram-project"></i> Document Approval Flow
            </p>
            <div class="space-y-2">
                @php
                    $steps = [
                        ['label' => 'Staff Submits Request',      'icon' => 'fa-user-pen',     'active' => true,  'done' => false],
                        ['label' => 'Official Reviews & Approves','icon' => 'fa-user-tie',     'active' => false, 'done' => false],
                        ['label' => 'Document Generated',         'icon' => 'fa-file-circle-check','active' => false, 'done' => false],
                        ['label' => 'Staff Issues to Requestor',  'icon' => 'fa-print',        'active' => false, 'done' => false],
                    ];
                @endphp
                @foreach($steps as $i => $step)
                <div class="flex items-center gap-2.5">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px]
                                {{ $step['active'] ? 'bg-amber-500 text-white' : 'bg-white border border-amber-200 text-amber-400' }}">
                        @if($step['active'])
                            <i class="fa-solid {{ $step['icon'] }}"></i>
                        @else
                            <span class="font-bold">{{ $i + 1 }}</span>
                        @endif
                    </div>
                    <span class="text-xs {{ $step['active'] ? 'font-semibold text-amber-800' : 'text-amber-600' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
                @if(!$loop->last)
                <div class="ml-3 h-3 w-px bg-amber-200"></div>
                @endif
                @endforeach
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

        {{-- Notes --}}
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info"></i> Notes
            </p>
            <ul class="text-xs text-blue-600 space-y-1.5 list-disc list-inside">
                <li>Resident must be registered in the profiling database.</li>
                <li>Certificate of Indigency is free of charge.</li>
                <li>Official receipts must be issued for all paid documents.</li>
                <li>Documents are valid for 6 months from date of issuance.</li>
            </ul>
        </div>

    </div>
</div>

</form>

<script>
document.querySelectorAll('input[name="document_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const fee = parseFloat(this.getAttribute('data-fee') || 0);
        document.getElementById('fee_amount').value = fee;
        document.getElementById('fee_display').textContent = fee > 0 ? '₱' + fee.toFixed(2) : 'Free';
        document.getElementById('total_display').textContent = fee > 0 ? '₱' + fee.toFixed(2) : 'Free';
    });
});

// Simulate resident found preview on input
document.getElementById('resident_search').addEventListener('input', function() {
    const preview = document.getElementById('resident_preview');
    if (this.value.length > 2) {
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
});
</script>

@endsection
