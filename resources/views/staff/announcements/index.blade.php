@extends('layouts.staff')
@section('title', 'Announcements & Notices')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Announcements &amp; Notices</h2>
        <p class="text-sm text-gray-500">Post and manage community announcements for Barangay Caranas</p>
    </div>
    <a href="{{ route('staff.announcements.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-bullhorn"></i> Post Announcement
    </a>
</div>

{{-- Category Filter --}}
<div class="flex flex-wrap gap-2 mb-6">
    @php
        $categories = ['All','Health','Peace & Order','Events','Emergency'];
        $activeClass = 'text-white';
        $inactiveClass = 'bg-gray-100 text-gray-600 hover:bg-gray-200';
    @endphp
    @foreach($categories as $i => $cat)
    <button class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $i === 0 ? $activeClass : $inactiveClass }}"
            {{ $i === 0 ? 'style=background-color:#1a4731;' : '' }}>
        {{ $cat }}
    </button>
    @endforeach
</div>

@php
    $announcements = isset($announcements) && $announcements->count() > 0 ? $announcements : [];
@endphp

{{-- Pinned Announcements --}}
@php $pinned = $announcements->filter(fn($a) => $a->status === 'Published')->take(3); @endphp
@if($pinned->count())
<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-2">
        <i class="fa-solid fa-thumbtack text-amber-400"></i> Pinned
    </p>
    <div class="space-y-3">
        @forelse($pinned as $a)
        <div class="rounded-2xl bg-white border border-l-4 border-l-amber-400 border-gray-100 shadow-sm p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->category === 'Emergency' ? 'bg-red-100 text-red-700' : ($a->category === 'Health' ? 'bg-green-100 text-green-700' : ($a->category === 'Events' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">{{ $a->category }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-600 text-[10px] font-semibold px-2 py-0.5">
                            <i class="fa-solid fa-thumbtack text-[9px]"></i> Featured
                        </span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 mb-1">{{ $a->title }}</h3>
                    <p class="text-xs text-gray-500 line-clamp-2">{{ Str::limit($a->content, 150) }}</p>
                    <p class="text-[11px] text-gray-400 mt-2">Posted {{ $a->created_at->format('M d, Y') }} &middot; by {{ $a->postedBy?->name }}</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    @if(Auth::user()->role === 'admin' || Auth::user()->role === 'staff')
                    <a href="{{ route('staff.announcements.edit', $a->id) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                        <i class="fa-solid fa-pen text-[11px]"></i> Edit
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        @endforelse
    </div>
</div>
@endif

{{-- All Announcements --}}
<div>
    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">All Announcements</p>
    <div class="space-y-3">
        @forelse($announcements as $a)
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->category === 'Emergency' ? 'bg-red-100 text-red-700' : ($a->category === 'Health' ? 'bg-green-100 text-green-700' : ($a->category === 'Events' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">{{ $a->category }}</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $a->status === 'Published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $a->status }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 mb-1">{{ $a->title }}</h3>
                    <p class="text-xs text-gray-500 line-clamp-2">{{ Str::limit($a->content, 150) }}</p>
                    <p class="text-[11px] text-gray-400 mt-2">Posted {{ $a->created_at->format('M d, Y') }} &middot; by {{ $a->postedBy?->name }}</p>
                </div>
                <div class="flex gap-1.5 shrink-0">
                    <a href="{{ route('staff.announcements.show', $a->id) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                        <i class="fa-solid fa-eye text-[11px]"></i>
                    </a>
                    <a href="{{ route('staff.announcements.edit', $a->id) }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                        <i class="fa-solid fa-pen text-[11px]"></i>
                    </a>
                    <form action="{{ route('staff.announcements.destroy', $a->id) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 bg-red-50 hover:bg-red-100 transition-colors">
                            <i class="fa-solid fa-trash text-[11px]"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-2xl bg-white border border-gray-100 p-8 text-center">
            <i class="fa-solid fa-bullhorn text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No announcements yet</h3>
            <p class="text-gray-500 mb-4">Create your first announcement to keep residents informed.</p>
            <a href="{{ route('staff.announcements.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background-color:#1a4731;">Post Announcement</a>
        </div>
        @endforelse
    </div>
</div>

@endsection
