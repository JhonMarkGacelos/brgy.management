@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'View Document Request')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">Document Request Details</h2>
        <p class="text-xs text-gray-400 mt-0.5">Tracking #{{ $document->tracking_number }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Document Details --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Document Details</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Tracking Number</label>
                    <p class="text-sm font-mono text-gray-900">{{ $document->tracking_number }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Document Type</label>
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $document->document_type === 'Barangay Clearance' ? 'bg-blue-100 text-blue-700' : ($document->document_type === 'Certificate of Residency' ? 'bg-green-100 text-green-700' : ($document->document_type === 'Certificate of Indigency' ? 'bg-yellow-100 text-yellow-700' : 'bg-purple-100 text-purple-700')) }}">
                        {{ $document->document_type }}
                    </span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Resident</label>
                    <p class="text-sm text-gray-900">{{ $document->resident?->full_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Purpose</label>
                    <p class="text-sm text-gray-900">{{ $document->purpose }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Requested By</label>
                    <p class="text-sm text-gray-900">{{ $document->requestedBy->name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date Requested</label>
                    <p class="text-sm text-gray-900">{{ $document->created_at->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        {{-- ID Photo Verification --}}
        @if($document->id_photo_url)
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 text-xs">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Valid ID</p>
                </div>
                @php
                    $verifiedBadge = match($document->id_verified) {
                        'verified' => ['bg-green-100 text-green-700',  'fa-circle-check',       'Verified'],
                        'rejected' => ['bg-red-100 text-red-700',      'fa-circle-xmark',       'Rejected'],
                        default    => ['bg-yellow-100 text-yellow-700','fa-clock',              'Pending Verification'],
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $verifiedBadge[0] }}">
                    <i class="fa-solid {{ $verifiedBadge[1] }} text-[10px]"></i>
                    {{ $verifiedBadge[2] }}
                </span>
            </div>
            <div class="p-4">
                <a href="{{ $document->id_photo_url }}" target="_blank">
                    <img src="{{ $document->id_photo_url }}" alt="ID Photo"
                         class="w-full rounded-xl border border-gray-200 object-contain max-h-56 bg-gray-50 hover:opacity-90 transition-opacity cursor-zoom-in">
                </a>
                <p class="text-[11px] text-gray-400 mt-2 text-center">Click image to view full size</p>
            </div>
            @if($document->id_verified === 'pending')
            <div class="px-4 pb-4 grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route($isStaff ? 'staff.documents.verify-id' : 'documents.verify-id', $document->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="verify">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 transition-colors">
                        <i class="fa-solid fa-check text-[10px]"></i> Verify ID
                    </button>
                </form>
                <form method="POST" action="{{ route($isStaff ? 'staff.documents.verify-id' : 'documents.verify-id', $document->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Reject ID
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endif

        {{-- Payment Receipt Verification --}}
        @if($document->payment_receipt_url)
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600 text-xs">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Payment Receipt</p>
                </div>
                @php
                    $paymentBadge = match($document->payment_verified) {
                        'verified' => ['bg-green-100 text-green-700',  'fa-circle-check',       'Verified'],
                        'rejected' => ['bg-red-100 text-red-700',      'fa-circle-xmark',       'Rejected'],
                        default    => ['bg-yellow-100 text-yellow-700','fa-clock',              'Pending Verification'],
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentBadge[0] }}">
                    <i class="fa-solid {{ $paymentBadge[1] }} text-[10px]"></i>
                    {{ $paymentBadge[2] }}
                </span>
            </div>
            <div class="p-4">
                <a href="{{ $document->payment_receipt_url }}" target="_blank">
                    <img src="{{ $document->payment_receipt_url }}" alt="Payment Receipt"
                         class="w-full rounded-xl border border-gray-200 object-contain max-h-56 bg-gray-50 hover:opacity-90 transition-opacity cursor-zoom-in">
                </a>
                <p class="text-[11px] text-gray-400 mt-2 text-center">Click image to view full size</p>
            </div>
            @if($document->payment_verified === 'pending')
            <div class="px-4 pb-4 grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route($isStaff ? 'staff.documents.verify-payment' : 'documents.verify-payment', $document->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="verify">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 transition-colors">
                        <i class="fa-solid fa-check text-[10px]"></i> Verify Payment
                    </button>
                </form>
                <form method="POST" action="{{ route($isStaff ? 'staff.documents.verify-payment' : 'documents.verify-payment', $document->id) }}">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Reject Payment
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endif

        {{-- Payment & Status --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-50 text-green-600 text-xs">
                    <i class="fa-solid fa-peso-sign"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Payment & Status</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">OR Number</label>
                    <p class="text-sm text-gray-900">{{ $document->or_number ?: 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Fee Amount</label>
                    <p class="text-sm text-gray-900">{{ $document->fee ? '₱'.number_format($document->fee, 2) : 'Free' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $document->status === 'Issued' ? 'bg-green-100 text-green-700' : ($document->status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : ($document->status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')) }}">
                        {{ $document->status }}
                    </span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Processed By</label>
                    <p class="text-sm text-gray-900">{{ $document->processedBy?->name ?? 'N/A' }}</p>
                </div>
                @if($document->issued_at)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date Issued</label>
                    <p class="text-sm text-gray-900">{{ $document->issued_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
                @if($document->remarks)
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Remarks</label>
                    <p class="text-sm text-gray-900 whitespace-pre-line">{{ $document->remarks }}</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Actions --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-50 text-gray-500 text-xs">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Actions</p>
            </div>
            <div class="p-5 space-y-3">

                {{-- Print & Issue (available to both staff and admin) --}}
                @if($document->status !== 'Rejected')
                <a href="{{ route($isStaff ? 'staff.documents.print' : 'documents.print', $document->id) }}"
                   target="_blank"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                   style="background-color:#1a4731;"
                   onmouseover="this.style.backgroundColor='#2d6a4f'"
                   onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-print text-xs"></i>
                    {{ $document->status === 'Issued' ? 'Reprint ' : 'Print & Issue ' }}{{ $document->document_type }}
                </a>
                @if($document->or_number)
                <div class="flex items-center gap-2 rounded-xl bg-gray-50 border border-gray-200 px-3 py-2.5">
                    <i class="fa-solid fa-barcode text-gray-400 text-xs"></i>
                    <span class="text-xs font-mono text-gray-700 flex-1">{{ $document->or_number }}</span>
                    <span class="text-[10px] text-green-600 font-semibold bg-green-50 rounded-full px-2 py-0.5">OR No.</span>
                </div>
                @endif
                @endif

                {{-- Reject (staff + admin) --}}
                @if(!in_array($document->status, ['Issued', 'Rejected']))
                <form method="POST" action="{{ route($isStaff ? 'staff.documents.update' : 'documents.update', $document->id) }}"
                      onsubmit="return confirm('Reject this document request?')">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="Rejected">
                    <div class="mb-2">
                        <textarea name="remarks" rows="2" placeholder="Reason for rejection (optional)…"
                                  class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors border border-red-200">
                        <i class="fa-solid fa-circle-xmark text-xs"></i> Reject Request
                    </button>
                </form>
                @endif

                {{-- Admin only: delete --}}
                @if(!$isStaff)
                <form action="{{ route('documents.destroy', $document->id) }}" method="POST" onsubmit="return confirm('Delete this document request?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i> Delete Request
                    </button>
                </form>
                @endif

            </div>
        </div>

        {{-- Edit OR Number & Purpose --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Edit Details</p>
            </div>
            <form method="POST" action="{{ route($isStaff ? 'staff.documents.update' : 'documents.update', $document->id) }}">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="{{ $document->status }}">
                <input type="hidden" name="edit_details" value="1">
                <div class="p-5 space-y-4">
                    <div class="rounded-xl border-2 {{ $document->or_number ? 'border-gray-200 bg-gray-50' : 'border-amber-400 bg-amber-50' }} p-3">
                        <label class="block text-xs font-semibold uppercase tracking-wide mb-1.5 {{ $document->or_number ? 'text-gray-500' : 'text-amber-700' }}">
                            OR Number
                            @unless($document->or_number)
                                <span class="ml-1 normal-case font-normal text-amber-600"><i class="fa-solid fa-triangle-exclamation text-xs"></i> Required before issuing</span>
                            @endunless
                        </label>
                        <input type="text" name="or_number" value="{{ $document->or_number }}"
                               placeholder="Enter OR Number"
                               class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 transition-all placeholder-gray-400
                                      {{ $document->or_number
                                          ? 'border-gray-200 bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20'
                                          : 'border-amber-300 bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-400/20' }}
                                      focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Purpose</label>
                        <input type="text" name="purpose" value="{{ $document->purpose }}"
                               placeholder="e.g. For employment, For travel"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400">
                    </div>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-floppy-disk text-xs"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

@endsection