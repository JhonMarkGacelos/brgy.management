@extends('layouts.app')
@section('title', 'Analytics & Reports')

@push('styles')
<style>
@media screen {
    .print-header  { display: none; }
    .print-only    { display: none !important; }
}

@media print {
    /* ── Hide on-screen UI ── */
    .no-print { display: none !important; }

    /* ── Reset layout so sidebar disappears ── */
    body, html {
        background: #fff !important;
        margin: 0 !important; padding: 0 !important;
        height: auto !important; overflow: visible !important;
        font-size: 8pt !important;
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

    /* ── Print header ── */
    .print-header { display: block !important; }
    .print-header h1 { font-size: 12pt !important; }
    .print-header h2 { font-size: 9pt !important; }
    .print-header p  { font-size: 7pt !important; }

    /* ── Typography ── */
    .text-xs             { font-size: 7pt !important; }
    .text-sm             { font-size: 8pt !important; }
    .text-2xl            { font-size: 11pt !important; }
    .text-base, .text-lg { font-size: 9pt !important; }

    /* ── Cards ── */
    .rounded-2xl {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        margin-bottom: 5px !important;
    }
    .p-4, .p-5     { padding: 5px !important; }
    .px-5          { padding-left: 5px !important; padding-right: 5px !important; }
    .py-4          { padding-top: 3px !important; padding-bottom: 3px !important; }
    .py-3, .py-3\.5 { padding-top: 2px !important; padding-bottom: 2px !important; }
    .mb-6, .mb-5   { margin-bottom: 5px !important; }
    .mb-4, .mb-3   { margin-bottom: 3px !important; }
    .gap-4, .gap-5 { gap: 4px !important; }
    .space-y-3 > * + * { margin-top: 3px !important; }
    .h-9, .h-10    { height: 16px !important; width: 16px !important; font-size: 7pt !important; }

    /* ── Grid columns ── */
    .grid { display: grid !important; }
    .grid-cols-2                                              { grid-template-columns: repeat(2, 1fr) !important; }
    .grid-cols-3, .sm\:grid-cols-3, .lg\:grid-cols-3         { grid-template-columns: repeat(3, 1fr) !important; }
    .grid-cols-4, .sm\:grid-cols-4                           { grid-template-columns: repeat(4, 1fr) !important; }
    .lg\:grid-cols-6, .sm\:grid-cols-6                       { grid-template-columns: repeat(6, 1fr) !important; }
    .lg\:grid-cols-2, .grid-cols-1                           { grid-template-columns: repeat(2, 1fr) !important; }

    /* ── Hide all ApexCharts, show print fallbacks ── */
    #blotterChart, #docPieChart, #purokChart,
    #monthlyDocsChart, #monthlyRevenueChart, #welfareChart,
    #civilStatusChart, #employmentChart { display: none !important; }
    .print-only  { display: table !important; }
    .print-block { display: block !important; }

    /* ── Page 2 break ── */
    .print-page-2 { break-before: page !important; page-break-before: always !important; }

    @page { size: A4 portrait; margin: 1.2cm; }
}
</style>
@endpush

@section('content')

{{-- Print-only header --}}
<div class="print-header text-center mb-6 pb-4 border-b-2 border-gray-800">
    <p class="text-xs text-gray-500 uppercase tracking-widest">Republic of the Philippines · Province of Samar · Municipality of Motiong</p>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Barangay Caranas</h1>
    <h2 class="text-base font-semibold text-gray-700 mt-0.5">Population &amp; Activity Report</h2>
    @if($filterMonth || $filterSector)
    <p class="text-xs text-gray-600 mt-1 font-medium">
        Report Filter:
        @if($filterMonth) {{ \Carbon\Carbon::createFromFormat('Y-m', $filterMonth)->format('F Y') }} @endif
        @if($filterMonth && $filterSector) &nbsp;·&nbsp; @endif
        @if($filterSector) Sector: {{ $filterSector }} @endif
    </p>
    @endif
    <p class="text-xs text-gray-400 mt-1">Printed: {{ now()->format('F j, Y h:i A') }}</p>
</div>

{{-- Screen header + filter panel --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 no-print">
    <div>
        <h2 class="text-base font-semibold text-gray-900">Population &amp; Activity Reports</h2>
        <p class="text-xs text-gray-400 mt-0.5">Barangay Caranas statistics and insights</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="window.print()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 hover:bg-red-500 transition-colors px-3.5 py-2 text-xs font-semibold text-white">
            <i class="fa-solid fa-file-pdf"></i> PDF / Print
        </button>
    </div>
</div>

{{-- Filter Panel --}}
<div class="no-print mb-6 rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">
        <i class="fa-solid fa-filter mr-1.5 text-gray-400"></i> Filter Report
    </p>
    <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Month</label>
            <select name="month"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                           focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                <option value="">All Time</option>
                @foreach($availableMonths as $value => $label)
                <option value="{{ $value }}" {{ $filterMonth === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Sector</label>
            <select name="sector"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                           focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                <option value="">All Sectors</option>
                @foreach(['4Ps','Senior Citizen','Social Pension','PWD','Solo Parent','Voter','Indigent','Pregnant'] as $s)
                <option value="{{ $s }}" {{ $filterSector === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors"
                style="background-color:#1a4731;"
                onmouseover="this.style.backgroundColor='#2d6a4f'"
                onmouseout="this.style.backgroundColor='#1a4731'">
            <i class="fa-solid fa-magnifying-glass text-xs"></i> Apply Filter
        </button>
        @if($filterMonth || $filterSector)
        <a href="{{ route('analytics.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-xmark text-xs"></i> Clear
        </a>
        @endif
    </form>
    @if($filterMonth || $filterSector)
    <div class="mt-3 flex flex-wrap gap-2 items-center">
        <span class="text-xs text-gray-400">Active filters:</span>
        @if($filterMonth)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 border border-green-200 px-2.5 py-1 text-xs font-semibold text-green-700">
            <i class="fa-solid fa-calendar-day text-[10px]"></i>
            {{ \Carbon\Carbon::createFromFormat('Y-m', $filterMonth)->format('F Y') }}
        </span>
        @endif
        @if($filterSector)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-xs font-semibold text-blue-700">
            <i class="fa-solid fa-tag text-[10px]"></i>
            {{ $filterSector }}
        </span>
        @endif
    </div>
    @endif
</div>

{{-- ── POPULATION OVERVIEW ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
    Population Overview
    @if($filterMonth)<span class="normal-case font-normal text-gray-400 ml-1">— as of {{ $asOf->format('F j, Y') }}</span>@endif
    @if($filterSector)<span class="normal-case font-normal text-gray-400 ml-1">— {{ $filterSector }} only</span>@endif
</p>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
        $popCards = [
            ['label'=>'Total Population', 'value'=>$totalResidents,  'icon'=>'fa-people-group',       'bg'=>'bg-indigo-50',  'color'=>'text-indigo-600', 'new'=>$newResidents],
            ['label'=>'Total Families',   'value'=>$totalHouseholds, 'icon'=>'fa-house-chimney-user',  'bg'=>'bg-green-50',   'color'=>'text-green-700', 'new'=>$newHouseholds],
            ['label'=>'Male',             'value'=>$maleResidents,   'icon'=>'fa-person',              'bg'=>'bg-blue-50',    'color'=>'text-blue-600', 'note'=>$genderUnknown ? $genderUnknown.' gender not specified' : null],
            ['label'=>'Female',           'value'=>$femaleResidents, 'icon'=>'fa-person-dress',        'bg'=>'bg-pink-50',    'color'=>'text-pink-600'],
            ['label'=>'Senior Citizens',  'value'=>$seniorCitizens,  'icon'=>'fa-person-cane',         'bg'=>'bg-orange-50',  'color'=>'text-orange-600'],
            ['label'=>'PWDs',             'value'=>$pwds,            'icon'=>'fa-wheelchair',          'bg'=>'bg-purple-50',  'color'=>'text-purple-600'],
        ];
    @endphp
    @foreach($popCards as $s)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} text-sm mb-3">
            <i class="fa-solid {{ $s['icon'] }}"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ number_format($s['value']) }}</p>
        <p class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $s['label'] }}</p>
        @if(isset($s['new']) && $s['new'] !== null)
        <p class="text-[11px] text-green-600 mt-1">+{{ number_format($s['new']) }} registered this month</p>
        @endif
        @if(!empty($s['note']))
        <p class="text-[11px] text-amber-600 mt-1">{{ $s['note'] }}</p>
        @endif
    </div>
    @endforeach
</div>

{{-- ── SECTOR STATS ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Sector Statistics</p>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $sectorCards = [
            ['label'=>'Registered Voters', 'value'=>$voters,     'icon'=>'fa-check-to-slot',       'bg'=>'bg-teal-50',  'color'=>'text-teal-600'],
            ['label'=>'4Ps Beneficiaries', 'value'=>$fourPs,     'icon'=>'fa-hand-holding-heart',  'bg'=>'bg-sky-50',   'color'=>'text-sky-600'],
            ['label'=>'Social Pension',     'value'=>$socialPensioners, 'icon'=>'fa-person-cane',   'bg'=>'bg-teal-50',  'color'=>'text-teal-700'],
            ['label'=>'Solo Parents',       'value'=>$soloParents,'icon'=>'fa-person-breastfeeding','bg'=>'bg-rose-50',  'color'=>'text-rose-600'],
            ['label'=>'Indigent',           'value'=>$indigent,  'icon'=>'fa-people-roof',          'bg'=>'bg-amber-50', 'color'=>'text-amber-600'],
            ['label'=>'Pregnant',           'value'=>$pregnant,  'icon'=>'fa-baby',                 'bg'=>'bg-pink-50',  'color'=>'text-pink-600'],
        ];
    @endphp
    @foreach($sectorCards as $s)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} text-sm mb-3">
            <i class="fa-solid {{ $s['icon'] }}"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ number_format($s['value']) }}</p>
        <p class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $s['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── BLOTTER & DOCUMENTS ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- Blotter --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 text-xs">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <p class="text-sm font-semibold text-gray-800">Complaint Cases
                @if($filterMonth)<span class="text-xs font-normal text-gray-400 ml-1">— {{ \Carbon\Carbon::createFromFormat('Y-m', $filterMonth)->format('F Y') }}</span>@endif
            </p>
        </div>
        <div class="p-5 grid grid-cols-3 sm:grid-cols-5 gap-4">
            @php
                $blotterStats = [
                    ['label'=>'Total',           'value'=>$totalCases,    'color'=>'text-gray-900'],
                    ['label'=>'Pending',         'value'=>$pendingCases,  'color'=>'text-yellow-600'],
                    ['label'=>'Under Mediation', 'value'=>$ongoingCases,  'color'=>'text-blue-600'],
                    ['label'=>'Settled',         'value'=>$settledCases,  'color'=>'text-green-600'],
                    ['label'=>'Referred',        'value'=>$referredCases, 'color'=>'text-purple-600'],
                ];
            @endphp
            @foreach($blotterStats as $b)
            <div class="text-center">
                <p class="text-2xl font-bold {{ $b['color'] }}">{{ number_format($b['value']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $b['label'] }}</p>
            </div>
            @endforeach
        </div>
        @if($settlementRate !== null)
        <p class="px-5 -mt-2 pb-3 text-xs text-gray-500">
            Settlement rate: <span class="font-semibold text-green-700">{{ $settlementRate }}%</span>
            <span class="text-gray-400">&middot; settled ÷ (settled + referred) at barangay level</span>
        </p>
        @endif
        <div class="px-5 pb-5">
            <div id="blotterChart"></div>
        </div>
    </div>

    {{-- Documents --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <p class="text-sm font-semibold text-gray-800">Document Requests
                @if($filterMonth)<span class="text-xs font-normal text-gray-400 ml-1">— {{ \Carbon\Carbon::createFromFormat('Y-m', $filterMonth)->format('F Y') }}</span>@endif
            </p>
        </div>
        <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $docStats = [
                    ['label'=>'Total',    'value'=>$totalDocuments,    'color'=>'text-gray-900'],
                    ['label'=>'In Progress', 'value'=>$pendingDocuments, 'color'=>'text-yellow-600'],
                    ['label'=>'Issued',   'value'=>$issuedDocuments,   'color'=>'text-green-600'],
                    ['label'=>'Rejected', 'value'=>$rejectedDocuments, 'color'=>'text-red-600'],
                ];
            @endphp
            @foreach($docStats as $d)
            <div class="text-center">
                <p class="text-2xl font-bold {{ $d['color'] }}">{{ number_format($d['value']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $d['label'] }}</p>
            </div>
            @endforeach
        </div>
        <div class="px-5 pb-5">
            <div id="docPieChart"></div>
        </div>
    </div>
</div>

{{-- ── CHARTS ROW ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- Population by Purok --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900 mb-1">Population by Purok</p>
        <p class="text-xs text-gray-400 mb-4">Registered residents per purok</p>
        <div id="purokChart"></div>
        {{-- Print fallback --}}
        <table class="print-only w-full text-xs" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                <th class="text-left py-1 text-gray-500">Purok</th>
                <th class="text-right py-1 text-gray-500">Residents</th>
            </tr></thead>
            <tbody>
                @forelse($populationByPurok as $purok => $count)
                <tr class="border-b border-gray-100">
                    <td class="py-1 text-gray-700">{{ $purok }}</td>
                    <td class="py-1 text-right font-semibold text-gray-900">{{ $count }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="py-2 text-gray-400 text-center">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Monthly Documents --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900 mb-1">Monthly Document Requests</p>
        <p class="text-xs text-gray-400 mb-4">Last 12 months</p>
        <div id="monthlyDocsChart"></div>
        {{-- Print fallback --}}
        <table class="print-only w-full text-xs" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                @foreach($monthLabels as $m)<th class="text-center py-1 text-gray-500">{{ $m }}</th>@endforeach
            </tr></thead>
            <tbody><tr>
                @foreach($monthlyDocuments as $val)
                <td class="text-center py-1 font-semibold text-gray-900">{{ $val }}</td>
                @endforeach
            </tr></tbody>
        </table>
    </div>
</div>

{{-- ── REVENUE ── --}}
<div class="flex items-center justify-between mb-3">
    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
        Revenue
        @if($filterMonth)<span class="normal-case font-normal text-gray-400 ml-1">— {{ \Carbon\Carbon::createFromFormat('Y-m', $filterMonth)->format('F Y') }}</span>@endif
    </p>
    <a href="{{ route('documents.payments') }}" class="no-print text-xs font-semibold text-green-700 hover:text-green-800 transition-colors">
        View Fee Collected <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
    </a>
</div>
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-5">
    @php
        $revenueCards = [
            ['label'=>'Total Collected',  'value'=>'₱'.number_format($totalRevenue, 2),          'icon'=>'fa-sack-dollar',   'bg'=>'bg-green-50', 'color'=>'text-green-700'],
            ['label'=>'Paid Requests',    'value'=>number_format($paidDocumentsCount),            'icon'=>'fa-file-invoice', 'bg'=>'bg-blue-50',  'color'=>'text-blue-600'],
            ['label'=>'Avg. per Request', 'value'=>'₱'.number_format($avgRevenuePerDocument, 2), 'icon'=>'fa-calculator',   'bg'=>'bg-amber-50', 'color'=>'text-amber-600'],
        ];
    @endphp
    @foreach($revenueCards as $r)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $r['bg'] }} {{ $r['color'] }} text-sm mb-3">
            <i class="fa-solid {{ $r['icon'] }}"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $r['value'] }}</p>
        <p class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $r['label'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- Monthly Revenue --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900 mb-1">Monthly Revenue</p>
        <p class="text-xs text-gray-400 mb-4">Last 12 months, collected amounts</p>
        <div id="monthlyRevenueChart"></div>
        {{-- Print fallback --}}
        <table class="print-only w-full text-xs" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                @foreach($monthLabels as $m)<th class="text-center py-1 text-gray-500">{{ $m }}</th>@endforeach
            </tr></thead>
            <tbody><tr>
                @foreach($monthlyRevenue as $val)
                <td class="text-center py-1 font-semibold text-gray-900">₱{{ number_format($val, 0) }}</td>
                @endforeach
            </tr></tbody>
        </table>
    </div>

    {{-- Revenue by Document Type --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Revenue by Document Type</p>
            <p class="text-xs text-gray-400 mt-0.5">Collected amount per document type</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                        <th class="px-5 py-2.5">Document Type</th>
                        <th class="px-5 py-2.5 text-right">Paid</th>
                        <th class="px-5 py-2.5 text-right">Collected</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(['Barangay Clearance','Certificate of Residency','Certificate of Indigency','Business Clearance'] as $type)
                    @php $row = $revenueByType[$type] ?? null; @endphp
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="px-5 py-3 text-gray-700">{{ $type }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $row->count ?? 0 }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900">₱{{ number_format($row->total ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-5 py-4 text-center text-gray-400">No data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── WELFARE CLASSIFICATION ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
    Barangay Welfare Score <span class="normal-case font-normal">— barangay's own scoring, not PSA</span>
</p>

{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
    @php
        $welfareSummary = [
            ['label'=>'Families Assessed',   'value'=> number_format($householdsClassified), 'icon'=>'fa-clipboard-check', 'bg'=>'bg-gray-50',   'color'=>'text-gray-600'],
            ['label'=>'Avg. Per Capita / mo', 'value'=>'₱'.number_format($avgPerCapita, 0),  'icon'=>'fa-peso-sign',       'bg'=>'bg-green-50',  'color'=>'text-green-700'],
            ['label'=>'Poor tiers (score)',   'value'=> number_format($belowPovertyLine),     'icon'=>'fa-circle-exclamation','bg'=>'bg-red-50',  'color'=>'text-red-600'],
            ['label'=>'Non-Poor / Vulnerable','value'=> number_format(($classificationCounts['Non-Poor'] ?? 0) + ($classificationCounts['Vulnerable'] ?? 0)), 'icon'=>'fa-circle-check', 'bg'=>'bg-teal-50', 'color'=>'text-teal-600'],
        ];
    @endphp
    @foreach($welfareSummary as $s)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} text-sm mb-3">
            <i class="fa-solid {{ $s['icon'] }}"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $s['value'] }}</p>
        <p class="text-xs text-gray-400 mt-0.5 leading-tight">{{ $s['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Tier distribution --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-500 text-xs">
            <i class="fa-solid fa-scale-balanced"></i>
        </div>
        <p class="text-sm font-semibold text-gray-800">Welfare Score Distribution</p>
        @if($householdsClassified < $totalHouseholds)
        <span class="ml-auto text-[11px] text-gray-400">{{ $totalHouseholds - $householdsClassified }} household(s) not yet assessed</span>
        @endif
    </div>
    <div id="welfareChart" class="px-5 py-4"></div>
    {{-- Print fallback --}}
    @php $welfareTotal = array_sum($classificationCounts) ?: 1; @endphp
    <table class="print-only w-full text-xs px-5 pb-3" style="display:none;">
        <thead><tr class="border-b border-gray-200">
            <th class="text-left py-1 text-gray-500">Classification</th>
            <th class="text-right py-1 text-gray-500">Households</th>
            <th class="text-right py-1 text-gray-500">%</th>
        </tr></thead>
        <tbody>
            @foreach($classificationCounts as $tier => $cnt)
            <tr class="border-b border-gray-100">
                <td class="py-1 text-gray-700">{{ $tier }}</td>
                <td class="py-1 text-right font-semibold text-gray-900">{{ $cnt }}</td>
                <td class="py-1 text-right text-gray-500">{{ round($cnt / $welfareTotal * 100, 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>


{{-- ── PSA POVERTY STATUS ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
    PSA Poverty Status <span class="normal-case font-normal">— per capita income vs. PSA thresholds</span>
</p>
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-6">
    @if(!$psaThresholds['poverty'])
    <div class="p-5 text-center text-xs text-amber-700">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
        PSA thresholds haven't been set yet. Enter them in Settings to see PSA Poverty Status.
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 border-b border-gray-100">
        <div>
            <p class="text-2xl font-bold text-red-600 tracking-tight">{{ number_format($psaBelowLine) }}</p>
            <p class="text-xs text-gray-400">Households below the PSA poverty line</p>
        </div>
        <div class="flex gap-6">
            <div>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $psaFamilyIncidence }}%</p>
                <p class="text-xs text-gray-400">Poverty incidence among families</p>
                <p class="text-[11px] text-gray-300">{{ number_format($psaBelowLine) }} of {{ number_format($psaAssessed) }} households</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $psaPopulationIncidence }}%</p>
                <p class="text-xs text-gray-400">Poverty incidence among population</p>
                <p class="text-[11px] text-gray-300">{{ number_format($psaPeopleBelow) }} of {{ number_format($psaPeopleAssessed) }} people</p>
            </div>
        </div>
        <div class="text-xs text-gray-500 space-y-0.5">
            @if($psaThresholds['food'])
            <p>Food threshold: <span class="font-semibold text-gray-700">₱{{ number_format($psaThresholds['food'], 2) }}</span></p>
            @endif
            <p>Poverty threshold: <span class="font-semibold text-gray-700">₱{{ number_format($psaThresholds['poverty'], 2) }}</span></p>
            <p class="text-gray-400">{{ $psaThresholds['source'] }}</p>
        </div>
    </div>
    <div class="px-5 py-4 space-y-2">
        @php $psaMax = max(1, max($psaCounts)); @endphp
        @foreach($psaCounts as $status => $cnt)
        <div class="flex items-center gap-3 text-xs">
            <span class="w-28 shrink-0 inline-flex justify-center rounded-md px-2 py-0.5 font-medium {{ \App\Services\ClassificationService::PSA_STYLES[$status] }}">{{ $status }}</span>
            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                <div class="h-2 rounded-full bg-sky-500" style="width: {{ $cnt / $psaMax * 100 }}%"></div>
            </div>
            <span class="w-20 text-right text-gray-700 font-semibold">{{ $cnt }}
                <span class="font-normal text-gray-400">({{ $psaAssessed > 0 ? round($cnt / $psaAssessed * 100, 1) : 0 }}%)</span>
            </span>
        </div>
        @endforeach
        <p class="pt-2 text-[11px] text-gray-400">Food Poor and Poor follow PSA. Classes above the poverty line follow the PIDS income classes (multiples of the poverty line).</p>
    </div>
    @endif
</div>

{{-- ── AGE GROUP TABLE (PAGE 2) ── --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-6 print-page-2">
    <div class="px-5 py-4 border-b border-gray-100">
        <p class="text-sm font-semibold text-gray-900">Age Group Demographics</p>
        <p class="text-xs text-gray-400 mt-0.5">Population breakdown by age category</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                    <th class="px-5 py-3.5">Age Group</th>
                    <th class="px-5 py-3.5">Male</th>
                    <th class="px-5 py-3.5">Female</th>
                    @if($genderUnknown)<th class="px-5 py-3.5">Not specified</th>@endif
                    <th class="px-5 py-3.5">Total</th>
                    <th class="px-5 py-3.5">% of Population</th>
                </tr>
            </thead>
            <tbody>
                @php $totalMale = 0; $totalFemale = 0; $totalUnspecified = 0; @endphp
                @if($ageUnknown)
                    @php $ageGroups[] = ['label' => 'Age not recorded', 'male' => 0, 'female' => 0, 'unspecified' => 0, 'all' => $ageUnknown]; @endphp
                @endif
                @foreach($ageGroups as $g)
                @php
                    $total = $g['all'] ?? ($g['male'] + $g['female'] + $g['unspecified']);
                    $totalMale += $g['male']; $totalFemale += $g['female']; $totalUnspecified += $g['unspecified'];
                    $pct = $totalResidents > 0 ? round($total / $totalResidents * 100, 1) : 0;
                @endphp
                <tr class="odd:bg-white even:bg-gray-50/70 border-b border-gray-100">
                    <td class="px-5 py-4 font-medium text-gray-800">{{ $g['label'] }}</td>
                    <td class="px-5 py-4 font-semibold text-blue-600">{{ number_format($g['male']) }}</td>
                    <td class="px-5 py-4 font-semibold text-pink-600">{{ number_format($g['female']) }}</td>
                    @if($genderUnknown)<td class="px-5 py-4 font-semibold text-gray-500">{{ number_format($g['unspecified']) }}</td>@endif
                    <td class="px-5 py-4 font-bold text-gray-900">{{ number_format($total) }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-1.5 rounded-full bg-gray-100 max-w-[120px]">
                                <div class="h-1.5 rounded-full bg-green-600" style="width: {{ min($pct * 4, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600 w-10">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                <tr class="bg-gray-100 border-t-2 border-gray-300 font-bold">
                    <td class="px-5 py-4 text-gray-900">Total</td>
                    <td class="px-5 py-4 text-blue-600">{{ number_format($totalMale) }}</td>
                    <td class="px-5 py-4 text-pink-600">{{ number_format($totalFemale) }}</td>
                    @if($genderUnknown)<td class="px-5 py-4 text-gray-500">{{ number_format($totalUnspecified) }}</td>@endif
                    <td class="px-5 py-4 text-gray-900">{{ number_format($totalResidents) }}</td>
                    <td class="px-5 py-4 text-xs text-gray-500">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── CIVIL STATUS & EMPLOYMENT ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- Civil Status — Donut (proportional breakdown) --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Civil Status Breakdown</p>
            <p class="text-xs text-gray-400 mt-0.5">All residents &middot; civil status is recorded for household heads; others show as Not recorded</p>
        </div>
        <div id="civilStatusChart" class="px-5 py-4"></div>
        {{-- Print fallback --}}
        @php $totalCivil = array_sum($civilStatus) ?: 1; @endphp
        <table class="print-only w-full text-xs px-5 pb-3" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                <th class="text-left py-1 text-gray-500">Civil Status</th>
                <th class="text-right py-1 text-gray-500">Count</th>
                <th class="text-right py-1 text-gray-500">%</th>
            </tr></thead>
            <tbody>
                @forelse($civilStatus as $status => $count)
                <tr class="border-b border-gray-100">
                    <td class="py-1 text-gray-700">{{ $status }}</td>
                    <td class="py-1 text-right font-semibold text-gray-900">{{ number_format($count) }}</td>
                    <td class="py-1 text-right text-gray-500">{{ round($count / $totalCivil * 100, 1) }}%</td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-2 text-center text-gray-400">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Employment Status — Horizontal Bar (comparison across categories) --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Employment Status Breakdown</p>
            <p class="text-xs text-gray-400 mt-0.5">All residents per employment category, including Not recorded</p>
        </div>
        <div id="employmentChart" class="px-5 py-4"></div>
        {{-- Print fallback --}}
        @php $totalEmp = array_sum($employmentStatus) ?: 1; @endphp
        <table class="print-only w-full text-xs px-5 pb-3" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                <th class="text-left py-1 text-gray-500">Employment Status</th>
                <th class="text-right py-1 text-gray-500">Count</th>
                <th class="text-right py-1 text-gray-500">%</th>
            </tr></thead>
            <tbody>
                @forelse($employmentStatus as $status => $count)
                <tr class="border-b border-gray-100">
                    <td class="py-1 text-gray-700">{{ $status }}</td>
                    <td class="py-1 text-right font-semibold text-gray-900">{{ number_format($count) }}</td>
                    <td class="py-1 text-right text-gray-500">{{ round($count / $totalEmp * 100, 1) }}%</td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-2 text-center text-gray-400">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
$monthlyCasesJson        = json_encode($monthlyCases);
$monthlyDocsJson         = json_encode($monthlyDocuments);
$monthlyRevenueJson      = json_encode($monthlyRevenue);
$monthLabelsJson         = json_encode($monthLabels);
$docTypesValuesJson      = json_encode(array_values($documentTypes));
$docTypesLabelsJson      = json_encode(array_keys($documentTypes));
$purokValuesJson         = json_encode(array_values($populationByPurok));
$purokLabelsJson         = json_encode(array_keys($populationByPurok));
$welfareLabelsJson       = json_encode(array_keys($classificationCounts));
$welfareValuesJson       = json_encode(array_values($classificationCounts));
$civilLabelsJson         = json_encode(array_keys($civilStatus));
$civilValuesJson         = json_encode(array_values($civilStatus));
$empLabelsJson           = json_encode(array_keys($employmentStatus));
$civilPalette            = ['#6366f1','#ec4899','#f59e0b','#10b981','#0ea5e9'];
$civilColorsJson         = json_encode(array_values(array_map(
    fn ($label, $i) => $label === 'Not recorded' ? '#d1d5db' : $civilPalette[$i % count($civilPalette)],
    array_keys($civilStatus), array_keys(array_keys($civilStatus)))));
$empColorsJson           = json_encode(array_map(fn ($label) => $label === 'Not recorded' ? '#d1d5db' : '#10b981', array_keys($employmentStatus)));
$empValuesJson           = json_encode(array_values($employmentStatus));
@endphp

<script>
const months = {!! $monthLabelsJson !!};

// Monthly Blotter Chart
new ApexCharts(document.getElementById('blotterChart'), {
    chart: { type: 'bar', height: 180, toolbar: { show: false } },
    series: [{ name: 'Cases', data: {!! $monthlyCasesJson !!} }],
    xaxis: { categories: months, labels: { style: { fontSize: '10px', colors: '#9ca3af' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontSize: '10px', colors: '#9ca3af' } } },
    colors: ['#ef4444'],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
}).render();

// Document Types Donut
new ApexCharts(document.getElementById('docPieChart'), {
    chart: { type: 'donut', height: 180 },
    series: {!! $docTypesValuesJson !!},
    labels: {!! $docTypesLabelsJson !!},
    colors: ['#1a4731','#3b82f6','#f59e0b','#8b5cf6','#ec4899'],
    legend: { position: 'bottom', fontSize: '10px', labels: { colors: '#6b7280' } },
    dataLabels: { style: { fontSize: '10px' } },
    plotOptions: { pie: { donut: { size: '60%' } } },
    stroke: { width: 0 },
}).render();

// Population by Purok
new ApexCharts(document.getElementById('purokChart'), {
    chart: { type: 'bar', height: 220, toolbar: { show: false } },
    series: [{ name: 'Residents', data: {!! $purokValuesJson !!} }],
    xaxis: { categories: {!! $purokLabelsJson !!}, labels: { style: { fontSize: '10px', colors: '#9ca3af' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontSize: '10px', colors: '#9ca3af' } } },
    colors: ['#52b788'],
    plotOptions: { bar: { borderRadius: 5, horizontal: true, barHeight: '55%' } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
}).render();

// Welfare Classification Donut
if (document.getElementById('welfareChart')) {
new ApexCharts(document.getElementById('welfareChart'), {
    chart: { type: 'donut', height: 220 },
    series: {!! $welfareValuesJson !!},
    labels: {!! $welfareLabelsJson !!},
    colors: ['#ef4444','#f97316','#eab308','#60a5fa','#22c55e'],
    legend: { position: 'bottom', fontSize: '10px', labels: { colors: '#6b7280' } },
    dataLabels: { style: { fontSize: '10px' } },
    plotOptions: { pie: { donut: { size: '60%' } } },
    stroke: { width: 0 },
}).render();
}

// Civil Status — Donut
if (document.getElementById('civilStatusChart')) {
new ApexCharts(document.getElementById('civilStatusChart'), {
    chart: { type: 'donut', height: 260 },
    series: {!! $civilValuesJson !!},
    labels: {!! $civilLabelsJson !!},
    colors: {!! $civilColorsJson !!},
    legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#6b7280' } },
    dataLabels: { style: { fontSize: '11px' }, dropShadow: { enabled: false } },
    plotOptions: { pie: { donut: { size: '55%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '11px', color: '#9ca3af', formatter: (w) => w.globals.seriesTotals.reduce((a,b) => a+b, 0) } } } } },
    stroke: { width: 0 },
    tooltip: { y: { formatter: (v) => v + ' residents' } },
}).render();
}

// Employment Status — Horizontal Bar
if (document.getElementById('employmentChart')) {
new ApexCharts(document.getElementById('employmentChart'), {
    chart: { type: 'bar', height: 260, toolbar: { show: false } },
    series: [{ name: 'Residents', data: {!! $empValuesJson !!} }],
    xaxis: { categories: {!! $empLabelsJson !!}, labels: { style: { fontSize: '11px', colors: '#9ca3af' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#9ca3af' } } },
    colors: {!! $empColorsJson !!},
    legend: { show: false },
    plotOptions: { bar: { borderRadius: 5, horizontal: true, barHeight: '50%', distributed: true,
        dataLabels: { position: 'center' } } },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: ['#fff'] }, formatter: (v) => v > 0 ? v : '' },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    tooltip: { y: { formatter: (v) => v + ' residents' } },
}).render();
}

// Monthly Documents Chart
new ApexCharts(document.getElementById('monthlyDocsChart'), {
    chart: { type: 'area', height: 220, toolbar: { show: false } },
    series: [{ name: 'Requests', data: {!! $monthlyDocsJson !!} }],
    xaxis: { categories: months, labels: { style: { fontSize: '10px', colors: '#9ca3af' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontSize: '10px', colors: '#9ca3af' } } },
    colors: ['#3b82f6'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
}).render();

// Monthly Revenue Chart
new ApexCharts(document.getElementById('monthlyRevenueChart'), {
    chart: { type: 'area', height: 220, toolbar: { show: false } },
    series: [{ name: 'Collected', data: {!! $monthlyRevenueJson !!} }],
    xaxis: { categories: months, labels: { style: { fontSize: '10px', colors: '#9ca3af' } }, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { style: { fontSize: '10px', colors: '#9ca3af' }, formatter: (v) => '₱' + v.toFixed(0) } },
    colors: ['#16a34a'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
    tooltip: { y: { formatter: (v) => '₱' + Number(v).toLocaleString() } },
}).render();
</script>

@endsection
