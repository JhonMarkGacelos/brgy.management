@php
    $layout = match(auth()->user()->role) {
        'admin'   => 'layouts.app',
        'staff'   => 'layouts.staff',
        default   => 'layouts.resident',
    };
@endphp
@extends($layout)
@section('title', 'Notifications')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Notifications</h2>
        <p class="text-sm text-gray-500 mt-0.5">All updates and alerts sent to your account.</p>
    </div>
    @if($notifications->where('read_at', null)->count() > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                Mark all as read
            </button>
        </form>
    @endif
</div>

<div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    @if($notifications->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <i class="fa-regular fa-bell-slash text-3xl mb-3"></i>
            <p class="text-sm font-medium">No notifications yet.</p>
        </div>
    @else
        <div class="divide-y divide-gray-50">
            @php
                $bgMap = ['green' => 'bg-green-100', 'red' => 'bg-red-100', 'blue' => 'bg-blue-100', 'amber' => 'bg-amber-100', 'purple' => 'bg-purple-100', 'gray' => 'bg-gray-100'];
                $fgMap = ['green' => 'text-green-600', 'red' => 'text-red-600', 'blue' => 'text-blue-600', 'amber' => 'text-amber-600', 'purple' => 'text-purple-600', 'gray' => 'text-gray-600'];
            @endphp
            @foreach($notifications as $n)
                @php
                    $color = $n->data['color'] ?? 'gray';
                @endphp
                <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="block">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-start gap-3 px-5 py-4 text-left hover:bg-gray-50 transition-colors {{ !$n->read_at ? 'bg-green-50/40' : '' }}">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $bgMap[$color] ?? $bgMap['gray'] }}">
                            <i class="fa-solid {{ $n->data['icon'] ?? 'fa-bell' }} text-sm {{ $fgMap[$color] ?? $fgMap['gray'] }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-800">{{ $n->data['title'] ?? 'Notification' }}</p>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $n->data['body'] ?? '' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                        @if(!$n->read_at)
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-green-600"></span>
                        @endif
                    </button>
                </form>
            @endforeach
        </div>
        @if($notifications->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $notifications->links() }}
            </div>
        @endif
    @endif
</div>

@endsection
