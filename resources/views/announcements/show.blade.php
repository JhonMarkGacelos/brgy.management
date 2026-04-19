@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'View Announcement')

@section('content')

@php $isStaff = Auth::user()->role === 'staff'; @endphp

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.announcements.index' : 'announcements.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">Announcement Details</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $announcement->title }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Announcement Content --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Announcement Content</p>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $announcement->category === 'Emergency' ? 'bg-red-100 text-red-700' : ($announcement->category === 'Health' ? 'bg-green-100 text-green-700' : ($announcement->category === 'Events' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')) }}">{{ $announcement->category }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $announcement->status === 'Published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $announcement->status }}</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">{{ $announcement->title }}</h3>
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! nl2br(e($announcement->content)) !!}
                </div>
            </div>
        </div>

        {{-- Metadata --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-50 text-gray-500 text-xs">
                    <i class="fa-solid fa-info"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Publication Details</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Posted By</label>
                    <p class="text-sm text-gray-900">{{ $announcement->postedBy->name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date Posted</label>
                    <p class="text-sm text-gray-900">{{ $announcement->created_at->format('M d, Y H:i') }}</p>
                </div>
                @if($announcement->published_at)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Published At</label>
                    <p class="text-sm text-gray-900">{{ $announcement->published_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
                @if($announcement->expires_at)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Expires At</label>
                    <p class="text-sm text-gray-900">{{ $announcement->expires_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Audience</label>
                    <p class="text-sm text-gray-900">{{ $announcement->audience ?? 'All Residents' }}</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Actions --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-50 text-gray-500 text-xs">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Actions</p>
            </div>
            <div class="p-5 space-y-3">
                <a href="{{ route($isStaff ? 'staff.announcements.edit' : 'announcements.edit', $announcement->id) }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                    <i class="fa-solid fa-pen text-xs"></i> Edit Announcement
                </a>
                <form action="{{ route($isStaff ? 'staff.announcements.destroy' : 'announcements.destroy', $announcement->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i> Delete Announcement
                    </button>
                </form>
            </div>
        </div>

        {{-- Status Update --}}
        @if(Auth::user()->role === 'admin')
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-toggle-on"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Status Control</p>
            </div>
            <div class="p-5">
                <form action="{{ route($isStaff ? 'staff.announcements.update' : 'announcements.update', $announcement->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <select name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all mb-3">
                        <option value="Draft" {{ $announcement->status === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Published" {{ $announcement->status === 'Published' ? 'selected' : '' }}>Published</option>
                        <option value="Archived" {{ $announcement->status === 'Archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;">
                        <i class="fa-solid fa-save text-xs"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>

@endsection