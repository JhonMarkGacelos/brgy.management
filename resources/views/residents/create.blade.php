@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', isset($household) ? 'Edit Household' : 'Register Household')

@section('content')

@php
$isStaff  = Auth::user()->role === 'staff';
$isEdit   = isset($household);

// Build init data for Alpine when editing
$initData = null;
if ($isEdit) {
    $head    = $household->residents->firstWhere('is_head', true);
    $members = $household->residents->where('is_head', false)->values();
    $initData = [[
        'head' => [
            'first_name'        => $head?->first_name ?? '',
            'middle_name'       => $head?->middle_name ?? '',
            'last_name'         => $head?->last_name ?? '',
            'date_of_birth'     => $head?->date_of_birth?->format('Y-m-d') ?? '',
            'age'               => $head?->age ?? '',
            'gender'            => $head?->gender ?? '',
            'civil_status'      => $head?->civil_status ?? '',
            'contact_number'    => $head?->contact_number ?? '',
            'employment_status' => $head?->employment_status ?? '',
            'sectors' => [
                '4ps'            => (bool)($head?->is_4ps),
                'senior_citizen' => (bool)($head?->is_senior_citizen),
                'pwd'            => (bool)($head?->is_pwd),
                'solo_parent'    => (bool)($head?->is_solo_parent),
                'voter'          => (bool)($head?->is_voter),
                'indigent'       => (bool)($head?->is_indigent),
            ],
        ],
        'members' => $members->map(fn($m) => [
            'first_name'    => $m->first_name ?? '',
            'middle_name'   => $m->middle_name ?? '',
            'last_name'     => $m->last_name ?? '',
            'date_of_birth' => $m->date_of_birth?->format('Y-m-d') ?? '',
            'age'           => $m->age ?? '',
            'gender'        => $m->gender ?? '',
            'relationship'  => $m->relationship_to_head ?? '',
            'sectors' => [
                '4ps'            => (bool)($m->is_4ps),
                'senior_citizen' => (bool)($m->is_senior_citizen),
                'pwd'            => (bool)($m->is_pwd),
                'solo_parent'    => (bool)($m->is_solo_parent),
                'voter'          => (bool)($m->is_voter),
                'indigent'       => (bool)($m->is_indigent),
            ],
        ])->values()->all(),
    ]];
}
@endphp

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-gray-600 transition-colors shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-lg font-bold text-gray-900">{{ $isEdit ? 'Edit Household' : 'Register New Household' }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $isEdit ? 'Update household address and member information' : 'Fill in the shared address and add one or more family units' }}</p>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>

<form action="{{ $isEdit ? route($isStaff ? 'staff.residents.update' : 'residents.update', $household->id) : route($isStaff ? 'staff.residents.store' : 'residents.store') }}"
      method="POST" x-data="householdForm({{ $isEdit ? json_encode($initData) : 'null' }})">
@csrf
@if($isEdit) @method('PUT') @endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 xl:items-start">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- 1. Shared Address --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold">1</div>
                <p class="text-sm font-semibold text-gray-800">Shared Address</p>
                <span class="text-xs text-gray-400">(all families at this location)</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">House No.</label>
                    <input type="text" name="house_no" value="{{ old('house_no', $household->house_no ?? '') }}" placeholder="e.g. 123"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm placeholder-gray-400
                                  focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Street / Sitio</label>
                    <input type="text" name="street" value="{{ old('street', $household->street ?? '') }}" placeholder="e.g. Rizal St."
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm placeholder-gray-400
                                  focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Purok <span class="text-red-400 normal-case">*</span></label>
                    <select name="purok" required
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                   focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                        <option value="">Select Purok</option>
                        @foreach(['Purok 1','Purok 2','Purok 3','Purok 4','Purok 5'] as $p)
                        <option value="{{ $p }}" {{ old('purok', $household->purok ?? '') === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- 2. Family Units --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-bold">2</div>
                    <p class="text-sm font-semibold text-gray-800">Family Units</p>
                    <span class="inline-flex items-center justify-center h-5 min-w-5 rounded-full bg-brand-100 text-brand-700 text-[10px] font-bold px-1.5"
                          x-text="families.length"></span>
                </div>
                <button type="button" @click="addFamily()"
                        class="inline-flex items-center gap-2 rounded-xl border border-dashed border-green-400 bg-green-50 px-3.5 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100 transition-colors">
                    <i class="fa-solid fa-plus text-[10px]"></i> Add Another Family
                </button>
            </div>

            <div class="divide-y divide-gray-100">
                <template x-for="(family, fi) in families" :key="fi">
                    <div class="overflow-hidden">

                        {{-- Family Header --}}
                        <div class="flex items-center justify-between px-5 py-3"
                             :style="'background-color:' + familyBg(fi)">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-house-user text-sm" :style="'color:' + familyColor(fi)"></i>
                                <span class="text-sm font-bold" :style="'color:' + familyColor(fi)"
                                      x-text="'Family ' + (fi + 1)"></span>
                                <span class="text-xs font-medium opacity-60" :style="'color:' + familyColor(fi)"
                                      x-text="family.head.first_name ? '— ' + family.head.first_name + ' ' + family.head.last_name : ''"></span>
                            </div>
                            <button type="button" @click="removeFamily(fi)" x-show="families.length > 1"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-red-500 bg-white/70 hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-trash text-[10px]"></i> Remove
                            </button>
                        </div>

                        <div class="p-5 space-y-5">

                            {{-- Head of Family --}}
                            <div class="rounded-xl border-2 overflow-hidden" :style="'border-color:' + familyColor(fi) + '40'">
                                <div class="flex items-center justify-between px-4 py-2.5" :style="'background-color:' + familyBg(fi)">
                                    <p class="text-xs font-bold" :style="'color:' + familyColor(fi)">Head of Family</p>
                                    <span class="inline-flex items-center gap-1 rounded-full text-white text-[10px] font-bold px-2 py-0.5"
                                          :style="'background-color:' + familyColor(fi)">
                                        <i class="fa-solid fa-star text-[7px]"></i> HEAD
                                    </span>
                                </div>
                                <div class="p-4 space-y-3">
                                    {{-- Name --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">First Name *</label>
                                            <input type="text" :name="'families['+fi+'][head][first_name]'" x-model="family.head.first_name"
                                                   placeholder="First name" required
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Middle Name</label>
                                            <input type="text" :name="'families['+fi+'][head][middle_name]'" x-model="family.head.middle_name"
                                                   placeholder="Middle name"
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Last Name *</label>
                                            <input type="text" :name="'families['+fi+'][head][last_name]'" x-model="family.head.last_name"
                                                   placeholder="Last name" required
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                    </div>
                                    {{-- Bio --}}
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Date of Birth</label>
                                            <input type="date" :name="'families['+fi+'][head][date_of_birth]'" x-model="family.head.date_of_birth"
                                                   @change="autoAge(family.head)"
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Age</label>
                                            <input type="number" :name="'families['+fi+'][head][age]'" x-model="family.head.age"
                                                   placeholder="Age" min="0"
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Gender *</label>
                                            <select :name="'families['+fi+'][head][gender]'" x-model="family.head.gender" required
                                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900
                                                           focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <option value="">Select</option><option>Male</option><option>Female</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Civil Status</label>
                                            <select :name="'families['+fi+'][head][civil_status]'" x-model="family.head.civil_status"
                                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900
                                                           focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <option value="">Select</option>
                                                <option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option>
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Contact + Employment --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Contact Number</label>
                                            <input type="text" :name="'families['+fi+'][head][contact_number]'" x-model="family.head.contact_number"
                                                   placeholder="09xx-xxx-xxxx"
                                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400
                                                          focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Employment Status</label>
                                            <select :name="'families['+fi+'][head][employment_status]'" x-model="family.head.employment_status"
                                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900
                                                           focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <option value="">Select</option>
                                                <option>Employed</option><option>Self-Employed</option><option>Unemployed</option><option>Student</option><option>Retired</option>
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Head Sectors --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Sector Tags</label>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="s in sectorList" :key="s">
                                                <label class="inline-flex items-center gap-1 rounded-xl border-2 border-gray-200 bg-gray-50 px-2.5 py-1 cursor-pointer transition-all text-xs font-medium text-gray-600"
                                                       :class="family.head.sectors[s] ? 'border-green-600 bg-green-50 text-green-700' : 'hover:border-gray-300'">
                                                    <input type="checkbox" :name="'families['+fi+'][head][is_'+s+']'" value="1"
                                                           class="fixed opacity-0 w-0 h-0"
                                                           x-model="family.head.sectors[s]">
                                                    <span x-text="sectorLabel(s)"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Members of this Family --}}
                            <div class="rounded-xl border border-gray-200 overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-users text-gray-400 text-xs"></i>
                                        <p class="text-xs font-semibold text-gray-600">Family Members</p>
                                        <span class="inline-flex items-center justify-center h-4 min-w-4 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold px-1"
                                              x-text="family.members.length"></span>
                                    </div>
                                    <button type="button" @click="addMember(fi)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-white border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                                        <i class="fa-solid fa-plus text-[9px]"></i> Add Member
                                    </button>
                                </div>

                                <div x-show="family.members.length === 0" style="display:none"
                                     class="flex items-center gap-3 px-4 py-4 text-gray-400">
                                    <i class="fa-solid fa-user-plus text-sm"></i>
                                    <span class="text-xs">No additional members yet. Click "Add Member" to include others.</span>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    <template x-for="(member, mi) in family.members" :key="mi">
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold"
                                                         x-text="mi + 2"></div>
                                                    <p class="text-xs font-semibold text-gray-700"
                                                       x-text="member.first_name || member.last_name ? (member.first_name + ' ' + member.last_name).trim() : 'Member ' + (mi + 2)"></p>
                                                </div>
                                                <button type="button" @click="removeMember(fi, mi)"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 transition-colors">
                                                    <i class="fa-solid fa-trash text-[9px]"></i> Remove
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
                                                <input type="text" :name="'families['+fi+'][members]['+mi+'][first_name]'" x-model="member.first_name"
                                                       placeholder="First name"
                                                       class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <input type="text" :name="'families['+fi+'][members]['+mi+'][middle_name]'" x-model="member.middle_name"
                                                       placeholder="Middle name"
                                                       class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <input type="text" :name="'families['+fi+'][members]['+mi+'][last_name]'" x-model="member.last_name"
                                                       placeholder="Last name"
                                                       class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2">
                                                <input type="date" :name="'families['+fi+'][members]['+mi+'][date_of_birth]'" x-model="member.date_of_birth"
                                                       @change="autoAge(member)"
                                                       class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <input type="number" :name="'families['+fi+'][members]['+mi+'][age]'" x-model="member.age"
                                                       placeholder="Age" min="0"
                                                       class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm placeholder-gray-400 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                <select :name="'families['+fi+'][members]['+mi+'][gender]'" x-model="member.gender"
                                                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                    <option value="">Gender</option><option>Male</option><option>Female</option>
                                                </select>
                                                <select :name="'families['+fi+'][members]['+mi+'][relationship]'" x-model="member.relationship"
                                                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:bg-white focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                                                    <option value="">Role / Relation</option>
                                                    <optgroup label="Spouse / Partner">
                                                        <option>Wife</option><option>Husband</option><option>Partner</option>
                                                    </optgroup>
                                                    <optgroup label="Children">
                                                        <option>Son</option><option>Daughter</option><option>Stepson</option>
                                                        <option>Stepdaughter</option><option>Adopted Son</option><option>Adopted Daughter</option>
                                                    </optgroup>
                                                    <optgroup label="Parents / In-Laws">
                                                        <option>Father</option><option>Mother</option><option>Father-in-Law</option>
                                                        <option>Mother-in-Law</option><option>Stepfather</option><option>Stepmother</option>
                                                    </optgroup>
                                                    <optgroup label="Siblings">
                                                        <option>Brother</option><option>Sister</option>
                                                        <option>Brother-in-Law</option><option>Sister-in-Law</option>
                                                    </optgroup>
                                                    <optgroup label="Grandchildren / Grandparents">
                                                        <option>Grandson</option><option>Granddaughter</option>
                                                        <option>Grandfather</option><option>Grandmother</option>
                                                    </optgroup>
                                                    <optgroup label="Extended Family">
                                                        <option>Uncle</option><option>Aunt</option>
                                                        <option>Nephew</option><option>Niece</option><option>Cousin</option>
                                                    </optgroup>
                                                    <optgroup label="Other">
                                                        <option>Boarder / Lodger</option><option>House Helper</option>
                                                        <option>Other Relative</option><option>Non-Relative</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="s in sectorList" :key="s">
                                                    <label x-show="showMemberSector(member, s)"
                                                           class="inline-flex items-center gap-1 rounded-xl border-2 border-gray-200 bg-gray-50 px-2.5 py-1 cursor-pointer transition-all text-xs font-medium text-gray-600"
                                                           :class="member.sectors[s] ? 'border-green-600 bg-green-50 text-green-700' : 'hover:border-gray-300'">
                                                        <input type="checkbox" :name="'families['+fi+'][members]['+mi+'][is_'+s+']'" value="1"
                                                               class="fixed opacity-0 w-0 h-0"
                                                               x-model="member.sectors[s]">
                                                        <span x-text="sectorLabel(s)"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>{{-- end p-5 --}}
                    </div>{{-- end family card --}}
                </template>
            </div>

            <div class="px-5 py-3 border-t border-dashed border-gray-200">
                <button type="button" @click="addFamily()"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors">
                    <i class="fa-solid fa-plus text-[10px]"></i> Add Another Family Unit
                </button>
            </div>
        </div>

    </div>

    {{-- Right Sidebar --}}
    <div class="space-y-5">

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-floppy-disk"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Save</p>
            </div>
            <div class="p-5 space-y-3">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> {{ $isEdit ? 'Update Household' : 'Register Household' }}
                </button>
                <a href="{{ route($isStaff ? 'staff.residents.index' : 'residents.index') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Summary</p>
            </div>
            <div class="p-5 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">Family Units</span>
                    <span class="text-xs font-bold text-brand-700" x-text="families.length"></span>
                </div>
                <template x-for="(family, fi) in families" :key="fi">
                    <div class="flex items-center justify-between pl-3 border-l-2" :style="'border-color:' + familyColor(fi)">
                        <span class="text-xs text-gray-500" x-text="'Family ' + (fi+1)"></span>
                        <span class="text-xs font-semibold text-gray-700" x-text="(1 + family.members.length) + ' member' + (family.members.length !== 0 ? 's' : '')"></span>
                    </div>
                </template>
                <div class="flex items-center justify-between border-t border-gray-100 pt-2.5">
                    <span class="text-xs font-semibold text-gray-700">Total Residents</span>
                    <span class="text-sm font-bold text-brand-600" x-text="totalMembers()"></span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-info"></i> Guidelines
            </p>
            <ul class="text-xs text-blue-600 space-y-1.5 list-disc list-inside">
                <li>Each family unit gets its own Head.</li>
                <li>Multiple families at the same address are registered separately.</li>
                <li>Sector tags affect eligibility for benefits.</li>
                <li>Date of birth auto-fills age.</li>
            </ul>
        </div>

    </div>
</div>

</form>

<script>
function householdForm(initFamilies) {
    const emptySectors = () => ({ '4ps':false, 'senior_citizen':false, 'pwd':false, 'solo_parent':false, 'voter':false, 'indigent':false });
    const emptyHead   = () => ({ first_name:'', middle_name:'', last_name:'', date_of_birth:'', age:'', gender:'', civil_status:'', contact_number:'', employment_status:'', sectors: emptySectors() });
    const emptyMember = () => ({ first_name:'', middle_name:'', last_name:'', date_of_birth:'', age:'', gender:'', relationship:'', sectors: emptySectors() });

    const COLORS = ['#1a4731','#1d4ed8','#7c3aed','#b45309','#be185d'];
    const BGS    = ['#f0faf4','#eff6ff','#f5f3ff','#fffbeb','#fdf2f8'];

    return {
        families: initFamilies ?? [{ head: emptyHead(), members: [] }],
        sectorList: ['4ps','senior_citizen','pwd','solo_parent','voter','indigent'],

        sectorLabel(s) {
            return { '4ps':'4Ps', 'senior_citizen':'Senior Citizen', 'pwd':'PWD', 'solo_parent':'Solo Parent', 'voter':'Voter', 'indigent':'Indigent' }[s] || s;
        },
        showMemberSector(member, s) {
            const childRoles = ['Son','Daughter','Stepson','Stepdaughter','Adopted Son','Adopted Daughter','Grandson','Granddaughter','Nephew','Niece'];
            if (s === 'solo_parent' && childRoles.includes(member.relationship)) return false;
            if (s === 'indigent'    && childRoles.includes(member.relationship)) return false;
            return true;
        },
        familyColor(fi) { return COLORS[fi % COLORS.length]; },
        familyBg(fi)    { return BGS[fi % BGS.length]; },

        addFamily()         { this.families.push({ head: emptyHead(), members: [] }); },
        removeFamily(fi)    { if (this.families.length > 1) this.families.splice(fi, 1); },
        addMember(fi)       { this.families[fi].members.push(emptyMember()); },
        removeMember(fi,mi) { this.families[fi].members.splice(mi, 1); },

        totalMembers() {
            return this.families.reduce((sum, f) => sum + 1 + f.members.length, 0);
        },
        autoAge(person) {
            if (!person.date_of_birth) return;
            const age = Math.floor((new Date() - new Date(person.date_of_birth)) / (365.25 * 24 * 60 * 60 * 1000));
            if (age > 0 && age < 150) person.age = age;
        }
    };
}
</script>

@endsection
