@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Announcements & Notices')

@section('content')

@php $isStaff = Auth::user()->role === 'staff'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Announcements &amp; Notices</h2>
        <p class="text-sm text-gray-500">Post and manage community announcements</p>
    </div>
    <a href="{{ route($isStaff ? 'staff.announcements.create' : 'announcements.create') }}"
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
        $catColors = [
            'All'          => 'bg-gray-800 text-white',
            'Health'       => 'bg-gray-100 text-gray-600 hover:bg-gray-200',
            'Peace & Order'=> 'bg-gray-100 text-gray-600 hover:bg-gray-200',
            'Events'       => 'bg-gray-100 text-gray-600 hover:bg-gray-200',
            'Emergency'    => 'bg-gray-100 text-gray-600 hover:bg-gray-200',
        ];
    @endphp
    @foreach($categories as $cat)
    <button class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors {{ $catColors[$cat] }}">
        {{ $cat }}
    </button>
    @endforeach
</div>

@forelse($announcements as $announcement)
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $announcement->status === 'Published' ? 'border-green-400' : 'border-gray-300' }}">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $announcement->category === 'Emergency' ? 'bg-red-100 text-red-700' : ($announcement->category === 'Health' ? 'bg-green-100 text-green-700' : ($announcement->category === 'Events' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">{{ $announcement->category }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $announcement->status === 'Published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $announcement->status }}</span>
                </div>
                <h4 class="font-semibold text-gray-800 mb-1">{{ $announcement->title }}</h4>
                <p class="text-sm text-gray-600 leading-relaxed">{{ Str::limit($announcement->content, 200) }}</p>
                <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
                    <span><i class="fa-regular fa-calendar mr-1"></i>{{ $announcement->created_at->format('M d, Y') }}</span>
                    <span><i class="fa-solid fa-user mr-1"></i>{{ $announcement->postedBy->name }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <a href="{{ route($isStaff ? 'staff.announcements.show' : 'announcements.show', $announcement->id) }}" title="View"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                    <i class="fa-solid fa-eye text-[11px]"></i> View
                </a>
                <a href="{{ route($isStaff ? 'staff.announcements.edit' : 'announcements.edit', $announcement->id) }}" title="Edit"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                    <i class="fa-solid fa-pen text-[11px]"></i> Edit
                </a>
                <form action="{{ route($isStaff ? 'staff.announcements.destroy' : 'announcements.destroy', $announcement->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Delete"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-trash text-[11px]"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
        <i class="fa-solid fa-bullhorn text-4xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">No announcements yet</h3>
        <p class="text-gray-500 mb-4">Create your first announcement to keep residents informed.</p>
        <a href="{{ route($isStaff ? 'staff.announcements.create' : 'announcements.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
           style="background-color:#1a4731;">Post Announcement</a>
    </div>
@endforelse
</div>

@if(isset($announcements) && $announcements->hasPages())
<div class="mt-6 flex justify-center">
    {{ $announcements->links() }}
</div>
@endif

@endsection
