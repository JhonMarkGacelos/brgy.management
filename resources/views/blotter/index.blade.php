@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Blotter Records')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Blotter Records</h2>
        <p class="text-sm text-gray-500">Incident reports and case tracking</p>
    </div>
    <a href="{{ route($isStaff ? 'staff.blotter.create' : 'blotter.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
        <i class="fa-solid fa-shield-halved"></i> File New Blotter
    </a>
</div>

{{-- Summary Cards --}}
@php
    $blotterStats = [
        ['label'=>'Filed This Month', 'value'=>$stats['this_month'], 'sub'=>'This month',        'icon'=>'fa-shield-halved',       'bg'=>'bg-red-50',    'color'=>'text-red-600',    'ring'=>'ring-red-100'],
        ['label'=>'Open Cases',       'value'=>$stats['open'],  'sub'=>'Needs action',      'icon'=>'fa-circle-dot',          'bg'=>'bg-orange-50', 'color'=>'text-orange-600', 'ring'=>'ring-orange-100'],
        ['label'=>'Under Mediation',  'value'=>$stats['mediation'],  'sub'=>'In progress',       'icon'=>'fa-handshake',           'bg'=>'bg-yellow-50', 'color'=>'text-yellow-600', 'ring'=>'ring-yellow-100'],
        ['label'=>'Settled',          'value'=>$stats['settled'],  'sub'=>'This month',        'icon'=>'fa-circle-check',        'bg'=>'bg-green-50',  'color'=>'text-green-600',  'ring'=>'ring-green-100'],
        ['label'=>'Referred to Court','value'=>$stats['referred'],  'sub'=>'Escalated cases',   'icon'=>'fa-arrow-right-from-bracket','bg'=>'bg-blue-50','color'=>'text-blue-600',  'ring'=>'ring-blue-100'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
    @foreach($blotterStats as $s)
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <div class="flex items-start justify-between mb-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $s['bg'] }} {{ $s['color'] }} ring-1 {{ $s['ring'] }} text-sm">
                <i class="fa-solid {{ $s['icon'] }}"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 mb-0.5">{{ $s['value'] }}</p>
        <p class="text-xs font-semibold text-gray-700 leading-tight">{{ $s['label'] }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ $s['sub'] }}</p>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route($isStaff ? 'staff.blotter.index' : 'blotter.index') }}">
<div class="bg-white rounded-xl shadow-sm p-4 mb-5">
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <i class="fa-solid fa-search text-sm"></i>
            </span>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by case no., complainant, respondent..."
                   class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="">All Statuses</option>
            @foreach(['Pending','Open','Under Mediation','Settled','Referred','Resolved'] as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            <i class="fa-solid fa-magnifying-glass text-xs"></i> Search
        </button>
        @if(request('search') || request('status'))
        <a href="{{ route($isStaff ? 'staff.blotter.index' : 'blotter.index') }}"
           class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-400 hover:bg-gray-50">
            <i class="fa-solid fa-xmark text-xs"></i> Clear
        </a>
        @endif
    </div>
</div>
</form>

{{-- Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs text-green-100 uppercase">
                    <th class="px-4 py-3">Case No.</th>
                    <th class="px-4 py-3">Date Filed</th>
                    <th class="px-4 py-3">Complainant</th>
                    <th class="px-4 py-3">Respondent</th>
                    <th class="px-4 py-3">Incident Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-mono font-medium text-gray-800 text-xs">{{ $record->case_number }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $record->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $record->complainant_name }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $record->respondent_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->incident_type }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $record->status === 'Open' ? 'bg-red-100 text-red-700' : ($record->status === 'Under Mediation' ? 'bg-yellow-100 text-yellow-700' : ($record->status === 'Settled' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700')) }}">
                            {{ $record->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route($isStaff ? 'staff.blotter.show' : 'blotter.show', $record->id) }}" title="View"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                <i class="fa-solid fa-eye text-[11px]"></i> View
                            </a>
                            <a href="{{ route($isStaff ? 'staff.blotter.edit' : 'blotter.edit', $record->id) }}" title="Edit"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                <i class="fa-solid fa-pen text-[11px]"></i> Edit
                            </a>
                            <form action="{{ route($isStaff ? 'staff.blotter.destroy' : 'blotter.destroy', $record->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this blotter record?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                    <i class="fa-solid fa-trash text-[11px]"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-{{ request('search') || request('status') ? 'magnifying-glass' : 'shield-halved' }} text-lg"></i>
                            </div>
                            <div>
                                @if(request('search') || request('status'))
                                <p class="text-sm font-semibold text-gray-900">No blotter records found</p>
                                <p class="text-xs text-gray-400 mt-1">No results match your search or filter. Try different keywords.</p>
                                @else
                                <p class="text-sm font-semibold text-gray-900">No blotter records yet</p>
                                <p class="text-xs text-gray-400 mt-1">Start by filing a new blotter case</p>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
        <span>{{ $records->total() }} blotter records</span>
        {{ $records->links() }}
    </div>
</div>

@endsection
