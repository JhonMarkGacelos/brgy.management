@extends('layouts.staff')
@section('title', 'Document Requests')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Document Requests</h2>
        <p class="text-sm text-gray-500">Process, track, and issue barangay documents</p>
    </div>
    <a href="{{ route('staff.documents.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-file-circle-plus text-xs"></i> New Document Request
    </a>
</div>

{{-- Document Type Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $docSummary = [
            ['label'=>'Barangay Clearance',       'count'=>42, 'fee'=>'₱50',  'icon'=>'fa-file-shield',         'iconBg'=>'bg-brand-100',  'iconColor'=>'text-brand-700'],
            ['label'=>'Cert. of Residency',       'count'=>28, 'fee'=>'₱50',  'icon'=>'fa-house-flag',          'iconBg'=>'bg-blue-100',   'iconColor'=>'text-blue-700'],
            ['label'=>'Cert. of Indigency',       'count'=>11, 'fee'=>'Free', 'icon'=>'fa-hand-holding-heart',  'iconBg'=>'bg-orange-100', 'iconColor'=>'text-orange-700'],
            ['label'=>'Business Clearance',       'count'=>6,  'fee'=>'₱200', 'icon'=>'fa-briefcase',           'iconBg'=>'bg-purple-100', 'iconColor'=>'text-purple-700'],
        ];
    @endphp
    @foreach($docSummary as $ds)
    <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $ds['iconBg'] }} {{ $ds['iconColor'] }} text-sm">
                <i class="fa-solid {{ $ds['icon'] }}"></i>
            </div>
            <span class="text-xs font-semibold text-gray-400">{{ $ds['fee'] }}</span>
        </div>
        <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $ds['count'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $ds['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <i class="fa-solid fa-search text-sm"></i>
            </span>
            <input type="text" placeholder="Search by resident name or OR number..."
                   class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="">All Document Types</option>
            <option>Barangay Clearance</option>
            <option>Certificate of Residency</option>
            <option>Certificate of Indigency</option>
            <option>Business Clearance</option>
        </select>
        <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="">All Statuses</option>
            <option>Pending Official</option>
            <option>Approved</option>
            <option>Issued</option>
            <option>Rejected</option>
        </select>
    </div>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs text-green-100 uppercase">
                    <th class="px-4 py-3">Tracking No.</th>
                    <th class="px-4 py-3">Resident Name</th>
                    <th class="px-4 py-3">Document Type</th>
                    <th class="px-4 py-3">Date Requested</th>
                    <th class="px-4 py-3">Fee</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $docs = [
                        ['no'=>'OR-2024-087','resident'=>'Pedro Reyes',    'type'=>'Barangay Clearance',      'date'=>'Apr 17, 2024','fee'=>'₱50', 'status'=>'Issued'],
                        ['no'=>'OR-2024-086','resident'=>'Luz Fernandez',  'type'=>'Cert. of Residency',      'date'=>'Apr 17, 2024','fee'=>'₱50', 'status'=>'Issued'],
                        ['no'=>'OR-2024-085','resident'=>'Maria Santos',   'type'=>'Cert. of Indigency',      'date'=>'Apr 17, 2024','fee'=>'Free','status'=>'Pending Official'],
                        ['no'=>'OR-2024-084','resident'=>'Rene Aquino',    'type'=>'Barangay Clearance',      'date'=>'Apr 16, 2024','fee'=>'₱50', 'status'=>'Approved'],
                        ['no'=>'OR-2024-083','resident'=>'TechMart Store', 'type'=>'Business Clearance',      'date'=>'Apr 16, 2024','fee'=>'₱200','status'=>'Rejected'],
                        ['no'=>'OR-2024-082','resident'=>'Juan dela Cruz', 'type'=>'Barangay Clearance',      'date'=>'Apr 15, 2024','fee'=>'₱50', 'status'=>'Pending Official'],
                    ];
                    $statusColors = [
                        'Issued'          => 'bg-brand-100 text-brand-700',
                        'Approved'        => 'bg-green-100 text-green-700',
                        'Pending Official'=> 'bg-amber-100 text-amber-700',
                        'Rejected'        => 'bg-red-100 text-red-700',
                    ];
                    $statusIcons = [
                        'Issued'          => 'fa-file-circle-check',
                        'Approved'        => 'fa-circle-check',
                        'Pending Official'=> 'fa-clock',
                        'Rejected'        => 'fa-circle-xmark',
                    ];
                @endphp
                @foreach($docs as $d)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-mono font-medium text-gray-700 text-xs">{{ $d['no'] }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $d['resident'] }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $d['type'] }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $d['date'] }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-700 text-xs">{{ $d['fee'] }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$d['status']] }}">
                            <i class="fa-solid {{ $statusIcons[$d['status']] }} text-[9px]"></i>
                            {{ $d['status'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <a href="#" title="View"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                <i class="fa-solid fa-eye text-[11px]"></i> View
                            </a>
                            @if($d['status'] === 'Approved')
                            <button title="Issue & Print"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-white transition-colors"
                               style="background-color:#1a4731;">
                                <i class="fa-solid fa-print text-[11px]"></i> Issue
                            </button>
                            @elseif($d['status'] === 'Issued')
                            <button title="Reprint"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 transition-colors">
                                <i class="fa-solid fa-print text-[11px]"></i>
                            </button>
                            @elseif($d['status'] === 'Rejected')
                            <a href="{{ route('staff.documents.create') }}" title="Resubmit"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 transition-colors">
                                <i class="fa-solid fa-rotate-right text-[11px]"></i> Resubmit
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
        <span>Showing 1–6 of 87 documents</span>
        <div class="flex gap-1">
            <button class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40" disabled>
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button class="px-3 py-1 rounded border text-white text-xs" style="background-color:#1a4731;">1</button>
            <button class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50 text-xs">2</button>
            <button class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
        </div>
    </div>
</div>

@endsection
