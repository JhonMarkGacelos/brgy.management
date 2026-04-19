@extends('layouts.staff')
@section('title', 'Blotter Records')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Blotter Records</h2>
        <p class="text-sm text-gray-500">Incident reports and case tracking</p>
    </div>
    <a href="{{ route('staff.blotter.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
        <i class="fa-solid fa-shield-halved"></i> File New Blotter
    </a>
</div>

{{-- Summary Cards --}}
@php
    $blotterStats = [
        ['label'=>'Filed This Month',    'value'=>$stats['this_month'] ?? 0, 'sub'=>now()->format('F Y'),  'icon'=>'fa-shield-halved', 'bg'=>'bg-red-50',    'color'=>'text-red-600',    'ring'=>'ring-red-100'],
        ['label'=>'Pending Official',    'value'=>$stats['pending']    ?? 0, 'sub'=>'Awaiting review',     'icon'=>'fa-clock',         'bg'=>'bg-amber-50',  'color'=>'text-amber-600',  'ring'=>'ring-amber-100'],
        ['label'=>'Returned w/ Remarks', 'value'=>$stats['returned']   ?? 0, 'sub'=>'Needs resubmit',      'icon'=>'fa-rotate-left',   'bg'=>'bg-orange-50', 'color'=>'text-orange-600', 'ring'=>'ring-orange-100'],
        ['label'=>'Under Mediation',     'value'=>$stats['mediation']  ?? 0, 'sub'=>'In progress',         'icon'=>'fa-handshake',     'bg'=>'bg-yellow-50', 'color'=>'text-yellow-600', 'ring'=>'ring-yellow-100'],
        ['label'=>'Settled',             'value'=>$stats['settled']    ?? 0, 'sub'=>'This month',          'icon'=>'fa-circle-check',  'bg'=>'bg-green-50',  'color'=>'text-green-600',  'ring'=>'ring-green-100'],
        ['label'=>'Open Cases',          'value'=>$stats['open']       ?? 0, 'sub'=>'Needs action',        'icon'=>'fa-circle-dot',    'bg'=>'bg-rose-50',   'color'=>'text-rose-600',   'ring'=>'ring-rose-100'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    @foreach($blotterStats as $s)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex items-start justify-between mb-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} ring-1 {{ $s['ring'] }} text-sm">
                <i class="fa-solid {{ $s['icon'] }}"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-0.5">{{ $s['value'] }}</p>
        <p class="text-xs font-semibold text-gray-700 leading-tight">{{ $s['label'] }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ $s['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Info Banner: Approval Flow --}}
<div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
    <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 shrink-0"></i>
    <p class="text-xs text-amber-800">
        Cases requiring <strong>official decision</strong> are marked <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 text-[10px] font-semibold px-2 py-0.5">Pending Official</span>.
        Once reviewed by the Barangay Official, status will be updated automatically.
    </p>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <i class="fa-solid fa-search text-sm"></i>
            </span>
            <input type="text" placeholder="Search by case no., complainant, respondent..."
                   class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="">All Statuses</option>
            <option>Open</option>
            <option>Pending Official</option>
            <option>Under Mediation</option>
            <option>Settled</option>
            <option>Referred</option>
            <option>Returned w/ Remarks</option>
        </select>
        <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="">All Incident Types</option>
            <option>Noise Complaint</option>
            <option>Physical Altercation</option>
            <option>Property Dispute</option>
            <option>Theft</option>
            <option>Domestic</option>
        </select>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs text-green-100 uppercase">
                    <th class="px-4 py-3">Case No.</th>
                    <th class="px-4 py-3">Date Filed</th>
                    <th class="px-4 py-3">Complainant</th>
                    <th class="px-4 py-3">Respondent</th>
                    <th class="px-4 py-3">Incident Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $statusColors = [
                        'Open'                => 'bg-red-100 text-red-700',
                        'Pending Official'    => 'bg-amber-100 text-amber-700',
                        'Under Mediation'     => 'bg-yellow-100 text-yellow-700',
                        'Settled'             => 'bg-green-100 text-green-700',
                        'Referred'            => 'bg-blue-100 text-blue-700',
                        'Returned w/ Remarks' => 'bg-orange-100 text-orange-700',
                    ];
                    $statusIcons = [
                        'Open'                => 'fa-circle-dot',
                        'Pending Official'    => 'fa-clock',
                        'Under Mediation'     => 'fa-handshake',
                        'Settled'             => 'fa-circle-check',
                        'Referred'            => 'fa-arrow-right-from-bracket',
                        'Returned w/ Remarks' => 'fa-rotate-left',
                    ];
                @endphp
                @forelse($records as $c)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-mono font-medium text-gray-800 text-xs">{{ $c->case_number }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $c->incident_date ? \Carbon\Carbon::parse($c->incident_date)->format('M d, Y') : '—' }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $c->complainant_name }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $c->respondent_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->incident_type }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$c->status] ?? 'bg-gray-100 text-gray-600' }}">
                            <i class="fa-solid {{ $statusIcons[$c->status] ?? 'fa-circle' }} text-[9px]"></i>
                            {{ $c->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('staff.blotter.show', $c->id) }}" title="View"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                <i class="fa-solid fa-eye text-[11px]"></i> View
                            </a>
                            @if($c->status === 'Returned w/ Remarks')
                            <a href="{{ route('staff.blotter.edit', $c->id) }}" title="Resubmit"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 transition-colors">
                                <i class="fa-solid fa-rotate-right text-[11px]"></i> Resubmit
                            </a>
                            @elseif(in_array($c->status, ['Open', 'Under Mediation']))
                            <a href="{{ route('staff.blotter.edit', $c->id) }}" title="Edit"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                <i class="fa-solid fa-pen text-[11px]"></i> Edit
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-shield-halved text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">No blotter records found</p>
                                <p class="text-xs text-gray-400 mt-1">File a new blotter case to get started</p>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
        <span class="text-xs text-gray-400">
            @if($records->total() > 0)
                Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }} cases
            @else
                No cases found
            @endif
        </span>
        <div class="flex gap-1">{{ $records->links() }}</div>
    </div>
</div>

@endsection
