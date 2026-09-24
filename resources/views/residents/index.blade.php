@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Household Profiling')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Household Profiling</h2>
        <p class="text-sm text-gray-400 mt-0.5">Manage all registered households of Barangay Caranas</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($isStaff ? 'staff.residents.list' : 'residents.list') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
            <i class="fa-solid fa-list text-xs"></i> View Resident List
        </a>
        <a href="{{ route($isStaff ? 'staff.residents.create' : 'residents.create') }}"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
           style="background-color:#1a4731;"
           onmouseover="this.style.backgroundColor='#2d6a4f'"
           onmouseout="this.style.backgroundColor='#1a4731'">
            <i class="fa-solid fa-plus text-xs"></i> Register Household
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
    @php
        $summary = [
            ['label'=>'Total Families',   'value'=> $totalHouseholds ?? 0, 'icon'=>'fa-house-chimney-user', 'bg'=>'bg-brand-100', 'color'=>'text-brand-700'],
            ['label'=>'Total Residents',  'value'=> $totalResidents  ?? 0, 'icon'=>'fa-users',              'bg'=>'bg-blue-100',  'color'=>'text-blue-700'],
            ['label'=>'Senior Citizens',  'value'=> $seniorCitizens  ?? 0, 'icon'=>'fa-person-cane',        'bg'=>'bg-orange-100','color'=>'text-orange-700'],
            ['label'=>'PWD Members',      'value'=> $pwdMembers      ?? 0, 'icon'=>'fa-wheelchair',         'bg'=>'bg-purple-100','color'=>'text-purple-700'],
        ];
    @endphp
    @foreach($summary as $s)
    <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm">
        <div class="flex items-start justify-between mb-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $s['label'] }}</p>
            <div class="flex h-8 w-8 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} text-sm">
                <i class="fa-solid {{ $s['icon'] }}"></i>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ number_format($s['value']) }}</p>
    </div>
    @endforeach
</div>

{{-- Welfare Classification Cards --}}
@php
    $tiers = [
        ['label'=>'Extremely Poor','key'=>'Extremely Poor','dot'=>'bg-red-500',   'bg'=>'bg-red-50',    'border'=>'border-red-200',   'color'=>'text-red-700'],
        ['label'=>'Poor',          'key'=>'Poor',          'dot'=>'bg-orange-500','bg'=>'bg-orange-50', 'border'=>'border-orange-200','color'=>'text-orange-700'],
        ['label'=>'Near Poor',     'key'=>'Near Poor',     'dot'=>'bg-yellow-500','bg'=>'bg-yellow-50', 'border'=>'border-yellow-200','color'=>'text-yellow-700'],
        ['label'=>'Vulnerable',    'key'=>'Vulnerable',    'dot'=>'bg-blue-500',  'bg'=>'bg-blue-50',   'border'=>'border-blue-200',  'color'=>'text-blue-700'],
        ['label'=>'Non-Poor',      'key'=>'Non-Poor',      'dot'=>'bg-green-500', 'bg'=>'bg-green-50',  'border'=>'border-green-200', 'color'=>'text-green-700'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">
    @foreach($tiers as $t)
    @php $count = $classificationCounts[$t['key']] ?? 0; @endphp
    <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index', array_merge(request()->query(), ['classification' => $t['key']])) }}"
       class="rounded-2xl border-2 p-4 shadow-sm transition-all hover:shadow-md {{ request('classification') === $t['key'] ? $t['bg'].' '.$t['border'] : 'bg-white border-gray-100' }}">
        <div class="flex items-center gap-2 mb-2">
            <span class="h-2.5 w-2.5 rounded-full {{ $t['dot'] }} shrink-0"></span>
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide truncate">{{ $t['label'] }}</p>
        </div>
        <p class="text-2xl font-bold {{ request('classification') === $t['key'] ? $t['color'] : 'text-gray-900' }}">{{ number_format($count) }}</p>
        <p class="text-[11px] text-gray-400 mt-0.5">{{ $count == 1 ? 'family' : 'families' }}</p>
    </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}">
<div class="flex flex-col sm:flex-row gap-3 mb-5">
    <div class="relative flex-1">
        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by household head name or address..."
               class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-sm text-gray-900 placeholder-gray-400
                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
    </div>
    <select name="purok" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Puroks</option>
        @foreach($puroks as $p)
        <option value="{{ $p }}" {{ request('purok') == $p ? 'selected' : '' }}>{{ $p }}</option>
        @endforeach
    </select>
    <select name="sector" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Sectors</option>
        @foreach(['4Ps','Senior Citizen','PWD','Solo Parent'] as $s)
        <option value="{{ $s }}" {{ request('sector') == $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <select name="classification" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Welfare Scores</option>
        @foreach(['Extremely Poor','Poor','Near Poor','Vulnerable','Non-Poor'] as $c)
        <option value="{{ $c }}" {{ request('classification') == $c ? 'selected' : '' }}>{{ $c }}</option>
        @endforeach
    </select>
    <select name="psa_status" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All PSA Status</option>
        <option value="below" {{ request('psa_status') === 'below' ? 'selected' : '' }}>Below PSA poverty line</option>
        @foreach(\App\Services\ClassificationService::PSA_STATUSES as $ps)
        <option value="{{ $ps }}" {{ request('psa_status') === $ps ? 'selected' : '' }}>{{ $ps }}</option>
        @endforeach
    </select>
    <label class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm cursor-pointer
                  {{ request('review') ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-200 bg-white text-gray-600' }}">
        <input type="checkbox" name="review" value="1" onchange="this.form.submit()" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400"
               {{ request('review') ? 'checked' : '' }}>
        Needs review
    </label>
    <select name="employment_status" onchange="this.form.submit()"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Employment</option>
        @foreach(['Employed','Self-Employed','Unemployed','Student','Retired'] as $e)
        <option value="{{ $e }}" {{ request('employment_status') == $e ? 'selected' : '' }}>{{ $e }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
    </button>
    @if(request('search') || request('purok') || request('sector') || request('classification') || request('psa_status') || request('review') || request('employment_status'))
    <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}"
       class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors">
        <i class="fa-solid fa-xmark text-xs"></i>
    </a>
    @endif
</div>
</form>

{{-- Household Table --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide"
                    style="background-color:#1a4731;">
                    <th class="px-5 py-3.5 text-green-100">Household Head</th>
                    <th class="px-5 py-3.5 text-green-100">Address</th>
                    <th class="px-5 py-3.5 text-green-100">Purok</th>
                    <th class="px-5 py-3.5 text-green-100">Members</th>
                    <th class="px-5 py-3.5 text-green-100">Welfare Score</th>
                    <th class="px-5 py-3.5 text-green-100">PSA Status</th>
                    <th class="px-5 py-3.5 text-green-100">Sectors</th>
                    <th class="px-5 py-3.5 text-green-100 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sectorColors = [
                        '4Ps'        => 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
                        'Senior'     => 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
                        'Social Pension' => 'bg-teal-50 text-teal-700 ring-1 ring-teal-100',
                        'PWD'        => 'bg-purple-50 text-purple-600 ring-1 ring-purple-100',
                        'Solo Parent'=> 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
                        'Voter'      => 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
                        'Indigent'   => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
                    ];
                @endphp

                @if($households->count() > 0)
                    @foreach($households as $h)
                    <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-brand-50/30 transition-colors group border-b border-gray-100 last:border-0">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-xs font-bold">
                                    {{ strtoupper(substr($h->head?->first_name ?? '?', 0, 1) . substr($h->head?->last_name ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $h->head?->full_name ?? '—' }}</p>
                                    <p class="text-[11px] text-green-700 font-medium flex items-center gap-1 mt-0.5">
                                        <i class="fa-solid fa-house-user text-[9px]"></i> Head of Household
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-500 text-xs">{{ $h->house_no ? $h->house_no.' '.$h->street : ($h->street ?? '—') }}</td>
                        <td class="px-5 py-4"><span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $h->purok }}</span></td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-brand-600 text-white text-xs font-bold">{{ $h->residents_count }}</span>
                                <span class="text-xs text-gray-400">{{ $h->residents_count == 1 ? 'member' : 'members' }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @php
                                $cls = $h->classification;
                                $clsStyle = match($cls) {
                                    'Extremely Poor' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                    'Poor'           => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                                    'Near Poor'      => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                                    'Vulnerable'     => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                    'Non-Poor'       => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                                    default          => null,
                                };
                            @endphp
                            @if($cls && $clsStyle)
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold {{ $clsStyle }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ match($cls) {
                                        'Extremely Poor'=>'bg-red-500','Poor'=>'bg-orange-500',
                                        'Near Poor'=>'bg-yellow-500','Vulnerable'=>'bg-blue-500',
                                        default=>'bg-green-500'} }}"></span>
                                    {{ $cls }}
                                </span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($h->psa_status)
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-semibold {{ \App\Services\ClassificationService::PSA_STYLES[$h->psa_status] ?? '' }}">{{ $h->psa_status }}</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($h->residents->pluck('sectors')->flatten()->unique() as $sector)
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-600' }}">{{ $sector }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route($isStaff ? 'staff.residents.show' : 'residents.show', $h->id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                    <i class="fa-solid fa-eye text-[11px]"></i> View
                                </a>
                                <a href="{{ route($isStaff ? 'staff.residents.edit' : 'residents.edit', $h->id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                    <i class="fa-solid fa-pen text-[11px]"></i> Edit
                                </a>
                                <form method="POST" action="{{ route($isStaff ? 'staff.residents.destroy' : 'residents.destroy', $h->id) }}" onsubmit="return confirm('Delete this household and all its members?')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 transition-colors">
                                        <i class="fa-solid fa-trash text-[11px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-{{ request('search') || request('purok') || request('sector') || request('classification') || request('psa_status') || request('review') || request('employment_status') ? 'magnifying-glass' : 'house' }} text-lg"></i>
                                </div>
                                <div>
                                    @if(request('search') || request('purok') || request('sector') || request('classification') || request('psa_status') || request('review') || request('employment_status'))
                                    <p class="text-sm font-semibold text-gray-900">No households found</p>
                                    <p class="text-xs text-gray-400 mt-1">No results match your search or filter. Try different keywords.</p>
                                    @else
                                    <p class="text-sm font-semibold text-gray-900">No households registered yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Start by registering your first household</p>
                                    @endif
                                </div>
                                @if(!request('search') && !request('purok') && !request('sector') && !request('classification') && !request('employment_status'))
                                <a href="{{ route($isStaff ? 'staff.residents.create' : 'residents.create') }}"
                                   class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors"
                                   style="background-color:#1a4731;">
                                    <i class="fa-solid fa-plus text-xs"></i> Register First Household
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-5 py-3.5 border-t border-gray-100">
        <span class="text-xs text-gray-400">
            @if($households->total() > 0)
                Showing {{ $households->firstItem() }}–{{ $households->lastItem() }} of {{ $households->total() }} households
            @else
                No households found
            @endif
        </span>
        <div class="flex gap-1">{{ $households->links() }}</div>
    </div>
</div>

@endsection
