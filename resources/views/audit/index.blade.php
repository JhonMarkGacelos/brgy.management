@extends('layouts.app')
@section('title', 'Audit Log')

@section('content')

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800">Audit Log</h2>
    <p class="text-sm text-gray-500 mt-0.5">Full history of all actions performed by admin and staff accounts.</p>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('audit.index') }}" class="mb-5 flex flex-wrap gap-3">
    <select name="log_name"
            class="rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600"
            onchange="this.form.submit()">
        <option value="">All Categories</option>
        <option value="document"     {{ request('log_name') === 'document'     ? 'selected' : '' }}>Documents</option>
        <option value="blotter"      {{ request('log_name') === 'blotter'      ? 'selected' : '' }}>Complaint</option>
        <option value="resident"     {{ request('log_name') === 'resident'     ? 'selected' : '' }}>Residents</option>
        <option value="announcement"    {{ request('log_name') === 'announcement'    ? 'selected' : '' }}>Announcements</option>
        <option value="resident_request" {{ request('log_name') === 'resident_request' ? 'selected' : '' }}>Resident Requests</option>
        <option value="user"         {{ request('log_name') === 'user'         ? 'selected' : '' }}>User Accounts</option>
        <option value="household"    {{ request('log_name') === 'household'    ? 'selected' : '' }}>Households</option>
        <option value="setting"      {{ request('log_name') === 'setting'      ? 'selected' : '' }}>Settings</option>
    </select>
    <div class="flex-1 min-w-[200px] relative">
        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search actions..."
               class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-3.5 py-2 text-sm text-gray-700
                      focus:outline-none focus:ring-2 focus:ring-green-600/20 focus:border-green-600">
    </div>
    <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background-color:#1a4731;">
        Filter
    </button>
    @if(request('log_name') || request('search'))
        <a href="{{ route('audit.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
            Clear
        </a>
    @endif
</form>

<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    @if($logs->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <i class="fa-solid fa-clock-rotate-left text-3xl mb-3"></i>
            <p class="text-sm font-medium">No audit log entries found.</p>
        </div>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left">
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date & Time</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Performed By</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Category</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($logs as $log)
                @php
                    $badgeColor = match($log->log_name) {
                        'document'         => 'bg-blue-100 text-blue-700',
                        'blotter'          => 'bg-red-100 text-red-700',
                        'resident'         => 'bg-green-100 text-green-700',
                        'announcement'     => 'bg-purple-100 text-purple-700',
                        'resident_request' => 'bg-amber-100 text-amber-700',
                        'user'             => 'bg-indigo-100 text-indigo-700',
                        'household'        => 'bg-teal-100 text-teal-700',
                        'setting'          => 'bg-cyan-100 text-cyan-700',
                        default            => 'bg-gray-100 text-gray-600',
                    };
                    $badgeLabel = match($log->log_name) {
                        'blotter'           => 'Complaint',
                        'resident_request'  => 'Resident Request',
                        'user'              => 'User Accounts',
                        'household'         => 'Households',
                        'setting'           => 'Settings',
                        default            => ucfirst($log->log_name),
                    };
                    // spatie/laravel-activitylog v5 stores old/new diffs in attribute_changes, not properties
                    $old = $log->attribute_changes['old'] ?? [];
                    $new = $log->attribute_changes['attributes'] ?? [];
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3.5 text-xs text-gray-500 whitespace-nowrap">
                        {{ $log->created_at->format('M d, Y') }}<br>
                        <span class="text-gray-400">{{ $log->created_at->format('h:i A') }}</span>
                    </td>
                    <td class="px-5 py-3.5">
                        @if($log->causer)
                            <p class="font-semibold text-gray-800">{{ $log->causer->name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ $log->causer->role ?? '' }}</p>
                        @else
                            <span class="text-gray-400 text-xs">System</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $badgeColor }}">
                            {{ $badgeLabel }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-gray-700 max-w-xs">
                        {{ $log->description }}
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-500 max-w-sm">
                        @if(!empty($old) && !empty($new))
                            <div class="space-y-1">
                                @foreach($new as $field => $value)
                                    @if(isset($old[$field]) && $old[$field] != $value)
                                        <div>
                                            <span class="font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $field) }}:</span>
                                            <span class="line-through text-red-400 ml-1">{{ $old[$field] ?? '—' }}</span>
                                            <span class="text-green-600 ml-1">→ {{ $value ?? '—' }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @elseif(!empty($new))
                            <div class="space-y-0.5">
                                @foreach(array_slice($new, 0, 3) as $field => $value)
                                    <div><span class="font-medium text-gray-600 capitalize">{{ str_replace('_', ' ', $field) }}:</span> {{ $value ?? '—' }}</div>
                                @endforeach
                                @if(count($new) > 3)
                                    <div class="text-gray-400">+{{ count($new) - 3 }} more</div>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
