@extends('layouts.resident')
@section('title', $announcement->title)

@section('content')

<div class="mb-5">
    <a href="{{ route('resident.announcements.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-700 transition-colors">
        <i class="fa-solid fa-chevron-left text-xs"></i> {{ __('portal.back_to_announcements') }}
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-6 border-b border-gray-100" style="background: linear-gradient(135deg, #1a4731 0%, #2d6a4f 100%);">
            <div class="flex items-center gap-2 mb-3">
                @php
                    $categoryColors = [
                        'Health'      => 'bg-red-400/20 text-red-200 border-red-400/30',
                        'Safety'      => 'bg-orange-400/20 text-orange-200 border-orange-400/30',
                        'Education'   => 'bg-blue-400/20 text-blue-200 border-blue-400/30',
                        'Environment' => 'bg-green-400/20 text-green-200 border-green-400/30',
                        'Events'      => 'bg-purple-400/20 text-purple-200 border-purple-400/30',
                        'General'     => 'bg-white/10 text-white/70 border-white/20',
                    ];
                    $colorClass = $categoryColors[$announcement->category] ?? 'bg-white/10 text-white/70 border-white/20';
                @endphp
                <span class="inline-flex items-center rounded-full border {{ $colorClass }} text-[11px] font-semibold px-2.5 py-1">
                    {{ $announcement->category ?? 'General' }}
                </span>
                <span class="text-white/50 text-xs">
                    <i class="fa-solid fa-users text-[10px] mr-1"></i>{{ $announcement->audience ?? __('portal.all_residents') }}
                </span>
            </div>
            <h1 class="text-xl font-bold text-white leading-snug">{{ $announcement->title }}</h1>
            <p class="text-white/60 text-xs mt-2">
                <i class="fa-regular fa-calendar mr-1"></i>
                {{ __('portal.announcements.show.posted', ['date' => $announcement->published_at?->format('F j, Y')]) }}
                @if($announcement->expires_at)
                    &middot; {{ __('portal.announcements.show.expires', ['date' => $announcement->expires_at->format('F j, Y')]) }}
                @endif
            </p>
        </div>

        {{-- Body --}}
        <div class="px-6 py-6">
            <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                {!! nl2br(e($announcement->content)) !!}
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
            <span class="text-xs text-gray-400">
                {{ __('portal.announcements.show.footer') }}
            </span>
            <a href="{{ route('resident.announcements.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white transition-colors"
               style="background-color:#1a4731;"
               onmouseover="this.style.backgroundColor='#2d6a4f'"
               onmouseout="this.style.backgroundColor='#1a4731'">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> {{ __('portal.announcements.show.back_to_board') }}
            </a>
        </div>
    </div>
</div>

@endsection
