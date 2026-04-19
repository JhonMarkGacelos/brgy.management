@extends('layouts.app')
@section('title', 'Analytics & Reports')

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }

    body, html {
        background: #fff !important;
        margin: 0 !important; padding: 0 !important;
        height: auto !important; overflow: visible !important;
        font-size: 8pt !important;
    }

    body > div, .flex.h-screen {
        height: auto !important; overflow: visible !important;
    }

    .print-main {
        padding-left: 0 !important; margin-left: 0 !important;
        width: 100% !important; min-height: auto !important;
        overflow: visible !important;
    }

    main { padding: 6px !important; overflow: visible !important; }

    .print-header { display: block !important; }
    .print-header h1 { font-size: 13pt !important; }
    .print-header h2 { font-size: 10pt !important; }
    .print-header p  { font-size: 7pt !important; }

    /* Compact section labels */
    .text-xs  { font-size: 7pt !important; }
    .text-sm  { font-size: 8pt !important; }
    .text-2xl { font-size: 12pt !important; }
    .text-base, .text-lg { font-size: 9pt !important; }

    /* Tighter padding on cards */
    .rounded-2xl {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        margin-bottom: 6px !important;
    }
    .p-4, .p-5 { padding: 6px !important; }
    .px-5 { padding-left: 6px !important; padding-right: 6px !important; }
    .py-4 { padding-top: 4px !important; padding-bottom: 4px !important; }
    .py-3, .py-3\.5 { padding-top: 3px !important; padding-bottom: 3px !important; }
    .mb-6  { margin-bottom: 6px !important; }
    .mb-3  { margin-bottom: 4px !important; }
    .gap-4, .gap-5 { gap: 5px !important; }
    .space-y-3 > * + * { margin-top: 4px !important; }

    /* 2-column grids for stat cards */
    .grid { display: grid !important; }
    .grid-cols-2  { grid-template-columns: repeat(2, 1fr) !important; }
    .grid-cols-3, .sm\:grid-cols-3, .lg\:grid-cols-3 { grid-template-columns: repeat(3, 1fr) !important; }
    .grid-cols-4, .sm\:grid-cols-4 { grid-template-columns: repeat(4, 1fr) !important; }
    .lg\:grid-cols-6, .sm\:grid-cols-6 { grid-template-columns: repeat(3, 1fr) !important; }
    .lg\:grid-cols-2 { grid-template-columns: repeat(2, 1fr) !important; }

    /* Hide charts, show print fallback tables */
    #blotterChart, #docPieChart, #purokChart, #monthlyDocsChart { display: none !important; }
    .print-only { display: table !important; }

    /* Compact icon sizes */
    .h-9, .h-10 { height: 20px !important; width: 20px !important; font-size: 8pt !important; }
    .mb-3 { margin-bottom: 3px !important; }

    svg { max-width: 100% !important; }

    @page { size: A4; margin: 1cm; }
}
@media screen {
    .print-header { display: none; }
}
</style>
@endpush

@section('content')

{{-- Print-only header --}}
<div class="print-header text-center mb-6 pb-4 border-b-2 border-gray-800">
    <p class="text-xs text-gray-500 uppercase tracking-widest">Republic of the Philippines · Province of Samar · Municipality of Motiong</p>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Barangay Caranas</h1>
    <h2 class="text-base font-semibold text-gray-700 mt-0.5">Population &amp; Activity Report</h2>
    <p class="text-xs text-gray-400 mt-1">Printed: {{ now()->format('F j, Y h:i A') }}</p>
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 no-print">
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

{{-- ── POPULATION OVERVIEW ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Population Overview</p>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
        $popCards = [
            ['label'=>'Total Population', 'value'=>$totalResidents,  'icon'=>'fa-people-group',       'bg'=>'bg-indigo-50',  'color'=>'text-indigo-600'],
            ['label'=>'Total Families',   'value'=>$totalHouseholds, 'icon'=>'fa-house-chimney-user',  'bg'=>'bg-green-50',   'color'=>'text-green-700'],
            ['label'=>'Male',             'value'=>$maleResidents,   'icon'=>'fa-person',              'bg'=>'bg-blue-50',    'color'=>'text-blue-600'],
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
    </div>
    @endforeach
</div>

{{-- ── SECTOR STATS ── --}}
<p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Sector Statistics</p>
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $sectorCards = [
            ['label'=>'Registered Voters', 'value'=>$voters,     'icon'=>'fa-check-to-slot', 'bg'=>'bg-teal-50',   'color'=>'text-teal-600'],
            ['label'=>'4Ps Beneficiaries', 'value'=>$fourPs,     'icon'=>'fa-hand-holding-heart','bg'=>'bg-sky-50', 'color'=>'text-sky-600'],
            ['label'=>'Solo Parents',       'value'=>$soloParents,'icon'=>'fa-person-breastfeeding','bg'=>'bg-rose-50','color'=>'text-rose-600'],
            ['label'=>'Indigent',           'value'=>$indigent,  'icon'=>'fa-people-roof',   'bg'=>'bg-amber-50',  'color'=>'text-amber-600'],
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
            <p class="text-sm font-semibold text-gray-800">Blotter Cases</p>
        </div>
        <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $blotterStats = [
                    ['label'=>'Total',    'value'=>$totalCases,    'color'=>'text-gray-900'],
                    ['label'=>'Pending',  'value'=>$pendingCases,  'color'=>'text-yellow-600'],
                    ['label'=>'Ongoing',  'value'=>$ongoingCases,  'color'=>'text-blue-600'],
                    ['label'=>'Resolved', 'value'=>$resolvedCases, 'color'=>'text-green-600'],
                ];
            @endphp
            @foreach($blotterStats as $b)
            <div class="text-center">
                <p class="text-2xl font-bold {{ $b['color'] }}">{{ number_format($b['value']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $b['label'] }}</p>
            </div>
            @endforeach
        </div>
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
            <p class="text-sm font-semibold text-gray-800">Document Requests</p>
        </div>
        <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $docStats = [
                    ['label'=>'Total',    'value'=>$totalDocuments,    'color'=>'text-gray-900'],
                    ['label'=>'Pending',  'value'=>$pendingDocuments,  'color'=>'text-yellow-600'],
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
        @php
            $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $startMonth  = now()->subMonths(11)->month - 1;
            $orderedMonths = array_merge(array_slice($monthLabels, $startMonth), array_slice($monthLabels, 0, $startMonth));
        @endphp
        <table class="print-only w-full text-xs" style="display:none;">
            <thead><tr class="border-b border-gray-200">
                @foreach($orderedMonths as $m)<th class="text-center py-1 text-gray-500">{{ $m }}</th>@endforeach
            </tr></thead>
            <tbody><tr>
                @foreach($monthlyDocuments as $val)
                <td class="text-center py-1 font-semibold text-gray-900">{{ $val }}</td>
                @endforeach
            </tr></tbody>
        </table>
    </div>
</div>

{{-- ── AGE GROUP TABLE ── --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-6">
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
                    <th class="px-5 py-3.5">Total</th>
                    <th class="px-5 py-3.5">% of Population</th>
                </tr>
            </thead>
            <tbody>
                @php $totalMale = 0; $totalFemale = 0; @endphp
                @foreach($ageGroups as $g)
                @php
                    $total = $g['male'] + $g['female'];
                    $totalMale += $g['male']; $totalFemale += $g['female'];
                    $pct = $totalResidents > 0 ? round($total / $totalResidents * 100, 1) : 0;
                @endphp
                <tr class="odd:bg-white even:bg-gray-50/70 border-b border-gray-100">
                    <td class="px-5 py-4 font-medium text-gray-800">{{ $g['label'] }}</td>
                    <td class="px-5 py-4 font-semibold text-blue-600">{{ number_format($g['male']) }}</td>
                    <td class="px-5 py-4 font-semibold text-pink-600">{{ number_format($g['female']) }}</td>
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
                    <td class="px-5 py-4 text-gray-900">{{ number_format($totalResidents) }}</td>
                    <td class="px-5 py-4 text-xs text-gray-500">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ── CIVIL STATUS & EMPLOYMENT ── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- Civil Status --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Civil Status Breakdown</p>
        </div>
        <div class="p-5 space-y-3">
            @php $totalCivil = array_sum($civilStatus) ?: 1; @endphp
            @forelse($civilStatus as $status => $count)
            @php $pct = round($count / $totalCivil * 100, 1); @endphp
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-gray-600 font-medium">{{ $status }}</span>
                    <span class="text-xs font-bold text-gray-800">{{ number_format($count) }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">No data available.</p>
            @endforelse
        </div>
    </div>

    {{-- Employment Status --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Employment Status Breakdown</p>
        </div>
        <div class="p-5 space-y-3">
            @php $totalEmp = array_sum($employmentStatus) ?: 1; @endphp
            @forelse($employmentStatus as $status => $count)
            @php $pct = round($count / $totalEmp * 100, 1); @endphp
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-gray-600 font-medium">{{ $status }}</span>
                    <span class="text-xs font-bold text-gray-800">{{ number_format($count) }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400 text-center py-4">No data available.</p>
            @endforelse
        </div>
    </div>
</div>

@php
$monthlyCasesJson    = json_encode($monthlyCases);
$monthlyDocsJson     = json_encode($monthlyDocuments);
$docTypesValuesJson  = json_encode(array_values($documentTypes));
$docTypesLabelsJson  = json_encode(array_keys($documentTypes));
$purokValuesJson     = json_encode(array_values($populationByPurok));
$purokLabelsJson     = json_encode(array_keys($populationByPurok));
@endphp

<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

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
</script>

@endsection
