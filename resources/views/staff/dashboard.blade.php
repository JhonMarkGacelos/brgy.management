@extends('layouts.staff')
@section('title', 'Staff Dashboard')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Good day, {{ explode(' ', Auth::user()->name ?? 'Staff')[0] }}!</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ now()->format('l, F j, Y') }} &mdash; Barangay Caranas Operations</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('staff.residents.create') }}"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shrink-0 transition-colors"
           style="background-color:#1a4731;"
           onmouseover="this.style.backgroundColor='#2d6a4f'"
           onmouseout="this.style.backgroundColor='#1a4731'">
            <i class="fa-solid fa-plus text-xs"></i> New Resident
        </a>
    </div>
</div>

{{-- Stat Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label'=>'Docs Processed Today', 'value'=>$docsProcessedToday,  'sub'=>'Issued this session',      'icon'=>'fa-file-circle-check', 'iconBg'=>'bg-blue-100',  'iconColor'=>'text-blue-600'],
            ['label'=>'Pending Approvals',    'value'=>$pendingApprovals,  'sub'=>'Awaiting official decision','icon'=>'fa-clock',             'iconBg'=>'bg-amber-100', 'iconColor'=>'text-amber-600'],
            ['label'=>'Active Complaint Cases', 'value'=>$activeBlotterCases,  'sub'=>'Open & under mediation',   'icon'=>'fa-shield-halved',     'iconBg'=>'bg-red-100',   'iconColor'=>'text-red-600'],
            ['label'=>'Residents This Month', 'value'=>$residentsThisMonth, 'sub'=>'New registrations',        'icon'=>'fa-user-plus',         'iconBg'=>'bg-teal-100',  'iconColor'=>'text-teal-600'],
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
        <p class="text-3xl font-bold text-gray-900 tracking-tight">{{ number_format($card['value']) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Pending Approvals + Quick Actions --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- Pending Approvals Queue --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900">Pending Approvals</p>
                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5">{{ count($pendingQueue) }} items</span>
            </div>
            <a href="{{ route('staff.documents.index') }}" class="text-xs font-medium text-green-700 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($pendingQueue as $p)
            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-amber-50/30 transition-colors">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-500 text-sm">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $p->document_type }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $p->resident?->full_name }} &middot; Submitted {{ $p->created_at->format('M d, Y H:i A') }}</p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    @php
                        $badgeClass = match($p->status) {
                            'Issued'           => 'bg-green-100 text-green-700',
                            'Rejected'         => 'bg-red-100 text-red-700',
                            'Pending Official' => 'bg-amber-100 text-amber-700',
                            default            => 'bg-yellow-100 text-yellow-700',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full {{ $badgeClass }} text-[10px] font-semibold px-2 py-0.5">
                        {{ $p->status }}
                    </span>
                    <a href="{{ route('staff.documents.show', $p->id) }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                        <i class="fa-solid fa-eye text-[10px]"></i> View
                    </a>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                No pending approvals
            </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm font-semibold text-gray-900 mb-4">Quick Actions</p>
        <div class="grid grid-cols-2 gap-2.5">

            <a href="{{ route('staff.residents.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-brand-50 hover:bg-brand-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-user-plus text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-brand-700 group-hover:text-white transition-colors leading-tight text-center">New Resident</span>
            </a>

            <a href="{{ route('staff.blotter.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-red-50 hover:bg-red-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-shield-halved text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-red-600 group-hover:text-white transition-colors leading-tight text-center">File Complaint</span>
            </a>

            <a href="{{ route('staff.documents.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-blue-50 hover:bg-blue-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-file-circle-plus text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-blue-600 group-hover:text-white transition-colors leading-tight text-center">Request Document</span>
            </a>

            <a href="{{ route('staff.announcements.create') }}"
               class="group flex flex-col items-center justify-center gap-2 rounded-xl p-4 bg-purple-50 hover:bg-purple-600 transition-all duration-200">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500 group-hover:bg-white/20 transition-colors">
                    <i class="fa-solid fa-bullhorn text-white text-sm"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 group-hover:text-white transition-colors leading-tight text-center">Post Announcement</span>
            </a>

        </div>
    </div>
</div>

{{-- Recent Activity + Today's Documents --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Recent Activity --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900">Recent Activity</p>
            <span class="text-xs font-medium text-green-700 hover:underline cursor-pointer">View all</span>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentDocuments as $doc)
            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/60 transition-colors">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-500 text-sm">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $doc->document_type }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $doc->resident?->full_name ?? 'N/A' }} &middot; {{ $doc->status }}</p>
                </div>
                <span class="text-xs text-gray-400 shrink-0">{{ $doc->created_at->diffForHumans() }}</span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                No recent activity
            </div>
            @endforelse
        </div>
    </div>

    {{-- Today's Document Queue --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900">Today's Documents</p>
            </div>
        </div>
        @php
            $docStatusColors = [
                'Issued'           => 'bg-brand-100 text-brand-700',
                'Pending'          => 'bg-amber-100 text-amber-700',
                'Pending Official' => 'bg-amber-100 text-amber-700',
                'Approved'         => 'bg-green-100 text-green-700',
                'Rejected'         => 'bg-red-100 text-red-700',
            ];
            $todayDocuments = \App\Models\DocumentRequest::whereDate('created_at', today())->with('resident')->latest()->take(5)->get();
        @endphp
        <div class="p-4 space-y-2.5">
            @forelse($todayDocuments as $doc)
            <div class="flex items-center justify-between gap-2 rounded-xl bg-gray-50 px-3 py-2.5">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-800 truncate">{{ $doc->document_type }}</p>
                    <p class="text-[11px] text-gray-400 truncate">{{ $doc->resident?->full_name ?? 'N/A' }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center rounded-full {{ $docStatusColors[$doc->status] ?? 'bg-gray-100 text-gray-600' }} text-[10px] font-semibold px-2 py-0.5">
                    {{ $doc->status }}
                </span>
            </div>
            @empty
            <div class="text-center py-6 text-gray-400 text-xs">No documents today</div>
            @endforelse

            <div class="pt-2 border-t border-gray-100">
                <a href="{{ route('staff.documents.index') }}"
                   class="flex w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 transition-colors">
                    <i class="fa-solid fa-arrow-right text-[10px]"></i> View All Documents
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
