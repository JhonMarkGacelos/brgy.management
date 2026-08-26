@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Fee Collected')

@push('styles')
<style>
@media screen {
    .print-header { display: none; }
}

@media print {
    .no-print { display: none !important; }

    body, html {
        background: #fff !important;
        margin: 0 !important; padding: 0 !important;
        height: auto !important; overflow: visible !important;
        font-size: 9pt !important;
    }
    body > div, .flex.h-screen {
        display: block !important;
        height: auto !important; overflow: visible !important;
    }
    aside, nav, header { display: none !important; }
    .print-main {
        padding: 0 !important; margin: 0 !important;
        width: 100% !important; min-height: auto !important;
        overflow: visible !important;
    }
    main { padding: 4px !important; overflow: visible !important; }

    .print-header { display: block !important; }
    .print-header h1 { font-size: 13pt !important; }
    .print-header h2 { font-size: 10pt !important; }
    .print-header p  { font-size: 8pt !important; }

    table { font-size: 8pt !important; }
    thead { display: table-header-group; }
    tr { break-inside: avoid; }
    thead tr {
        background: #fff !important;
        border-bottom: 2px solid #111 !important;
    }
    thead tr th { color: #111 !important; }

    @page { size: A4 landscape; margin: 1cm; }
}
</style>
@endpush

@section('content')
@php
    $isStaff = Auth::user()->role === 'staff';
    $hasFilters = request('search') || request('type') || request('month') || request('date');
    $docTypeOptions = ['Barangay Clearance','Certificate of Residency','Certificate of Indigency','Business Clearance'];
@endphp

{{-- Print-only letterhead --}}
<div class="print-header text-center mb-6 pb-4 border-b-2 border-gray-800">
    <p class="text-xs text-gray-500 uppercase tracking-widest">Republic of the Philippines · Province of Samar · Municipality of Motiong</p>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Barangay Caranas</h1>
    <h2 class="text-base font-semibold text-gray-700 mt-0.5">Fee Collected Ledger</h2>
    @if($hasFilters)
    <p class="text-xs text-gray-600 mt-1 font-medium">
        Filters:
        @if(request('search')) Search "{{ request('search') }}" @endif
        @if(request('type')) &nbsp;·&nbsp; Type: {{ request('type') }} @endif
        @if(request('date')) &nbsp;·&nbsp; Date: {{ \Carbon\Carbon::parse(request('date'))->format('F j, Y') }} @endif
        @if(request('month') && !request('date')) &nbsp;·&nbsp; Month: {{ \Carbon\Carbon::createFromFormat('Y-m', request('month'))->format('F Y') }} @endif
    </p>
    @endif
    <p class="text-xs text-gray-600 mt-1 font-semibold">Total Collected: ₱{{ number_format($totalAmount, 2) }} across {{ $payments->count() }} payment(s)</p>
    <p class="text-xs text-gray-400 mt-1">Printed: {{ now()->format('F j, Y h:i A') }}</p>
</div>

{{-- Screen header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 no-print">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Fee Collected</h2>
        <p class="text-sm text-gray-400 mt-0.5">Who paid, when, and under what OR number</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-arrow-left text-xs"></i> Documents
        </a>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-500 transition-colors px-4 py-2.5 text-sm font-semibold text-white">
            <i class="fa-solid fa-file-pdf"></i> PDF / Print
        </button>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route($isStaff ? 'staff.documents.payments' : 'documents.payments') }}" class="no-print">
<div class="flex flex-col sm:flex-row flex-wrap gap-3 mb-3">
    <div class="relative flex-1 min-w-[200px]">
        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by resident name or OR number..."
               class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-sm text-gray-900 placeholder-gray-400
                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
    </div>
    <select name="type" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Document Types</option>
        @foreach($docTypeOptions as $t)
        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>
    <select name="month" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Time</option>
        @foreach($availableMonths as $value => $label)
        <option value="{{ $value }}" {{ request('month') === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()"
           class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
    <button type="submit" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
    </button>
    @if($hasFilters)
    <a href="{{ route($isStaff ? 'staff.documents.payments' : 'documents.payments') }}"
       class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-xmark text-xs"></i>
    </a>
    @endif
</div>
<p class="text-xs text-gray-400 mb-5">
    @if(request('date'))
        Showing payments for <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse(request('date'))->format('F j, Y') }}</span> only — the month filter is ignored when a specific date is set.
    @endif
</p>
</form>

{{-- Total summary --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 mb-5 flex items-center gap-4 no-print">
    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-50 text-green-700">
        <i class="fa-solid fa-sack-dollar"></i>
    </div>
    <div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">₱{{ number_format($totalAmount, 2) }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Total collected across {{ $payments->count() }} payment{{ $payments->count() == 1 ? '' : 's' }}{{ $hasFilters ? ' (filtered)' : '' }}</p>
    </div>
</div>

{{-- Payments Table --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    style="background-color:#1a4731;">
                    <th class="px-5 py-3.5 text-green-100">Date Paid</th>
                    <th class="px-5 py-3.5 text-green-100">OR Number</th>
                    <th class="px-5 py-3.5 text-green-100">Resident</th>
                    <th class="px-5 py-3.5 text-green-100">Document Type</th>
                    <th class="px-5 py-3.5 text-green-100 text-right">Amount</th>
                    <th class="px-5 py-3.5 text-green-100">Status</th>
                    <th class="px-5 py-3.5 text-green-100">Processed By</th>
                </tr>
            </thead>
            <tbody>
                @if($payments->count() > 0)
                    @foreach($payments as $p)
                    <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-brand-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-5 py-4 text-gray-600 text-xs">{{ $p->paid_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-5 py-4 font-mono text-xs font-semibold text-gray-800">{{ $p->or_number }}</td>
                        <td class="px-5 py-4 text-gray-900 font-semibold">{{ $p->resident?->full_name ?? 'N/A' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $p->document_type }}</td>
                        <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ $p->fee ? '₱'.number_format($p->fee, 2) : 'Free' }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-semibold {{ $p->status === 'Issued' ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' }}">
                                {{ $p->status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-gray-500 text-xs">{{ $p->processedBy?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-{{ $hasFilters ? 'magnifying-glass' : 'sack-dollar' }} text-lg"></i>
                                </div>
                                <div>
                                    @if($hasFilters)
                                    <p class="text-sm font-semibold text-gray-900">No payments found</p>
                                    <p class="text-xs text-gray-400 mt-1">No results match your search or filter. Try different keywords or a different date.</p>
                                    @else
                                    <p class="text-sm font-semibold text-gray-900">No payments recorded yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Payments appear here once a document request is Approved or Issued.</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-100 no-print">
        <span class="text-xs text-gray-400">
            Showing {{ $payments->count() }} payment{{ $payments->count() == 1 ? '' : 's' }}
        </span>
    </div>
</div>

@endsection
