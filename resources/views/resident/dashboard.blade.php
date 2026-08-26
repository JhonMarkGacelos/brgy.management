@extends('layouts.resident')
@section('title', 'Resident Portal')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">{{ __('portal.dashboard.welcome', ['name' => explode(' ', Auth::user()->name ?? 'Resident')[0]]) }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }} &mdash; {{ __('portal.dashboard.subtitle') }}</p>
    </div>
    <a href="{{ route('resident.documents.create') }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shrink-0 transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-file-circle-plus text-xs"></i> {{ __('portal.dashboard.request_document') }}
    </a>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @php
        $cards = [
            ['label'=>__('portal.dashboard.stat_pending'),  'value'=>$stats['pending'],  'sub'=>__('portal.dashboard.stat_pending_sub'),   'icon'=>'fa-clock',             'iconBg'=>'bg-amber-100', 'iconColor'=>'text-amber-600'],
            ['label'=>__('portal.dashboard.stat_approved'),          'value'=>$stats['approved'], 'sub'=>__('portal.dashboard.stat_approved_sub'),     'icon'=>'fa-circle-check',      'iconBg'=>'bg-green-100', 'iconColor'=>'text-green-600'],
            ['label'=>__('portal.dashboard.stat_issued'),            'value'=>$stats['issued'],   'sub'=>__('portal.dashboard.stat_issued_sub'),    'icon'=>'fa-file-circle-check', 'iconBg'=>'bg-blue-100',  'iconColor'=>'text-blue-600'],
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
        <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ $card['value'] }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- My Recent Requests --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900">{{ __('portal.dashboard.recent_requests') }}</p>
            </div>
            <a href="{{ route('resident.documents.index') }}" class="text-xs font-medium text-green-700 hover:underline">{{ __('common.view_all') }}</a>
        </div>
        @php
            $statusColors = [
                'Issued'           => 'bg-blue-100 text-blue-700',
                'Approved'         => 'bg-green-100 text-green-700',
                'Pending'          => 'bg-amber-100 text-amber-700',
                'Pending Official' => 'bg-amber-100 text-amber-700',
                'Rejected'         => 'bg-red-100 text-red-700',
            ];
            $statusKeys = [
                'Issued'           => 'issued',
                'Approved'         => 'approved',
                'Pending'          => 'pending',
                'Pending Official' => 'pending_official',
                'Rejected'         => 'rejected',
            ];
        @endphp
        <div class="divide-y divide-gray-50">
            @forelse($myRequests as $req)
            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/60 transition-colors">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-500 text-sm">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $req->document_type }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $req->tracking_number }} &middot; {{ $req->created_at->format('M d, Y') }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center rounded-full {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-600' }} text-[10px] font-semibold px-2 py-0.5">
                    {{ isset($statusKeys[$req->status]) ? __('common.status.'.$statusKeys[$req->status]) : $req->status }}
                </span>
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">
                <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                {{ __('portal.dashboard.no_requests_yet') }} <a href="{{ route('resident.documents.create') }}" class="text-green-700 font-medium hover:underline">{{ __('portal.dashboard.make_first_request') }}</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions + Track --}}
    <div class="space-y-5">

        {{-- Quick Actions --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-900 mb-4">{{ __('portal.dashboard.quick_actions') }}</p>
            <div class="grid grid-cols-2 gap-2.5">
                <a href="{{ route('resident.documents.create') }}"
                   class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-green-50 hover:bg-green-700 transition-all duration-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-700 group-hover:bg-white/20 transition-colors">
                        <i class="fa-solid fa-file-circle-plus text-white text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-800 group-hover:text-white transition-colors leading-tight text-center">{{ __('portal.dashboard.request_document') }}</span>
                </a>

                <a href="{{ route('resident.announcements.index') }}"
                   class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-purple-50 hover:bg-purple-600 transition-all duration-200">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500 group-hover:bg-white/20 transition-colors">
                        <i class="fa-solid fa-bullhorn text-white text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 group-hover:text-white transition-colors leading-tight text-center">{{ __('nav.announcements') }}</span>
                </a>

                <a href="{{ route('resident.documents.index') }}"
                   class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-blue-50 hover:bg-blue-600 transition-all duration-200 col-span-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500 group-hover:bg-white/20 transition-colors">
                        <i class="fa-solid fa-list-check text-white text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 group-hover:text-white transition-colors leading-tight text-center">{{ __('portal.dashboard.view_all_requests') }}</span>
                </a>
            </div>
        </div>

        {{-- Track Request --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-900 mb-1">{{ __('portal.dashboard.track_a_request') }}</p>
            <p class="text-xs text-gray-400 mb-4">{{ __('portal.dashboard.track_subtitle') }}</p>
            <form action="{{ route('resident.documents.track') }}" method="GET" class="space-y-3">
                <input type="text" name="tracking_number" placeholder="e.g. DOC-2024-0012"
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 font-mono">
                <button type="submit"
                        class="w-full rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-magnifying-glass mr-1.5 text-xs"></i> {{ __('portal.dashboard.track_button') }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Latest Announcements --}}
@if($announcements->count())
<div class="mt-5 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 text-xs">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <p class="text-sm font-semibold text-gray-900">{{ __('portal.dashboard.latest_announcements') }}</p>
        </div>
        <a href="{{ route('resident.announcements.index') }}" class="text-xs font-medium text-green-700 hover:underline">{{ __('common.view_all') }}</a>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($announcements as $ann)
        <a href="{{ route('resident.announcements.show', $ann->id) }}"
           class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50/60 transition-colors">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-500 text-sm mt-0.5">
                <i class="fa-solid fa-megaphone"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800">{{ $ann->title }}</p>
                <p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ Str::limit(strip_tags($ann->content), 120) }}</p>
            </div>
            <p class="text-xs text-gray-400 shrink-0 mt-0.5">{{ $ann->published_at?->format('M d') }}</p>
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection
