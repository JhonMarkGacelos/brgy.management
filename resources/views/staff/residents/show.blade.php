@extends('layouts.staff')
@section('title', 'Household Profile')

@section('content')

@php
    $isDemo = !isset($household);
    $headName     = $isDemo ? 'Juan dela Cruz'         : ($household->head?->full_name ?? '—');
    $headInitials = $isDemo ? 'JC'                     : strtoupper(substr($household->head?->first_name ?? '?',0,1).substr($household->head?->last_name ?? '',0,1));
    $purok        = $isDemo ? 'Purok 1'                : $household->purok;
    $address      = $isDemo ? '123 Rizal St., Purok 1' : $household->full_address;
    $memberCount  = $isDemo ? 4                        : $household->residents->count();

    $demoMembers = [
        ['name'=>'Juan dela Cruz',  'initials'=>'JC','age'=>34,'gender'=>'Male',  'civil'=>'Married','relation'=>'Head',    'sectors'=>['Voter'],             'status'=>'Active','avatarBg'=>'bg-brand-100 text-brand-700','is_head'=>true],
        ['name'=>'Rosa dela Cruz',  'initials'=>'RD','age'=>31,'gender'=>'Female','civil'=>'Married','relation'=>'Spouse',  'sectors'=>['Voter','Solo Parent'],'status'=>'Active','avatarBg'=>'bg-pink-100 text-pink-700',  'is_head'=>false],
        ['name'=>'Carlo dela Cruz', 'initials'=>'CD','age'=>12,'gender'=>'Male',  'civil'=>'Single', 'relation'=>'Son',     'sectors'=>[],                    'status'=>'Active','avatarBg'=>'bg-blue-100 text-blue-700',  'is_head'=>false],
        ['name'=>'Liza dela Cruz',  'initials'=>'LD','age'=>8, 'gender'=>'Female','civil'=>'Single', 'relation'=>'Daughter','sectors'=>[],                    'status'=>'Active','avatarBg'=>'bg-teal-100 text-teal-700',  'is_head'=>false],
    ];
    $sectorColors = [
        '4Ps'        => 'bg-blue-50 text-blue-600 ring-1 ring-blue-100',
        'Senior'     => 'bg-orange-50 text-orange-600 ring-1 ring-orange-100',
        'PWD'        => 'bg-purple-50 text-purple-600 ring-1 ring-purple-100',
        'Solo Parent'=> 'bg-pink-50 text-pink-600 ring-1 ring-pink-100',
        'Voter'      => 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
    ];
    $avatarColors = ['bg-brand-100 text-brand-700','bg-blue-100 text-blue-700','bg-orange-100 text-orange-700','bg-purple-100 text-purple-700','bg-pink-100 text-pink-700','bg-teal-100 text-teal-700'];
    $hasMemberIncome = !$isDemo && $household->residents->sum('monthly_income') > 0;
    $classification  = $hasMemberIncome
        ? \App\Services\ClassificationService::classify($household)
        : null;
@endphp

<div class="flex items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('staff.residents.index') }}"
           class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900">Household Profile</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $address }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('staff.residents.edit', $isDemo ? 1 : $household->id) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
            <i class="fa-solid fa-pen text-[11px]"></i> Edit
        </a>
        <a href="#add-member"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold text-white transition-colors shadow-sm"
           style="background-color:#1a4731;">
            <i class="fa-solid fa-user-plus text-[11px]"></i> Add Member
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    <div class="xl:col-span-2 space-y-5">

        {{-- Head Card --}}
        <div class="rounded-2xl bg-white border-2 border-brand-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-100" style="background-color:#f0faf4;">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-house-user text-brand-600 text-sm"></i>
                    <p class="text-sm font-bold text-brand-700">Head of Household</p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-600 text-white text-[10px] font-bold px-2.5 py-1">
                    <i class="fa-solid fa-star text-[8px]"></i> HEAD
                </span>
            </div>
            @php $head = $isDemo ? $demoMembers[0] : $household->head; @endphp
            @if($head)
            <div class="p-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-700 text-lg font-bold">
                        {{ $isDemo ? $head['initials'] : strtoupper(substr($head->first_name,0,1).substr($head->last_name,0,1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-base font-bold text-gray-900">{{ $isDemo ? $head['name'] : $head->full_name }}</p>
                        <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-500">
                            <span><i class="fa-solid fa-venus-mars mr-1 text-gray-400"></i>{{ $isDemo ? $head['gender'] : $head->gender }}</span>
                            <span><i class="fa-solid fa-cake-candles mr-1 text-gray-400"></i>Age {{ $isDemo ? $head['age'] : $head->age }}</span>
                            <span><i class="fa-solid fa-ring mr-1 text-gray-400"></i>{{ $isDemo ? $head['civil'] : $head->civil_status }}</span>
                        </div>
                    </div>
                    @if(!$isDemo && $head->monthly_income !== null)
                    @php $povertyLine = \App\Models\Setting::get('poverty_line', 10957); @endphp
                    <div class="text-right shrink-0">
                        <p class="text-[11px] text-gray-400">Monthly Income</p>
                        <p class="text-xs font-semibold {{ $head->monthly_income <= $povertyLine ? 'text-red-600' : 'text-gray-700' }}">
                            ₱{{ number_format($head->monthly_income, 2) }}
                        </p>
                        @if($head->monthly_income <= $povertyLine)
                        <p class="text-[10px] text-red-500 font-medium mt-0.5">Below poverty line</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Members Table --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-900">Household Members</p>
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-brand-600 text-white text-[10px] font-bold">{{ $memberCount }}</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Relationship</th>
                            <th class="px-5 py-3">Age / Gender</th>
                            <th class="px-5 py-3">Monthly Income</th>
                            <th class="px-5 py-3">Sectors</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $members = $isDemo ? $demoMembers : $household->residents; @endphp
                        @foreach($members as $i => $m)
                        @php
                            $mName     = $isDemo ? $m['name']    : $m->full_name;
                            $mInits    = $isDemo ? $m['initials'] : strtoupper(substr($m->first_name,0,1).substr($m->last_name,0,1));
                            $mAge      = $isDemo ? $m['age']      : $m->age;
                            $mGender   = $isDemo ? $m['gender']   : $m->gender;
                            $mRelation = $isDemo ? $m['relation'] : ($m->relationship_to_head ?? 'Head');
                            $mSectors  = $isDemo ? $m['sectors']  : $m->sectors;
                            $mStatus   = $isDemo ? $m['status']   : $m->status;
                            $mIsHead   = $isDemo ? $m['is_head']  : $m->is_head;
                            $mBg       = $avatarColors[$i % count($avatarColors)];
                        @endphp
                        <tr class="odd:bg-white even:bg-gray-50/60 hover:bg-brand-50/30 transition-colors border-b border-gray-100 last:border-0 group">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $mBg }} text-xs font-bold">{{ $mInits }}</div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $mName }}</p>
                                        @if($mIsHead)<p class="text-[10px] text-brand-600 font-semibold">Head</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-lg {{ $mIsHead ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'bg-gray-50 text-gray-600 ring-1 ring-gray-100' }} px-2.5 py-1 text-xs font-medium">
                                    {{ $mRelation }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600">{{ $mAge }} yrs &middot; {{ $mGender }}</td>
                            <td class="px-5 py-3.5 text-xs">
                                @if(!$isDemo && $m->monthly_income !== null)
                                    @php $pl = \App\Models\Setting::get('poverty_line', 10957); @endphp
                                    <span class="font-semibold {{ $m->monthly_income <= $pl ? 'text-red-600' : 'text-gray-700' }}">
                                        ₱{{ number_format($m->monthly_income, 2) }}
                                    </span>
                                    @if($m->monthly_income <= $pl)
                                    <span class="block text-[10px] text-red-500 font-medium">Below poverty line</span>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($mSectors as $sector)
                                        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-500' }}">{{ $sector }}</span>
                                    @empty
                                        <span class="text-xs text-gray-300">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium
                                    {{ $mStatus === 'Active' ? 'bg-green-50 text-green-700 ring-1 ring-green-100' : 'bg-gray-50 text-gray-400 ring-1 ring-gray-100' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $mStatus === 'Active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    {{ $mStatus }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="#" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                        <i class="fa-solid fa-pen text-[11px]"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Right --}}
    <div class="space-y-5">

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-house"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Household Info</p>
            </div>
            <div class="p-5 space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400">Purok / Sitio</span>
                    <span class="text-xs font-semibold text-gray-800">{{ $purok }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400">Full Address</span>
                    <span class="text-xs font-semibold text-gray-800 text-right max-w-[150px]">{{ $address }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-xs text-gray-400">Total Members</span>
                    <span class="text-sm font-bold text-brand-700">{{ $memberCount }}</span>
                </div>
            </div>
        </div>

        {{-- Welfare Classification --}}
        @if(!$isDemo)
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-500 text-xs">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Welfare Classification</p>
            </div>
            <div class="p-5 space-y-4">
                @if($classification)
                <div class="rounded-xl border-2 p-4 text-center {{ $classification['bg'] }}">
                    <p class="text-[10px] font-semibold uppercase tracking-widest {{ $classification['color'] }} opacity-60 mb-1">Family Status</p>
                    <p class="text-xl font-bold {{ $classification['color'] }}">{{ $classification['classification'] }}</p>
                    <p class="text-xs {{ $classification['color'] }} opacity-70 mt-0.5">Score: {{ $classification['final_score'] }} / 100</p>
                </div>

                <div class="rounded-xl bg-gray-50 border border-gray-100 p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Total Member Income</span>
                        <span class="text-xs font-bold text-gray-800">₱{{ number_format($classification['total_income'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Members</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $classification['member_count'] }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-2">
                        <span class="text-xs font-semibold text-gray-600">Per Capita Income</span>
                        <span class="text-sm font-bold text-gray-900">₱{{ number_format($classification['per_capita'], 2) }}</span>
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 border border-gray-100 p-3 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Base Score</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $classification['base_score'] }}</span>
                    </div>
                    @if($classification['total_modifier'] < 0)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">Modifiers</span>
                        <span class="text-xs font-semibold text-red-500">{{ $classification['total_modifier'] }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-gray-200 pt-1.5">
                        <span class="text-xs font-semibold text-gray-600">Final Score</span>
                        <span class="text-sm font-bold {{ $classification['color'] }}">{{ $classification['final_score'] }}</span>
                    </div>
                </div>

                {{-- Formula (collapsible) --}}
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex w-full items-center justify-between rounded-xl border border-dashed border-gray-200 px-3 py-2.5 text-xs font-semibold text-gray-500 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-solid fa-function text-gray-400"></i>
                            How it's calculated
                        </span>
                        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200"
                           :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open" x-transition class="mt-2 rounded-xl border border-gray-100 bg-gray-50 p-3.5 space-y-3.5 text-xs text-gray-600">
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Step 1 — Per Capita Income</p>
                            <p class="font-mono text-[11px] text-gray-500 bg-white border border-gray-100 rounded-lg px-3 py-2 leading-relaxed">
                                Per Capita = Total Income ÷ Members<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= ₱{{ number_format($classification['total_income'], 2) }} ÷ {{ $classification['member_count'] }}<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= <span class="font-bold text-gray-800">₱{{ number_format($classification['per_capita'], 2) }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Step 2 — Base Score (0–100)</p>
                            @php
                                $t = $classification['thresholds'];
                                $bands = [
                                    ['range' => '≤ ₱'.number_format($t['extremely_poor']),                                                'score' => '0–20'],
                                    ['range' => '₱'.number_format($t['extremely_poor']).' – ₱'.number_format($t['poor']),                 'score' => '20–40'],
                                    ['range' => '₱'.number_format($t['poor']).' – ₱'.number_format($t['near_poor']),                      'score' => '40–60'],
                                    ['range' => '₱'.number_format($t['near_poor']).' – ₱'.number_format($t['vulnerable']),                'score' => '60–80'],
                                    ['range' => '> ₱'.number_format($t['vulnerable']),                                                    'score' => '80–100'],
                                ];
                                $activeBand = match(true) {
                                    $classification['per_capita'] <= $t['extremely_poor'] => 0,
                                    $classification['per_capita'] <= $t['poor']           => 1,
                                    $classification['per_capita'] <= $t['near_poor']      => 2,
                                    $classification['per_capita'] <= $t['vulnerable']     => 3,
                                    default                                               => 4,
                                };
                            @endphp
                            <div class="space-y-1">
                                @foreach($bands as $bi => $band)
                                <div class="flex items-center justify-between rounded-lg px-2.5 py-1.5
                                    {{ $bi === $activeBand ? 'bg-brand-50 border border-brand-100 font-semibold' : 'bg-white border border-gray-100' }}">
                                    <span class="text-[11px] {{ $bi === $activeBand ? 'text-brand-700' : 'text-gray-500' }}">
                                        @if($bi === $activeBand)<i class="fa-solid fa-arrow-right text-brand-500 mr-1 text-[10px]"></i>@endif
                                        {{ $band['range'] }}
                                    </span>
                                    <span class="text-[11px] {{ $bi === $activeBand ? 'text-brand-700' : 'text-gray-400' }}">{{ $band['score'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 mb-1">Step 3 — Final Score</p>
                            <p class="font-mono text-[11px] text-gray-500 bg-white border border-gray-100 rounded-lg px-3 py-2 leading-relaxed">
                                Final = Base Score + Modifiers<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= {{ $classification['base_score'] }} + ({{ $classification['total_modifier'] }})<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;= <span class="font-bold text-gray-800">{{ $classification['final_score'] }} / 100</span>
                            </p>
                        </div>
                    </div>
                </div>

                @else
                <div class="rounded-xl bg-gray-50 border border-dashed border-gray-200 p-4 text-center">
                    <i class="fa-solid fa-circle-info text-gray-300 text-lg mb-2"></i>
                    <p class="text-xs text-gray-400">No income sources recorded yet.</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 space-y-2.5" id="add-member">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</p>
            <a href="#"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-brand-50 hover:bg-brand-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white text-xs">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <span class="text-xs font-semibold text-brand-700">Add Family Member</span>
            </a>
            <a href="{{ route('staff.documents.create') }}"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-blue-50 hover:bg-blue-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500 text-white text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <span class="text-xs font-semibold text-blue-700">Request Document</span>
            </a>
            <a href="{{ route('staff.blotter.create') }}"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-red-50 hover:bg-red-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 text-white text-xs">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <span class="text-xs font-semibold text-red-700">File Blotter Report</span>
            </a>
        </div>

    </div>
</div>

@endsection
