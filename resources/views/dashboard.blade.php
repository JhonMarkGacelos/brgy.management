@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

@php
/** @var int $totalResidents */
/** @var int $thisMonthResidents */
/** @var int $pendingDocuments */
/** @var int $resolvedCases */
/** @var array $activities */
/** @var array $monthlyBarangayTrends */
/** @var array $statusBreakdown */
$totalResidents    = $totalResidents    ?? 0;
$thisMonthResidents = $thisMonthResidents ?? 0;
$pendingDocuments  = $pendingDocuments  ?? 0;
$resolvedCases     = $resolvedCases     ?? 0;
$activities        = $activities        ?? [];
$monthlyBarangayTrends = $monthlyBarangayTrends ?? ['labels'=>[], 'documentsIssued'=>[], 'complaints'=>[], 'newResidents'=>[]];
$statusBreakdown   = $statusBreakdown   ?? [];
@endphp

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Welcome back, {{ explode(' ', Auth::user()->name ?? 'Admin')[0] }}!</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }} &mdash; Barangay Caranas</p>
    </div>
    <a href="{{ route('residents.create') }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shrink-0 transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-plus text-xs"></i> New Resident
    </a>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label'=>'Total Residents',   'value'=>$totalResidents, 'sub'=>'All time records',    'icon'=>'fa-users',              'iconBg'=>'bg-teal-100',   'iconColor'=>'text-teal-600'],
            ['label'=>'This Month',        'value'=>$thisMonthResidents,    'sub'=>'New registrations',   'icon'=>'fa-calendar-days',      'iconBg'=>'bg-blue-100',   'iconColor'=>'text-blue-600'],
            ['label'=>'Pending',           'value'=>$pendingDocuments,     'sub'=>'Awaiting action',     'icon'=>'fa-clock',              'iconBg'=>'bg-amber-100',  'iconColor'=>'text-amber-600'],
            ['label'=>'Resolved',          'value'=>$resolvedCases,     'sub'=>'Cases closed',        'icon'=>'fa-circle-check',       'iconBg'=>'bg-green-100',  'iconColor'=>'text-green-600'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm">
        <div class="flex items-start justify-between mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl {{ $card['iconBg'] }} {{ $card['iconColor'] }} text-sm">
                <i class="fa-solid {{ $card['icon'] }}"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ number_format($card['value']) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- Monthly Barangay Trends --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900">Monthly Barangay Trends</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">Documents issued, complaints & new residents this year</p>
        <div id="trendChart"></div>
    </div>

    {{-- Status Breakdown --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900">Document Requests</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">Status breakdown of all requests</p>
        <div id="statusChart"></div>
    </div>
</div>

{{-- Bottom Row --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Recent Activity --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Recent Activity</p>
            <a href="{{ route('audit.index') }}" class="text-xs font-medium text-green-700 hover:underline cursor-pointer">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @php
                $recentActivities = [];
                // Add documents
                foreach($activities['documents'] ?? [] as $doc) {
                    $recentActivities[] = [
                        'icon' => 'fa-file-lines',
                        'bg' => 'bg-blue-50 text-blue-500',
                        'title' => ucfirst($doc->document_type),
                        'sub' => $doc->resident?->full_name ?? 'Unknown Resident',
                        'time' => $doc->created_at->diffForHumans(),
                    ];
                }
                // Add residents
                foreach($activities['residents'] ?? [] as $resident) {
                    $recentActivities[] = [
                        'icon' => 'fa-user-plus',
                        'bg' => 'bg-green-50 text-green-600',
                        'title' => 'New resident registered',
                        'sub' => $resident->full_name,
                        'time' => $resident->created_at->diffForHumans(),
                    ];
                }
                // Add blotter
                foreach($activities['blotter'] ?? [] as $case) {
                    $recentActivities[] = [
                        'icon' => 'fa-shield-halved',
                        'bg' => 'bg-red-50 text-red-500',
                        'title' => 'Complaint case filed',
                        'sub' => $case->case_number ?? 'Case #' . $case->id,
                        'time' => $case->created_at->diffForHumans(),
                    ];
                }
                // Add announcements
                foreach($activities['announcements'] ?? [] as $announcement) {
                    $recentActivities[] = [
                        'icon' => 'fa-bullhorn',
                        'bg' => 'bg-purple-50 text-purple-500',
                        'title' => 'Announcement posted',
                        'sub' => $announcement->title,
                        'time' => $announcement->created_at->diffForHumans(),
                    ];
                }
                // Sort by time and take latest
                usort($recentActivities, fn($a, $b) => 0);
                $recentActivities = array_slice($recentActivities, 0, 5);
            @endphp
            @forelse($recentActivities as $a)
            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/60 transition-colors">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $a['bg'] }} text-sm">
                    <i class="fa-solid {{ $a['icon'] }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $a['title'] }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $a['sub'] }}</p>
                </div>
                <span class="text-xs text-gray-400 shrink-0">{{ $a['time'] }}</span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                No recent activity
            </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900 mb-4">Quick Actions</p>
        <div class="grid grid-cols-2 gap-2.5">

            <a href="{{ route('residents.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-brand-50 hover:bg-brand-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-user-plus text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-brand-700 group-hover:text-white transition-colors leading-tight text-center">New Resident</span>
            </a>

            <a href="{{ route('blotter.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-red-50 hover:bg-red-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-shield-halved text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-red-600 group-hover:text-white transition-colors leading-tight text-center">File Complaint</span>
            </a>

            <a href="{{ route('documents.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-blue-50 hover:bg-blue-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-file-circle-plus text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-blue-600 group-hover:text-white transition-colors leading-tight text-center">Issue Document</span>
            </a>

            <a href="{{ route('announcements.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-purple-50 hover:bg-purple-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-bullhorn text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 group-hover:text-white transition-colors leading-tight text-center">Post Announcement</span>
            </a>

        </div>

        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Sector Summary</p>
            <div class="space-y-3">
                @foreach($sectorSummary as $s)
                @php $pct = $totalResidents > 0 ? round($s['count'] / $totalResidents * 100) : 0; @endphp
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-xs text-gray-500">{{ $s['label'] }}</span>
                        <span class="text-xs font-semibold text-gray-800">{{ $s['count'] }}</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full {{ $s['bar'] }}"
                             x-data x-bind:style="{ width: '{{ $pct }}%' }"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@php
$monthlyTrendsJson = json_encode($monthlyBarangayTrends);
$statusValuesJson = json_encode(array_values($statusBreakdown));
$statusLabelsJson = json_encode(array_keys($statusBreakdown));
@endphp

<script>
// Data from PHP backend
const monthlyTrends = {!! $monthlyTrendsJson !!};
const statusBreakdownValues = {!! $statusValuesJson !!};
const statusBreakdownLabels = {!! $statusLabelsJson !!};

const trendChart = new ApexCharts(document.getElementById('trendChart'), {
    chart: { type: 'bar', height: 200, toolbar: { show: false } },
    series: [
        { name: 'Documents Issued', data: monthlyTrends.documentsIssued },
        { name: 'Complaints',       data: monthlyTrends.complaints },
        { name: 'New Residents',    data: monthlyTrends.newResidents },
    ],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    xaxis: {
        categories: monthlyTrends.labels,
        title: { text: 'Month', style: { fontSize: '11px', color: '#9ca3af' } },
        labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
        axisBorder: { show: false }, axisTicks: { show: false },
    },
    yaxis: {
        title: { text: 'Number of Records', style: { fontSize: '11px', color: '#9ca3af' } },
        labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
    },
    colors: ['#3b82f6', '#ef4444', '#1a4731'],
    legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#6b7280' } },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
    tooltip: { theme: 'light', shared: true, intersect: false },
    responsive: [{
        breakpoint: 640,
        options: {
            chart: { height: 240 },
            xaxis: {
                title: { text: undefined },
                labels: { style: { fontSize: '9px' }, rotate: -45, rotateAlways: true },
            },
            yaxis: {
                title: { text: undefined },
                labels: { style: { fontSize: '9px' } },
            },
            legend: { fontSize: '10px' },
        },
    }],
});
trendChart.render();

setInterval(() => {
    axios.get('{{ route('admin.dashboard.monthly-trends') }}')
        .then(res => trendChart.updateSeries([
            { name: 'Documents Issued', data: res.data.documentsIssued },
            { name: 'Complaints',       data: res.data.complaints },
            { name: 'New Residents',    data: res.data.newResidents },
        ]))
        .catch(() => {});
}, 60000);

new ApexCharts(document.getElementById('statusChart'), {
    chart: { type: 'donut', height: 200 },
    series: statusBreakdownValues,
    labels: statusBreakdownLabels,
    colors: ['#f59e0b', '#1a4731', '#ef4444'],
    legend: { position: 'bottom', fontSize: '11px', labels: { colors: '#6b7280' } },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: '65%' } } },
    stroke: { width: 0 },
}).render();
</script>

@endsection
