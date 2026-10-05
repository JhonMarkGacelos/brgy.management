@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Household Profile')

@section('content')

@php
$isStaff = Auth::user()->role === 'staff';
    // Demo data if no real household passed
    $isDemo = !isset($household);
    $headName     = $isDemo ? 'Juan dela Cruz'    : ($household->head?->full_name ?? '—');
    $headInitials = $isDemo ? 'JC'                : strtoupper(substr($household->head?->first_name ?? '?', 0,1).substr($household->head?->last_name ?? '', 0,1));
    $purok        = $isDemo ? 'Purok 1'           : $household->purok;
    $address      = $isDemo ? '123 Rizal St., Purok 1' : $household->full_address;
    $memberCount  = $isDemo ? 4                   : $household->residents->count();

    $demoMembers = [
        ['name'=>'Juan dela Cruz',  'initials'=>'JC', 'age'=>34,'gender'=>'Male',  'civil'=>'Married','relation'=>'Head',     'sectors'=>['Voter'],             'status'=>'Active','avatarBg'=>'bg-brand-100 text-brand-700','is_head'=>true],
        ['name'=>'Rosa dela Cruz',  'initials'=>'RD', 'age'=>31,'gender'=>'Female','civil'=>'Married','relation'=>'Spouse',   'sectors'=>['Voter','Solo Parent'],'status'=>'Active','avatarBg'=>'bg-pink-100 text-pink-700',  'is_head'=>false],
        ['name'=>'Carlo dela Cruz', 'initials'=>'CD', 'age'=>12,'gender'=>'Male',  'civil'=>'Single', 'relation'=>'Son',      'sectors'=>[],                    'status'=>'Active','avatarBg'=>'bg-blue-100 text-blue-700',  'is_head'=>false],
        ['name'=>'Liza dela Cruz',  'initials'=>'LD', 'age'=>8, 'gender'=>'Female','civil'=>'Single', 'relation'=>'Daughter', 'sectors'=>[],                    'status'=>'Active','avatarBg'=>'bg-teal-100 text-teal-700',  'is_head'=>false],
    ];

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
    $avatarColors = ['bg-brand-100 text-brand-700','bg-blue-100 text-blue-700','bg-orange-100 text-orange-700','bg-purple-100 text-purple-700','bg-pink-100 text-pink-700','bg-teal-100 text-teal-700'];
    $povertyLine  = \App\Services\ClassificationService::psaThresholds()['poverty'];
@endphp

{{-- Page Header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}"
           class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h2 class="text-base font-semibold text-gray-900">Household Profile</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $address }}</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route($isStaff ? 'staff.residents.edit' : 'residents.edit', $isDemo ? 1 : $household->id) }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
            <i class="fa-solid fa-pen text-[11px]"></i> Edit Household
        </a>
        <a href="#add-member"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold text-white transition-colors shadow-sm"
           style="background-color:#1a4731;">
            <i class="fa-solid fa-user-plus text-[11px]"></i> Add Member
        </a>
    </div>
</div>

{{-- Needs review: contradictory records (advisory only) --}}
@if(!$isDemo && ($reviewFlags = $household->reviewFlags()))
<div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
    <p class="font-semibold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation text-amber-500"></i> Needs review</p>
    <ul class="mt-1.5 list-disc pl-6 space-y-0.5 text-xs">
        @foreach($reviewFlags as $flag)<li>{{ $flag }}</li>@endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left: Members Table --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Head of Household Card --}}
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
                            @if($isDemo ? $head['sectors'] : $head->sectors)
                            @foreach(($isDemo ? $head['sectors'] : $head->sectors) as $sector)
                            @php
                                $sectorIdUrl = match(true) {
                                    $sector === '4Ps' && !$isDemo && $head->fourps_id_url => \App\Http\Controllers\IdPhotoController::link('fourps', $head),
                                    $sector === 'Social Pension' && !$isDemo && $head->senior_id_url => \App\Http\Controllers\IdPhotoController::link('senior', $head),
                                    $sector === 'PWD' && !$isDemo && $head->pwd_id_url => \App\Http\Controllers\IdPhotoController::link('pwd', $head),
                                    $sector === 'Solo Parent' && !$isDemo && $head->solo_parent_id_url => \App\Http\Controllers\IdPhotoController::link('solo_parent', $head),
                                    default => null,
                                };
                                $idLabel = $sector === 'Social Pension' ? 'Senior Citizen ID' : $sector . ' ID';
                            @endphp
                            @if($sectorIdUrl)
                            <button type="button" onclick="viewIdPhoto('{{ $sectorIdUrl }}', '{{ $idLabel }} — {{ addslashes($head->full_name) }}')" title="View {{ $idLabel }}"
                               class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? '' }} hover:brightness-95 transition cursor-pointer">
                                {{ $sector }} <i class="fa-solid fa-image text-[9px]"></i>
                            </button>
                            @else
                            <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? '' }}">{{ $sector }}</span>
                            @endif
                            @endforeach
                            @endif
                        </div>
                    </div>
                    @if(!$isDemo && ($head->contact_number || $head->monthly_income !== null))
                    <div class="text-right shrink-0 space-y-1">
                        @if($head->contact_number)
                        <div>
                            <p class="text-[11px] text-gray-400">Contact</p>
                            <p class="text-xs font-semibold text-gray-700">{{ $head->contact_number }}</p>
                        </div>
                        @endif
                        @if($head->monthly_income !== null)
                        <div>
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
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- All Members Table --}}
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
                        <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Relationship</th>
                            <th class="px-5 py-3">Age / Gender</th>
                            <th class="px-5 py-3">Monthly Income</th>
                            <th class="px-5 py-3">Sectors</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $members = $isDemo ? $demoMembers : $household->residents; @endphp
                        @foreach($members as $i => $m)
                        @php
                            $mName     = $isDemo ? $m['name']     : $m->full_name;
                            $mInits    = $isDemo ? $m['initials']  : strtoupper(substr($m->first_name,0,1).substr($m->last_name,0,1));
                            $mAge      = $isDemo ? $m['age']       : $m->age;
                            $mGender   = $isDemo ? $m['gender']    : $m->gender;
                            $mRelation = $isDemo ? $m['relation']  : ($m->relationship_to_head ?? 'Head');
                            $mSectors  = $isDemo ? $m['sectors']   : $m->sectors;
                            $mIsHead   = $isDemo ? $m['is_head']   : $m->is_head;
                            $mBg       = $avatarColors[$i % count($avatarColors)];
                        @endphp
                        <tr class="odd:bg-white even:bg-gray-50/60 hover:bg-brand-50/30 transition-colors border-b border-gray-100 last:border-0 group">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $mBg }} text-xs font-bold">
                                        {{ $mInits }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $mName }}</p>
                                        @if($mIsHead)
                                        <p class="text-[10px] text-brand-600 font-semibold">Head</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center rounded-lg {{ $mIsHead ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' : 'bg-gray-50 text-gray-600 ring-1 ring-gray-100' }} px-2.5 py-1 text-xs font-medium">
                                    {{ $mRelation }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-600">
                                {{ $mAge }} yrs &middot; {{ $mGender }}
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                @if(!$isDemo && $m->monthly_income !== null)
                                    <span class="font-semibold {{ $m->monthly_income <= $povertyLine ? 'text-red-600' : 'text-gray-700' }}">
                                        ₱{{ number_format($m->monthly_income, 2) }}
                                    </span>
                                    @if($m->monthly_income <= $povertyLine)
                                    <span class="block text-[10px] text-red-500 font-medium">Below poverty line</span>
                                    @endif
                                @elseif($isDemo || $m->pension_amount === null)
                                    <span class="text-gray-300">—</span>
                                @endif
                                @if(!$isDemo && $m->pension_amount !== null)
                                    <span class="block text-[10px] text-teal-700 font-medium">+ ₱{{ number_format($m->pension_amount, 2) }} pension</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($mSectors as $sector)
                                        @php
                                            $sectorIdUrl = match(true) {
                                                $sector === '4Ps' && !$isDemo && $m->fourps_id_url => \App\Http\Controllers\IdPhotoController::link('fourps', $m),
                                                $sector === 'Social Pension' && !$isDemo && $m->senior_id_url => \App\Http\Controllers\IdPhotoController::link('senior', $m),
                                                $sector === 'PWD' && !$isDemo && $m->pwd_id_url => \App\Http\Controllers\IdPhotoController::link('pwd', $m),
                                                $sector === 'Solo Parent' && !$isDemo && $m->solo_parent_id_url => \App\Http\Controllers\IdPhotoController::link('solo_parent', $m),
                                                default => null,
                                            };
                                            $idLabel = $sector === 'Social Pension' ? 'Senior Citizen ID' : $sector . ' ID';
                                        @endphp
                                        @if($sectorIdUrl)
                                        <button type="button" onclick="viewIdPhoto('{{ $sectorIdUrl }}', '{{ $idLabel }} — {{ addslashes($m->full_name) }}')" title="View {{ $idLabel }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-500' }} hover:brightness-95 transition cursor-pointer">
                                            {{ $sector }} <i class="fa-solid fa-image text-[9px]"></i>
                                        </button>
                                        @else
                                        <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-medium {{ $sectorColors[$sector] ?? 'bg-gray-50 text-gray-500' }}">{{ $sector }}</span>
                                        @endif
                                    @empty
                                        <span class="text-xs text-gray-300">—</span>
                                    @endforelse
                                    @if(!$isDemo && $m->is_social_pension_candidate)
                                        <span title="Senior, indigent and without a pension — may qualify for DSWD Social Pension"
                                              class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-medium bg-white text-teal-700 border border-dashed border-teal-300">
                                            <i class="fa-solid fa-hand-holding-heart text-[9px]"></i> Possible Social Pension candidate
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if(!$isDemo)
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="openEditMember({{ json_encode([
                                        'id'           => $m->id,
                                        'household_id' => $household->id,
                                        'first_name'   => $m->first_name,
                                        'middle_name'  => $m->middle_name,
                                        'last_name'    => $m->last_name,
                                        'age'          => $m->age,
                                        'gender'       => $m->gender,
                                        'civil_status' => $m->civil_status,
                                        'relationship' => $m->relationship_to_head,
                                        'contact_number'    => $m->contact_number,
                                        'employment_status' => $m->employment_status,
                                        'education'         => $m->education,
                                        'monthly_income'    => $m->monthly_income,
                                        'date_of_birth'     => $m->date_of_birth?->format('Y-m-d'),
                                        'is_head'           => $m->is_head,
                                        'is_4ps'            => $m->is_4ps,
                                        'is_senior_citizen' => $m->is_senior_citizen,
                                        'pension'           => $m->pension,
                                        'pension_amount'    => $m->pension_amount,
                                        'senior_id_url'     => \App\Http\Controllers\IdPhotoController::link('senior', $m),
                                        'is_pwd'            => $m->is_pwd,
                                        'is_solo_parent'    => $m->is_solo_parent,
                                        'is_voter'          => $m->is_voter,
                                        'is_indigent'       => $m->is_indigent,
                                        'is_pregnant'       => $m->is_pregnant,
                                        'pregnant_due_date' => $m->pregnant_due_date?->format('Y-m-d'),
                                        'pwd_id_url'         => \App\Http\Controllers\IdPhotoController::link('pwd', $m),
                                        'solo_parent_id_url' => \App\Http\Controllers\IdPhotoController::link('solo_parent', $m),
                                        'fourps_id_url'      => \App\Http\Controllers\IdPhotoController::link('fourps', $m),
                                    ]) }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                        <i class="fa-solid fa-pen text-[11px]"></i> Edit
                                    </button>
                                    @if(!$mIsHead)
                                    <form method="POST"
                                          action="{{ route($isStaff ? 'staff.residents.member.destroy' : 'residents.member.destroy', [$household->id, $m->id]) }}"
                                          onsubmit="return confirm('Remove {{ addslashes($mName) }} from this household?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 transition-colors">
                                            <i class="fa-solid fa-trash text-[11px]"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Right: Household Info + Stats --}}
    <div class="space-y-5">

        {{-- Household Info --}}
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
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400">Total Members</span>
                    <span class="text-sm font-bold text-brand-700">{{ $memberCount }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-xs text-gray-400">Date Registered</span>
                    <span class="text-xs font-semibold text-gray-800">
                        {{ $isDemo ? 'Jan 5, 2024' : ($household->created_at?->format('M j, Y') ?? '—') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Member Breakdown --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Composition</p>
            </div>
            <div class="p-5 space-y-3">
                @php
                    $realMembers = $isDemo ? collect($demoMembers) : $household->residents;
                    $composition = [
                        ['label'=>'Adults (18–59)',  'color'=>'bg-brand-500',
                         'count'=> $isDemo ? 2 : $realMembers->filter(fn($r) => $r->age >= 18 && $r->age <= 59)->count()],
                        ['label'=>'Children (0–17)', 'color'=>'bg-blue-400',
                         'count'=> $isDemo ? 2 : $realMembers->filter(fn($r) => $r->age !== null && $r->age < 18)->count()],
                        ['label'=>'Senior (60+)',    'color'=>'bg-orange-400',
                         'count'=> $isDemo ? 0 : $realMembers->filter(fn($r) => $r->is_senior_citizen)->count()],
                        ['label'=>'PWD',             'color'=>'bg-purple-400',
                         'count'=> $isDemo ? 0 : $realMembers->filter(fn($r) => $r->is_pwd)->count()],
                    ];
                    $totalComp = max(array_sum(array_column($composition,'count')),1);
                @endphp
                @foreach($composition as $c)
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-xs text-gray-500">{{ $c['label'] }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $c['count'] }}</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full {{ $c['color'] }}"
                             style="width: {{ round($c['count']/$totalComp*100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Poverty Status (PSA): the household's only classification --}}
        @if(!$isDemo)
        @php $psa = \App\Services\ClassificationService::psa($household); @endphp
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 text-xs">
                    <i class="fa-solid fa-landmark"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800">Poverty Status</p>
                    <p class="text-[11px] text-gray-400">Per capita income vs. PSA thresholds &middot; income only</p>
                </div>
            </div>

            <div class="p-5 space-y-4">
                @if(!$psa['configured'])
                <div class="rounded-xl bg-amber-50 border border-dashed border-amber-200 p-4 text-center text-xs text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg mb-2 block"></i>
                    PSA thresholds haven't been set yet.
                    @if(!$isStaff)
                    <a href="{{ route('settings.index') }}" class="mt-1 block font-semibold text-amber-900 hover:underline">Set them in Settings →</a>
                    @else
                    <span class="mt-1 block text-amber-600">Ask an administrator to set them in Settings.</span>
                    @endif
                </div>
                @elseif(!$psa['assessed'])
                <div class="rounded-xl bg-gray-50 border border-dashed border-gray-200 p-4 text-center text-xs text-gray-400">
                    <i class="fa-solid fa-circle-info text-gray-300 text-lg mb-2 block"></i>
                    Not assessed &mdash; no income or pension recorded for any member.
                    <a href="{{ route($isStaff ? 'staff.residents.edit' : 'residents.edit', $household->id) }}"
                       class="mt-2 block font-semibold text-green-700 hover:underline">
                        Add income →
                    </a>
                </div>
                @else
                @php $psaStyle = \App\Services\ClassificationService::PSA_STYLES[$psa['status']]; @endphp
                <div class="rounded-xl p-4 text-center {{ $psaStyle }}">
                    <p class="text-[10px] font-semibold uppercase tracking-widest opacity-60 mb-1">Poverty Status</p>
                    <p class="text-xl font-bold">{{ $psa['status'] }}</p>
                    <p class="text-xs opacity-70 mt-0.5">
                        {{ in_array($psa['status'], \App\Services\ClassificationService::PSA_POOR, true) ? 'Below the PSA poverty line' : 'Not poor' }}
                        &middot; {{ $psa['multiple'] }}&times; poverty line
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 overflow-hidden">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                                <th class="px-3 py-1.5 text-left font-semibold">Class</th>
                                <th class="px-3 py-1.5 text-right font-semibold">Per capita / month</th>
                            </tr>
                        </thead>
                        @foreach($psa['bands'] as $band)
                        @php $isActive = $band['status'] === $psa['status']; @endphp
                        <tr class="border-t border-gray-50 {{ $isActive ? 'bg-sky-50/60 font-semibold text-gray-900' : 'text-gray-500' }}">
                            <td class="px-3 py-1.5">
                                @if($isActive)<i class="fa-solid fa-arrow-right text-sky-500 mr-1 text-[10px]"></i>@endif
                                {{ $band['status'] }}
                            </td>
                            <td class="px-3 py-1.5 text-right font-mono">
                                @if($band['max'] === null) ₱{{ number_format($band['min']) }}+
                                @elseif($band['min'] == 0) &lt; ₱{{ number_format($band['max']) }}
                                @else ₱{{ number_format($band['min']) }} – &lt; ₱{{ number_format($band['max']) }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="text-[11px] text-gray-500 space-y-1">
                    <p class="flex justify-between"><span>Total monthly income</span><span class="font-semibold text-gray-700">₱{{ number_format($psa['total_income'], 2) }}</span></p>
                    @if($psa['pension_income'] > 0)
                    <p class="flex justify-between pl-3 text-teal-700"><span>incl. pension</span><span>₱{{ number_format($psa['pension_income'], 2) }}</span></p>
                    @endif
                    <p class="flex justify-between"><span>Household members</span><span class="font-semibold text-gray-700">{{ $psa['member_count'] }}</span></p>
                    <p class="flex justify-between"><span>Per capita income</span><span class="font-semibold text-gray-800">₱{{ number_format($psa['per_capita'], 2) }}</span></p>
                    @if($psa['food'])
                    <p class="flex justify-between"><span>Food threshold</span><span>₱{{ number_format($psa['food'], 2) }}</span></p>
                    @endif
                    <p class="flex justify-between"><span>Poverty threshold</span><span>₱{{ number_format($psa['poverty'], 2) }}</span></p>
                    <p class="pt-1 border-t border-gray-100 text-gray-400">
                        Source: {{ $psa['source'] }}. Classes above the poverty line follow the PIDS income classes (multiples of the poverty line).
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Quick Actions --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 space-y-2.5" id="add-member">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</p>
            {{-- Members are added on the household edit form --}}
            <a href="{{ route($isStaff ? 'staff.residents.edit' : 'residents.edit', $isDemo ? 1 : $household->id) }}"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-brand-50 hover:bg-brand-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white text-xs">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <span class="text-xs font-semibold text-brand-700">Add Family Member</span>
            </a>
            <a href="{{ route($isStaff ? 'staff.documents.create' : 'documents.create') }}"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-blue-50 hover:bg-blue-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500 text-white text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <span class="text-xs font-semibold text-blue-700">Issue Document</span>
            </a>
            <a href="{{ route($isStaff ? 'staff.blotter.create' : 'blotter.create') }}"
               class="w-full flex items-center gap-3 rounded-xl p-3 bg-red-50 hover:bg-red-100 transition-colors">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 text-white text-xs">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <span class="text-xs font-semibold text-red-700">File Complaint Report</span>
            </a>
        </div>

    </div>
</div>

{{-- ── EDIT MEMBER MODAL ── --}}
<div id="modal-edit-member" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
     style="@if(!$isStaff) left:0; @else left:0; @endif">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0" style="background-color:#1a4731;">
            <p class="text-sm font-semibold text-white"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit Member</p>
            <button onclick="document.getElementById('modal-edit-member').classList.add('hidden')"
                    class="text-white/60 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form id="edit-member-form" method="POST" enctype="multipart/form-data" class="overflow-y-auto">
            @csrf @method('PUT')
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">First Name</label>
                    <input type="text" name="first_name" id="em_first_name" required
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Middle Name</label>
                    <input type="text" name="middle_name" id="em_middle_name"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" id="em_last_name" required
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Relationship to Head</label>
                    <input type="text" name="relationship" id="em_relationship"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="em_dob" onchange="toggleSeniorPensionModal()"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Age</label>
                    <input type="number" name="age" id="em_age" min="0"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Gender</label>
                    <select name="gender" id="em_gender" required
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Civil Status</label>
                    <select name="civil_status" id="em_civil"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">Select</option>
                        <option>Single</option><option>Married</option>
                        <option>Widowed</option><option>Separated</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Contact Number</label>
                    <input type="text" name="contact_number" id="em_contact"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Gmail Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm"><i class="fa-brands fa-google"></i></span>
                        <input type="email" name="email" id="em_email"
                               placeholder="example@gmail.com"
                               class="w-full rounded-xl border border-gray-200 pl-10 pr-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Employment Status</label>
                    <select name="employment_status" id="em_employment" onchange="toggleStudentLevelModal()"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">Select</option>
                        <option>Employed</option><option>Unemployed</option>
                        <option>Self-Employed</option><option>Student</option><option>Out of School Youth</option><option>Retired</option>
                    </select>
                </div>
                <div id="em_education_wrap" class="hidden">
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Student Level <span class="text-red-500">*</span></label>
                    <select name="education" id="em_education"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                        <option value="">Select</option>
                        @foreach(\App\Models\Resident::STUDENT_LEVELS as $level)
                        <option>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5">Monthly Income (₱)</label>
                    <input type="number" name="monthly_income" id="em_monthly_income" min="0" step="0.01"
                           placeholder="0.00"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Sector Classification</label>
                    <div class="flex flex-wrap gap-3">
                        <label id="em_4ps_wrap" class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="is_4ps" id="em_is_4ps" value="1"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                   onchange="toggleIdUploadModal('fourps', this.checked)">
                            4Ps
                        </label>
                        @foreach(['is_voter'=>'Voter','is_indigent'=>'Indigent'] as $field => $label)
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="{{ $field }}" id="em_{{ $field }}" value="1"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            {{ $label }}
                        </label>
                        @endforeach
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="is_pwd" id="em_is_pwd" value="1"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                   onchange="toggleIdUploadModal('pwd', this.checked)">
                            PWD
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="is_solo_parent" id="em_is_solo_parent" value="1"
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                   onchange="toggleIdUploadModal('solo_parent', this.checked)">
                            Solo Parent
                        </label>
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="is_pregnant" id="em_is_pregnant" value="1"
                                   class="rounded border-gray-300 text-rose-500 focus:ring-rose-400"
                                   onchange="togglePregnantDueDateModal(this.checked)">
                            Pregnant
                        </label>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-400">
                        <i class="fa-solid fa-person-cane mr-1 text-orange-400"></i>Senior Citizen is set automatically from date of birth (60+).
                    </p>
                    <div id="em_senior_pension_wrap" class="hidden mt-3">
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5">Pension</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach(['none' => 'None', 'social' => 'DSWD Social Pension', 'other' => 'SSS / GSIS / Other pension'] as $value => $label)
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                <input type="radio" name="pension" id="em_pension_{{ $value }}" value="{{ $value }}"
                                       class="border-gray-300 text-teal-600 focus:ring-teal-500"
                                       onchange="toggleSeniorPensionModal()">
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                        <div id="em_pension_amount_wrap" class="hidden mt-2">
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">Monthly Pension Amount (₱) <span class="text-red-500">*</span></label>
                            <input type="number" name="pension_amount" id="em_pension_amount" min="0" step="0.01" placeholder="0.00"
                                   class="w-40 rounded-xl border border-teal-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <p class="mt-1 text-[10px] text-gray-400">Counted as household income. Don't include it in Monthly Income as well.</p>
                        </div>
                        <div id="em_senior_id_wrap" class="hidden mt-2">
                            <label class="block text-[11px] font-semibold text-gray-500 mb-1">
                                Senior Citizen (OSCA) ID <span id="em_senior_id_required_hint" class="text-red-500">*</span>
                            </label>
                            <div id="em_senior_id_existing" class="hidden mb-2">
                                <button type="button" onclick="viewIdPhoto(document.getElementById('em_senior_id_existing_img').src, 'Senior Citizen ID')" class="block relative rounded-xl overflow-hidden border-2 border-gray-200 max-w-[200px]">
                                    <img id="em_senior_id_existing_img" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                    <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 text-center">
                                        <span class="text-[10px] text-white">On file &middot; click to view</span>
                                    </div>
                                </button>
                            </div>
                            <label class="block w-full max-w-[200px] cursor-pointer">
                                <input type="file" name="senior_id_document" id="em_senior_id_document" accept="image/*" class="sr-only"
                                       onchange="handleIdFileChange('senior', this)">
                                <div id="em_senior_id_dropzone" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-teal-200 bg-teal-50/40 px-4 py-5 hover:border-teal-400 hover:bg-teal-50 transition-all">
                                    <i class="fa-solid fa-id-card text-teal-400 text-base"></i>
                                    <p class="text-[10px] text-teal-600 text-center">Click to upload &middot; JPG/PNG/WebP, max 5MB</p>
                                </div>
                                <div id="em_senior_id_preview_wrap" class="hidden relative rounded-xl overflow-hidden border-2 border-green-400">
                                    <img id="em_senior_id_preview" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                    <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 flex items-center justify-between gap-2">
                                        <span id="em_senior_id_filename" class="text-[10px] text-white truncate"></span>
                                        <span class="text-[10px] text-green-300 font-semibold shrink-0"><i class="fa-solid fa-check mr-1"></i>Ready</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div id="em_pregnant_due_wrap" class="hidden mt-2">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Due Date / Expected Labor Date</label>
                        <input type="date" name="pregnant_due_date" id="em_pregnant_due_date"
                               class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400">
                    </div>
                    <div id="em_fourps_id_wrap" class="hidden mt-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">
                            4Ps ID <span id="em_fourps_id_required_hint" class="text-red-500">*</span>
                        </label>
                        <div id="em_fourps_id_existing" class="hidden mb-2">
                            <button type="button" onclick="viewIdPhoto(document.getElementById('em_fourps_id_existing_img').src, '4Ps ID')" class="block relative rounded-xl overflow-hidden border-2 border-gray-200 max-w-[200px]">
                                <img id="em_fourps_id_existing_img" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 text-center">
                                    <span class="text-[10px] text-white">On file &middot; click to view</span>
                                </div>
                            </button>
                        </div>
                        <label class="block w-full max-w-[200px] cursor-pointer">
                            <input type="file" name="fourps_id_document" id="em_fourps_id_document" accept="image/*" class="sr-only"
                                   onchange="handleIdFileChange('fourps', this)">
                            <div id="em_fourps_id_dropzone" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-blue-200 bg-blue-50/40 px-4 py-5 hover:border-blue-400 hover:bg-blue-50 transition-all">
                                <i class="fa-solid fa-id-card text-blue-400 text-base"></i>
                                <p class="text-[10px] text-blue-600 text-center">Click to upload &middot; JPG/PNG/WebP, max 5MB</p>
                            </div>
                            <div id="em_fourps_id_preview_wrap" class="hidden relative rounded-xl overflow-hidden border-2 border-green-400">
                                <img id="em_fourps_id_preview" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 flex items-center justify-between gap-2">
                                    <span id="em_fourps_id_filename" class="text-[10px] text-white truncate"></span>
                                    <span class="text-[10px] text-green-300 font-semibold shrink-0"><i class="fa-solid fa-check mr-1"></i>Ready</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div id="em_pwd_id_wrap" class="hidden mt-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">
                            PWD ID <span id="em_pwd_id_required_hint" class="text-red-500">*</span>
                        </label>
                        <div id="em_pwd_id_existing" class="hidden mb-2">
                            <button type="button" onclick="viewIdPhoto(document.getElementById('em_pwd_id_existing_img').src, 'PWD ID')" class="block relative rounded-xl overflow-hidden border-2 border-gray-200 max-w-[200px]">
                                <img id="em_pwd_id_existing_img" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 text-center">
                                    <span class="text-[10px] text-white">On file &middot; click to view</span>
                                </div>
                            </button>
                        </div>
                        <label class="block w-full max-w-[200px] cursor-pointer">
                            <input type="file" name="pwd_id_document" id="em_pwd_id_document" accept="image/*" class="sr-only"
                                   onchange="handleIdFileChange('pwd', this)">
                            <div id="em_pwd_id_dropzone" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-purple-200 bg-purple-50/40 px-4 py-5 hover:border-purple-400 hover:bg-purple-50 transition-all">
                                <i class="fa-solid fa-id-card text-purple-400 text-base"></i>
                                <p class="text-[10px] text-purple-600 text-center">Click to upload &middot; JPG/PNG/WebP, max 5MB</p>
                            </div>
                            <div id="em_pwd_id_preview_wrap" class="hidden relative rounded-xl overflow-hidden border-2 border-green-400">
                                <img id="em_pwd_id_preview" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 flex items-center justify-between gap-2">
                                    <span id="em_pwd_id_filename" class="text-[10px] text-white truncate"></span>
                                    <span class="text-[10px] text-green-300 font-semibold shrink-0"><i class="fa-solid fa-check mr-1"></i>Ready</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div id="em_solo_parent_id_wrap" class="hidden mt-2">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">
                            Solo Parent ID <span id="em_solo_parent_id_required_hint" class="text-red-500">*</span>
                        </label>
                        <div id="em_solo_parent_id_existing" class="hidden mb-2">
                            <button type="button" onclick="viewIdPhoto(document.getElementById('em_solo_parent_id_existing_img').src, 'Solo Parent ID')" class="block relative rounded-xl overflow-hidden border-2 border-gray-200 max-w-[200px]">
                                <img id="em_solo_parent_id_existing_img" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 text-center">
                                    <span class="text-[10px] text-white">On file &middot; click to view</span>
                                </div>
                            </button>
                        </div>
                        <label class="block w-full max-w-[200px] cursor-pointer">
                            <input type="file" name="solo_parent_id_document" id="em_solo_parent_id_document" accept="image/*" class="sr-only"
                                   onchange="handleIdFileChange('solo_parent', this)">
                            <div id="em_solo_parent_id_dropzone" class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-pink-200 bg-pink-50/40 px-4 py-5 hover:border-pink-400 hover:bg-pink-50 transition-all">
                                <i class="fa-solid fa-id-card text-pink-400 text-base"></i>
                                <p class="text-[10px] text-pink-600 text-center">Click to upload &middot; JPG/PNG/WebP, max 5MB</p>
                            </div>
                            <div id="em_solo_parent_id_preview_wrap" class="hidden relative rounded-xl overflow-hidden border-2 border-green-400">
                                <img id="em_solo_parent_id_preview" src="" class="w-full max-h-24 object-contain bg-gray-100">
                                <div class="absolute bottom-0 inset-x-0 bg-black/50 px-2 py-1 flex items-center justify-between gap-2">
                                    <span id="em_solo_parent_id_filename" class="text-[10px] text-white truncate"></span>
                                    <span class="text-[10px] text-green-300 font-semibold shrink-0"><i class="fa-solid fa-check mr-1"></i>Ready</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 px-5 pb-5 shrink-0">
                <button type="button" onclick="document.getElementById('modal-edit-member').classList.add('hidden')"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── VIEW ID PHOTO LIGHTBOX ── --}}
<div id="modal-view-id" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
     onclick="if (event.target === this) closeViewIdPhoto()">
    <div class="relative w-full max-w-md">
        <button type="button" onclick="closeViewIdPhoto()"
                class="absolute -top-9 right-0 text-white/80 hover:text-white transition-colors">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        <div class="rounded-2xl overflow-hidden bg-white shadow-xl">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <p id="view_id_title" class="text-sm font-semibold text-gray-800"></p>
                <a id="view_id_original_link" href="#" target="_blank" class="text-[11px] text-gray-400 hover:text-gray-600 shrink-0">View original</a>
            </div>
            <img id="view_id_image" src="" class="w-full max-h-[70vh] object-contain bg-gray-100">
        </div>
    </div>
</div>

<script>
function viewIdPhoto(url, title) {
    document.getElementById('view_id_image').src = url;
    document.getElementById('view_id_title').textContent = title;
    document.getElementById('view_id_original_link').href = url;
    document.getElementById('modal-view-id').classList.remove('hidden');
}

function closeViewIdPhoto() {
    document.getElementById('modal-view-id').classList.add('hidden');
    document.getElementById('view_id_image').src = '';
}

function openEditMember(data) {
    const isStaff = {{ $isStaff ? 'true' : 'false' }};
    const base    = isStaff ? '/staff/residents' : '/residents';
    document.getElementById('edit-member-form').action = base + '/' + data.household_id + '/member/' + data.id;

    document.getElementById('em_first_name').value   = data.first_name   || '';
    document.getElementById('em_middle_name').value  = data.middle_name  || '';
    document.getElementById('em_last_name').value    = data.last_name    || '';
    document.getElementById('em_relationship').value = data.relationship || '';
    document.getElementById('em_dob').value          = data.date_of_birth || '';
    document.getElementById('em_age').value          = data.age          || '';
    document.getElementById('em_gender').value       = data.gender       || '';
    document.getElementById('em_civil').value        = data.civil_status || '';
    document.getElementById('em_contact').value        = data.contact_number || '';
    document.getElementById('em_email').value          = data.email || '';
    document.getElementById('em_employment').value     = data.employment_status || '';
    document.getElementById('em_education').value      = data.education || '';
    toggleStudentLevelModal();
    document.getElementById('em_monthly_income').value = data.monthly_income != null ? data.monthly_income : '';

    // Show 4Ps only for head of household
    const fourPsWrap = document.getElementById('em_4ps_wrap');
    fourPsWrap.style.display = data.is_head ? '' : 'none';
    document.getElementById('em_is_4ps').checked = !!data.is_4ps;

    ['is_pwd','is_solo_parent','is_voter','is_indigent','is_pregnant'].forEach(function(f) {
        document.getElementById('em_' + f).checked = !!data[f];
    });

    document.getElementById('em_pregnant_due_date').value = data.pregnant_due_date || '';
    togglePregnantDueDateModal(!!data.is_pregnant);

    document.getElementById('em_pension_' + (data.pension || 'none')).checked = true;
    document.getElementById('em_pension_amount').value = data.pension_amount != null ? data.pension_amount : '';
    setIdDocumentState('senior', false, data.senior_id_url || null);
    toggleSeniorPensionModal();

    setIdDocumentState('fourps', !!data.is_head && !!data.is_4ps, data.fourps_id_url || null);
    setIdDocumentState('pwd', !!data.is_pwd, data.pwd_id_url || null);
    setIdDocumentState('solo_parent', !!data.is_solo_parent, data.solo_parent_id_url || null);

    document.getElementById('modal-edit-member').classList.remove('hidden');
}

// Pension options only apply to seniors (60+); the server clears them for anyone younger.
function toggleSeniorPensionModal() {
    const dob = document.getElementById('em_dob').value;
    let isSenior = false;
    if (dob) {
        const [y, m, d] = dob.split('-').map(Number);
        const today = new Date();
        let age = today.getFullYear() - y;
        if (today.getMonth() + 1 < m || (today.getMonth() + 1 === m && today.getDate() < d)) age--;
        isSenior = age >= 60;
    }
    document.getElementById('em_senior_pension_wrap').classList.toggle('hidden', !isSenior);

    const pension = (document.querySelector('input[name="pension"]:checked') || {}).value || 'none';
    const hasPension = isSenior && pension !== 'none';
    document.getElementById('em_pension_amount_wrap').classList.toggle('hidden', !hasPension);
    document.getElementById('em_pension_amount').required = hasPension;
    toggleIdUploadModal('senior', isSenior && pension === 'social');
}

function toggleStudentLevelModal() {
    const isStudent = document.getElementById('em_employment').value === 'Student';
    document.getElementById('em_education_wrap').classList.toggle('hidden', !isStudent);
    document.getElementById('em_education').disabled = !isStudent;
}

function togglePregnantDueDateModal(show) {
    document.getElementById('em_pregnant_due_wrap').classList.toggle('hidden', !show);
}

const emIdState = { senior: { existingUrl: null }, fourps: { existingUrl: null }, pwd: { existingUrl: null }, solo_parent: { existingUrl: null } };

// Resets and (re)initializes an ID upload block for the resident currently being edited.
function setIdDocumentState(prefix, checked, existingUrl) {
    emIdState[prefix].existingUrl = existingUrl;

    document.getElementById('em_' + prefix + '_id_document').value = '';
    document.getElementById('em_' + prefix + '_id_preview').src = '';
    document.getElementById('em_' + prefix + '_id_filename').textContent = '';
    document.getElementById('em_' + prefix + '_id_preview_wrap').classList.add('hidden');
    document.getElementById('em_' + prefix + '_id_dropzone').classList.remove('hidden');

    const existingWrap = document.getElementById('em_' + prefix + '_id_existing');
    if (existingUrl) {
        document.getElementById('em_' + prefix + '_id_existing_img').src = existingUrl;
        existingWrap.classList.remove('hidden');
    } else {
        existingWrap.classList.add('hidden');
    }

    toggleIdUploadModal(prefix, checked);
}

function toggleIdUploadModal(prefix, checked) {
    document.getElementById('em_' + prefix + '_id_wrap').classList.toggle('hidden', !checked);

    const fileInput = document.getElementById('em_' + prefix + '_id_document');
    const requiresUpload = checked && !emIdState[prefix].existingUrl;
    fileInput.required = requiresUpload;
    document.getElementById('em_' + prefix + '_id_required_hint').style.display = requiresUpload ? '' : 'none';
}

function handleIdFileChange(prefix, inputEl) {
    const f = inputEl.files[0];
    if (!f) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('em_' + prefix + '_id_preview').src = e.target.result;
        document.getElementById('em_' + prefix + '_id_filename').textContent = f.name;
        document.getElementById('em_' + prefix + '_id_preview_wrap').classList.remove('hidden');
        document.getElementById('em_' + prefix + '_id_dropzone').classList.add('hidden');
    };
    reader.readAsDataURL(f);
}
</script>

@endsection
