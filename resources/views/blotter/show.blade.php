@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'View Blotter Case')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

{{-- Page Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route($isStaff ? 'staff.blotter.index' : 'blotter.index') }}"
       class="flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all shadow-sm">
        <i class="fa-solid fa-arrow-left text-xs"></i>
    </a>
    <div>
        <h2 class="text-base font-semibold text-gray-900">Blotter Case Details</h2>
        <p class="text-xs text-gray-400 mt-0.5">Case {{ $record->case_number }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left Column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Case Information --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-50 text-red-500 text-xs">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Case Information</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Case Number</label>
                    <p class="text-sm font-mono text-gray-900">{{ $record->case_number }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Date Filed</label>
                    <p class="text-sm text-gray-900">{{ $record->created_at->format('M d, Y') }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Filed By</label>
                    <p class="text-sm text-gray-900">{{ $record->filedBy->name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Incident Date</label>
                    <p class="text-sm text-gray-900">{{ $record->incident_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Incident Time</label>
                    <p class="text-sm text-gray-900">{{ $record->incident_time ?: 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Incident Type</label>
                    <p class="text-sm text-gray-900">{{ $record->incident_type }}</p>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Location</label>
                    <p class="text-sm text-gray-900">{{ $record->location }}</p>
                </div>
            </div>
        </div>

        {{-- Parties Involved --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Complainant --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Complainant</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full Name</label>
                        <p class="text-sm text-gray-900">{{ $record->complainant_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Address</label>
                        <p class="text-sm text-gray-900">{{ $record->complainant_address ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                        <p class="text-sm text-gray-900">{{ $record->complainant_contact ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Respondent --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-500 text-xs">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Respondent</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full Name</label>
                        <p class="text-sm text-gray-900">{{ $record->respondent_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Address</label>
                        <p class="text-sm text-gray-900">{{ $record->respondent_address ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Contact Number</label>
                        <p class="text-sm text-gray-900">{{ $record->respondent_contact ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Incident Details --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-500 text-xs">
                    <i class="fa-solid fa-paragraph"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Incident Details</p>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Narrative</label>
                    <p class="text-sm text-gray-900 whitespace-pre-line">{{ $record->narrative }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Witnesses</label>
                    <p class="text-sm text-gray-900">{{ $record->witnesses ?: 'None' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Action Taken</label>
                    <p class="text-sm text-gray-900 whitespace-pre-line">{{ $record->action_taken ?: 'None' }}</p>
                </div>
                @if($record->remarks)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Remarks</label>
                    <p class="text-sm text-gray-900 whitespace-pre-line">{{ $record->remarks }}</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Right Column --}}
    <div class="space-y-5">

        {{-- Status Card --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500 text-xs">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Case Status</p>
            </div>
            <div class="p-5 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Current Status</label>
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $record->status === 'Open' ? 'bg-red-100 text-red-700' : ($record->status === 'Under Mediation' ? 'bg-yellow-100 text-yellow-700' : ($record->status === 'Settled' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700')) }}">
                        {{ $record->status }}
                    </span>
                </div>
                @if($record->resolved_at)
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Resolved At</label>
                    <p class="text-sm text-gray-900">{{ $record->resolved_at->format('M d, Y H:i') }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Actions --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-50 text-gray-500 text-xs">
                    <i class="fa-solid fa-gear"></i>
                </div>
                <p class="text-sm font-semibold text-gray-800">Actions</p>
            </div>
            <div class="p-5 space-y-3">
                <a href="{{ route($isStaff ? 'staff.blotter.edit' : 'blotter.edit', $record->id) }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                    <i class="fa-solid fa-pen text-xs"></i> Edit Case
                </a>
                <form action="{{ route($isStaff ? 'staff.blotter.destroy' : 'blotter.destroy', $record->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this blotter record?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i> Delete Case
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection