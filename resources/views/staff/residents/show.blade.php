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
