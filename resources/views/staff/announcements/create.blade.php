@extends('layouts.staff')
@section('title', 'Post Announcement')

@section('content')

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('staff.announcements.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">Post Announcement</h2>
        <p class="text-xs text-gray-400 mt-0.5">Create a new community notice or announcement</p>
    </div>
</div>

<form action="{{ route('staff.announcements.store') }}" method="POST">
@csrf

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Announcement Content --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 text-xs">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Announcement Content</p>
            </div>
            <div class="p-5 space-y-4">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Title <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           placeholder="Announcement title..."
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all placeholder-gray-400" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Category <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                        @php
                            $categories = [
                                ['value' => 'Health',        'icon' => 'fa-heart-pulse',          'color' => 'bg-red-50 text-red-500 border-red-200'],
                                ['value' => 'Peace & Order', 'icon' => 'fa-shield-halved',        'color' => 'bg-blue-50 text-blue-500 border-blue-200'],
                                ['value' => 'Events',        'icon' => 'fa-calendar-star',        'color' => 'bg-purple-50 text-purple-500 border-purple-200'],
                                ['value' => 'Emergency',     'icon' => 'fa-triangle-exclamation', 'color' => 'bg-orange-50 text-orange-500 border-orange-200'],
                                ['value' => 'General',       'icon' => 'fa-circle-info',          'color' => 'bg-gray-100 text-gray-500 border-gray-200'],
                            ];
                        @endphp
                        @foreach($categories as $cat)
                        <label class="group flex flex-col items-center gap-1.5 rounded-xl border-2 border-gray-200 bg-white p-3 cursor-pointer transition-all
                                      has-[:checked]:border-green-600 has-[:checked]:bg-green-50 hover:border-gray-300">
                            <input type="radio" name="category" value="{{ $cat['value'] }}"
                                   class="sr-only" {{ old('category') === $cat['value'] ? 'checked' : '' }}>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $cat['color'] }} border text-xs transition-all">
                                <i class="fa-solid {{ $cat['icon'] }}"></i>
                            </div>
                            <span class="text-[10px] font-semibold text-gray-600 text-center leading-tight group-has-[:checked]:text-green-700">
                                {{ $cat['value'] }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Content / Body <span class="text-red-500 normal-case font-normal">*</span>
                    </label>
                    <textarea name="content" rows="8" placeholder="Write the full announcement here..."
                              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                     focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all resize-none placeholder-gray-400" required>{{ old('content') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Schedule --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Schedule</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Publish Date</label>
                    <input type="date" name="published_at" value="{{ old('published_at', date('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                        Expiry Date <span class="text-gray-400 normal-case font-normal">(optional)</span>
                    </label>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                                  focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    <p class="text-[11px] text-gray-400 mt-1">Leave blank to keep it indefinitely</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Post Action --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 text-xs">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Publish</p>
            </div>
            <div class="p-5 space-y-3">
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-bullhorn text-xs"></i> Post Announcement
                </button>
                <a href="{{ route('staff.announcements.index') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </div>

        {{-- Pin Toggle --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-yellow-50 text-yellow-500 text-xs">
                    <i class="fa-solid fa-thumbtack"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Pin Options</p>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Pin this announcement</p>
                        <p class="text-xs text-gray-400 mt-0.5">Appears at the top of the list</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_pinned" value="1" class="sr-only peer" {{ old('is_pinned') ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-yellow-400/50 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:border-gray-300 after:border after:rounded-full
                                    after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-400"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Visibility --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-50 text-green-600 text-xs">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Visibility</p>
            </div>
            <div class="p-5">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Target Audience</label>
                <select name="audience"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900
                               focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:bg-white focus:outline-none transition-all">
                    <option value="All">All Residents</option>
                    <option value="Senior Citizens">Senior Citizens</option>
                    <option value="4Ps Members">4Ps Members</option>
                    <option value="PWD">PWD</option>
                    <option value="Solo Parents">Solo Parents</option>
                </select>
            </div>
        </div>

        {{-- Posted By --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-500 text-xs">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Posted By</p>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 border border-gray-200 p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white text-xs font-bold" style="background-color:#1a4731;">
                        {{ strtoupper(substr(Auth::user()->name ?? 'S', 0, 2)) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name ?? 'Staff' }}</p>
                        <p class="text-xs text-amber-600 font-medium">Clerk / Staff</p>
                    </div>
                </div>
                <input type="hidden" name="posted_by" value="{{ Auth::user()->name ?? 'Staff' }}">
            </div>
        </div>

        {{-- Tips --}}
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
            <p class="text-xs font-semibold text-amber-700 mb-2 flex items-center gap-1.5">
                <i class="fa-solid fa-lightbulb"></i> Tips
            </p>
            <ul class="text-xs text-amber-600 space-y-1.5 list-disc list-inside">
                <li>Use clear, simple language.</li>
                <li>Include dates, times, and venues if relevant.</li>
                <li>Emergency alerts should always be pinned.</li>
                <li>You can only edit or delete announcements you posted.</li>
            </ul>
        </div>

    </div>
</div>

</form>
@endsection
