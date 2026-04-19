@extends('layouts.resident')
@section('title', 'Announcements')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Community Announcements</h2>
        <p class="text-sm text-gray-500">Stay informed with the latest news from Barangay Caranas</p>
    </div>
</div>

@if($announcements->isEmpty())
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm py-16 text-center">
        <i class="fa-regular fa-bell-slash text-4xl text-gray-300 mb-3 block"></i>
        <p class="text-gray-500 font-medium">No announcements at this time.</p>
        <p class="text-sm text-gray-400 mt-1">Check back later for updates from your barangay.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($announcements as $ann)
        @php
            $categoryColors = [
                'Health'      => 'bg-red-100 text-red-700',
                'Safety'      => 'bg-orange-100 text-orange-700',
                'Education'   => 'bg-blue-100 text-blue-700',
                'Environment' => 'bg-green-100 text-green-700',
                'Events'      => 'bg-purple-100 text-purple-700',
                'General'     => 'bg-gray-100 text-gray-600',
            ];
            $colorClass = $categoryColors[$ann->category] ?? 'bg-gray-100 text-gray-600';
        @endphp
        <a href="{{ route('resident.announcements.show', $ann->id) }}"
           class="group rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center rounded-full {{ $colorClass }} text-[11px] font-semibold px-2.5 py-1">
                        {{ $ann->category ?? 'General' }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $ann->published_at?->format('M d, Y') }}</span>
                </div>
                <h3 class="text-sm font-bold text-gray-800 group-hover:text-green-700 transition-colors leading-snug mb-2">
                    {{ $ann->title }}
                </h3>
                <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed">
                    {{ Str::limit(strip_tags($ann->content), 160) }}
                </p>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    <i class="fa-solid fa-users text-[10px] mr-1"></i>{{ $ann->audience ?? 'All Residents' }}
                </span>
                <span class="text-xs font-semibold text-green-700 group-hover:underline">Read more →</span>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $announcements->links() }}
    </div>
@endif

@endsection
