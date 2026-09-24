@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Resident List')

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
    $hasFilters = request('search') || request('sector') || request('purok') || request('gender') || request('status') || request('employment_status');
    $sectorOptions = ['4Ps','Senior Citizen','Social Pension','Social Pension Candidates','PWD','Solo Parent','Voter','Indigent','Pregnant'];
    $employmentOptions = ['Employed','Self-Employed','Unemployed','Student','Retired'];
    $sectorColors = [
        '4Ps'        => 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
        'Senior'     => 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
        'Social Pension' => 'bg-teal-50 text-teal-700 ring-1 ring-teal-100',
        'PWD'        => 'bg-purple-50 text-purple-600 ring-1 ring-purple-100',
        'Solo Parent'=> 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
        'Voter'      => 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
        'Indigent'   => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        'Pregnant'   => 'bg-rose-50 text-rose-600 ring-1 ring-rose-100',
    ];
@endphp

{{-- Print-only letterhead --}}
<div class="print-header text-center mb-6 pb-4 border-b-2 border-gray-800">
    <p class="text-xs text-gray-500 uppercase tracking-widest">Republic of the Philippines · Province of Samar · Municipality of Motiong</p>
    <h1 class="text-2xl font-bold text-gray-900 mt-1">Barangay Caranas</h1>
    <h2 class="text-base font-semibold text-gray-700 mt-0.5">Resident List</h2>
    @if($hasFilters)
    <p class="text-xs text-gray-600 mt-1 font-medium">
        Filters:
        @if(request('search')) Search "{{ request('search') }}" @endif
        @if(request('sector')) &nbsp;·&nbsp; Sector: {{ request('sector') }} @endif
        @if(request('purok')) &nbsp;·&nbsp; Purok: {{ request('purok') }} @endif
        @if(request('gender')) &nbsp;·&nbsp; Gender: {{ request('gender') }} @endif
        @if(request('status')) &nbsp;·&nbsp; Status: {{ request('status') }} @endif
        @if(request('employment_status')) &nbsp;·&nbsp; Employment: {{ request('employment_status') }} @endif
    </p>
    @endif
    <p class="text-xs text-gray-400 mt-1">Printed: {{ now()->format('F j, Y h:i A') }} · {{ $residents->count() }} resident(s)</p>
</div>

{{-- Screen header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 no-print">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Resident List</h2>
        <p class="text-sm text-gray-400 mt-0.5">Individual residents of Barangay Caranas — filter by sector and print</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-arrow-left text-xs"></i> Household View
        </a>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-500 transition-colors px-4 py-2.5 text-sm font-semibold text-white">
            <i class="fa-solid fa-file-pdf"></i> PDF / Print
        </button>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route($isStaff ? 'staff.residents.list' : 'residents.list') }}" class="no-print">
<div class="flex flex-col sm:flex-row flex-wrap gap-3 mb-5">
    <div class="relative flex-1 min-w-[200px]">
        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by resident name..."
               class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-sm text-gray-900 placeholder-gray-400
                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
    </div>
    <select name="sector" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Sectors</option>
        @foreach($sectorOptions as $s)
        <option value="{{ $s }}" {{ request('sector') == $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <select name="purok" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Puroks</option>
        @foreach($puroks as $p)
        <option value="{{ $p }}" {{ request('purok') == $p ? 'selected' : '' }}>{{ $p }}</option>
        @endforeach
    </select>
    <select name="gender" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Genders</option>
        @foreach(['Male','Female'] as $g)
        <option value="{{ $g }}" {{ request('gender') == $g ? 'selected' : '' }}>{{ $g }}</option>
        @endforeach
    </select>
    <select name="status" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Statuses</option>
        @foreach(['Active','Inactive'] as $st)
        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
        @endforeach
    </select>
    <select name="employment_status" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Employment</option>
        @foreach($employmentOptions as $e)
        <option value="{{ $e }}" {{ request('employment_status') == $e ? 'selected' : '' }}>{{ $e }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
    </button>
    @if($hasFilters)
    <a href="{{ route($isStaff ? 'staff.residents.list' : 'residents.list') }}"
       class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-xmark text-xs"></i>
    </a>
    @endif
</div>
</form>

{{-- Resident Table --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    style="background-color:#1a4731;">
                    <th class="px-5 py-3.5 text-green-100">#</th>
                    <th class="px-5 py-3.5 text-green-100">Name</th>
                    <th class="px-5 py-3.5 text-green-100">Age</th>
                    <th class="px-5 py-3.5 text-green-100">Gender</th>
                    <th class="px-5 py-3.5 text-green-100">Civil Status</th>
                    <th class="px-5 py-3.5 text-green-100">Purok / Address</th>
                    <th class="px-5 py-3.5 text-green-100">Contact</th>
                    <th class="px-5 py-3.5 text-green-100">Employment</th>
                    <th class="px-5 py-3.5 text-green-100">Sectors</th>
                    <th class="px-5 py-3.5 text-green-100">Status</th>
                </tr>
            </thead>
            <tbody>
                @if($residents->count() > 0)
                    @foreach($residents as $i => $r)
                    @php
                        $household = $r->household;
                        $sectors = $r->sectors;
                        if ($r->is_indigent) $sectors[] = 'Indigent';
                    @endphp
                    <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-brand-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-5 py-4 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $r->full_name }}</p>
                            @if($r->is_head)
                            <p class="text-[11px] text-green-700 font-medium flex items-center gap-1 mt-0.5">
                                <i class="fa-solid fa-house-user text-[9px]"></i> Head of Household
                            </p>
                            @else
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $r->relationship_to_head ?? '—' }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $r->age ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $r->gender ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $r->civil_status ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-500 text-xs">
                            <span class="inline-flex items-center rounded-lg bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-700 mr-1">{{ $household->purok ?? '—' }}</span>
                            {{ $household?->house_no ? $household->house_no.' '.$household->street : ($household->street ?? '') }}
                        </td>
                        <td class="px-5 py-4 text-gray-500 text-xs">{{ $r->contact_number ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-500 text-xs">{{ $r->employment_status ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($sectors as $sector)
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-600' }}">{{ $sector }}</span>
                                @empty
                                    <span class="text-xs text-gray-300">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-semibold {{ $r->status === 'Active' ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200' }}">
                                {{ $r->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="10" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-{{ $hasFilters ? 'magnifying-glass' : 'users' }} text-lg"></i>
                                </div>
                                <div>
                                    @if($hasFilters)
                                    <p class="text-sm font-semibold text-gray-900">No residents found</p>
                                    <p class="text-xs text-gray-400 mt-1">No results match your search or filter. Try different keywords.</p>
                                    @else
                                    <p class="text-sm font-semibold text-gray-900">No residents registered yet</p>
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
            Showing {{ $residents->count() }} resident{{ $residents->count() == 1 ? '' : 's' }}
        </span>
    </div>
</div>

@endsection
