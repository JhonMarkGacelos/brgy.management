@extends('layouts.staff')
@section('title', 'Household Profiling')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Household Profiling</h2>
        <p class="text-sm text-gray-400 mt-0.5">Manage all registered households of Barangay Caranas</p>
    </div>
    <a href="{{ route('staff.residents.create') }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-plus text-xs"></i> Register Household
    </a>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $summary = [
            ['label'=>'Total Households', 'value'=> $totalHouseholds ?? 0, 'icon'=>'fa-house',      'bg'=>'bg-brand-100', 'color'=>'text-brand-700'],
            ['label'=>'Total Residents',  'value'=> $totalResidents  ?? 0, 'icon'=>'fa-users',      'bg'=>'bg-blue-100',  'color'=>'text-blue-700'],
            ['label'=>'Senior Citizens',  'value'=> $seniorCitizens  ?? 0, 'icon'=>'fa-person-cane', 'bg'=>'bg-orange-100','color'=>'text-orange-700'],
            ['label'=>'PWD Members',      'value'=> $pwdMembers      ?? 0, 'icon'=>'fa-wheelchair',  'bg'=>'bg-purple-100','color'=>'text-purple-700'],
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

{{-- Filters --}}
<div class="flex flex-col sm:flex-row gap-3 mb-5">
    <div class="relative flex-1">
        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
        <input type="text" placeholder="Search by household head name or address..."
               class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-sm text-gray-900 placeholder-gray-400
                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
    </div>
    <select class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Puroks</option>
        <option>Purok 1</option><option>Purok 2</option><option>Purok 3</option>
        <option>Purok 4</option><option>Purok 5</option>
    </select>
    <select class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-600
                   focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none">
        <option value="">All Sectors</option>
        <option>4Ps</option><option>Senior Citizen</option><option>PWD</option><option>Solo Parent</option>
    </select>
</div>

{{-- Household Table --}}
<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs font-semibold text-green-100 uppercase">
                    <th class="px-5 py-3.5">Household Head</th>
                    <th class="px-5 py-3.5">Address</th>
                    <th class="px-5 py-3.5">Purok</th>
                    <th class="px-5 py-3.5">Members</th>
                    <th class="px-5 py-3.5">Sectors</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sectorColors = [
                        '4Ps'        => 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
                        'Senior'     => 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
                        'PWD'        => 'bg-purple-50 text-purple-600 ring-1 ring-purple-100',
                        'Solo Parent'=> 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
                        'Voter'      => 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
                    ];
                @endphp

                @if($households->count() > 0)
                    @foreach($households as $h)
                    <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-brand-50/30 transition-colors group border-b border-gray-100 last:border-0">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 text-xs font-bold">
                                    {{ strtoupper(substr($h->head?->first_name ?? '?',0,1).substr($h->head?->last_name ?? '',0,1)) }}
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
                            <div class="flex flex-wrap gap-1">
                                @foreach($h->residents->pluck('sectors')->flatten()->unique() as $sector)
                                    <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-600' }}">{{ $sector }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('staff.residents.show', $h->id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                    <i class="fa-solid fa-eye text-[11px]"></i> View
                                </a>
                                <a href="{{ route('staff.residents.edit', $h->id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                    <i class="fa-solid fa-pen text-[11px]"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i class="fa-solid fa-house text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">No households registered yet</p>
                                    <p class="text-xs text-gray-400 mt-1">Start by registering the first household</p>
                                </div>
                                <a href="{{ route('staff.residents.create') }}"
                                   class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors"
                                   style="background-color:#1a4731;">
                                    <i class="fa-solid fa-plus text-xs"></i> Register First Household
                                </a>
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
