@extends('layouts.resident')
@section('title', 'Track Request')

@section('content')

<div class="mb-5">
    <a href="{{ route('resident.documents.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-700 transition-colors">
        <i class="fa-solid fa-chevron-left text-xs"></i> Back to My Requests
    </a>
</div>

<div class="max-w-xl">
    <h2 class="text-xl font-bold text-gray-800 mb-5">Track Document Request</h2>

    {{-- Search Form --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5 mb-5">
        <form action="{{ route('resident.documents.track') }}" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <i class="fa-solid fa-barcode text-sm"></i>
                </span>
                <input type="text" name="tracking_number"
                       value="{{ request('tracking_number') }}"
                       placeholder="e.g. DOC-2024-0012"
                       class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors shrink-0"
                    style="background-color:#1a4731;"
                    onmouseover="this.style.backgroundColor='#2d6a4f'"
                    onmouseout="this.style.backgroundColor='#1a4731'">
                <i class="fa-solid fa-magnifying-glass text-xs"></i> Search
            </button>
        </form>
    </div>

    {{-- Result --}}
    @if(request()->has('tracking_number'))
        @if($document)
        @php
            $statusSteps = ['Pending', 'Pending Official', 'Approved', 'Issued'];
            $currentStep = array_search($document->status, $statusSteps);
            $isRejected  = $document->status === 'Rejected';

            $statusColors = [
                'Issued'           => ['bg' => 'bg-blue-100',   'text' => 'text-blue-700',  'icon' => 'fa-file-circle-check'],
                'Approved'         => ['bg' => 'bg-green-100',  'text' => 'text-green-700', 'icon' => 'fa-circle-check'],
                'Pending'          => ['bg' => 'bg-amber-100',  'text' => 'text-amber-700', 'icon' => 'fa-clock'],
                'Pending Official' => ['bg' => 'bg-amber-100',  'text' => 'text-amber-700', 'icon' => 'fa-clock'],
                'Rejected'         => ['bg' => 'bg-red-100',    'text' => 'text-red-700',   'icon' => 'fa-circle-xmark'],
            ];
            $sc = $statusColors[$document->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'icon' => 'fa-circle'];
        @endphp

        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="px-5 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs text-gray-400 font-mono mb-1">{{ $document->tracking_number }}</p>
                    <h3 class="text-base font-bold text-gray-800">{{ $document->document_type }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $document->resident?->full_name ?? 'N/A' }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full {{ $sc['bg'] }} {{ $sc['text'] }} text-xs font-semibold px-3 py-1.5 shrink-0">
                    <i class="fa-solid {{ $sc['icon'] }} text-[10px]"></i>
                    {{ $document->status }}
                </span>
            </div>

            {{-- Progress Tracker --}}
            @if(! $isRejected)
            <div class="px-5 py-5 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Processing Progress</p>
                <div class="flex items-center gap-0">
                    @php
                        $steps = [
                            ['label' => 'Submitted',    'key' => 0],
                            ['label' => 'For Approval', 'key' => 1],
                            ['label' => 'Approved',     'key' => 2],
                            ['label' => 'Issued',       'key' => 3],
                        ];
                    @endphp
                    @foreach($steps as $idx => $step)
                    @php $done = $currentStep !== false && $currentStep >= $step['key']; @endphp
                    <div class="flex flex-col items-center flex-1">
                        <div class="flex items-center w-full">
                            @if($idx > 0)
                            <div class="flex-1 h-0.5 {{ $done ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                            @endif
                            <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0 {{ $done ? 'bg-green-600' : 'bg-gray-200' }} transition-colors">
                                @if($done)
                                    <i class="fa-solid fa-check text-white text-xs"></i>
                                @else
                                    <span class="text-gray-400 text-xs font-bold">{{ $idx + 1 }}</span>
                                @endif
                            </div>
                            @if($idx < count($steps) - 1)
                            <div class="flex-1 h-0.5 {{ $currentStep !== false && $currentStep > $step['key'] ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                        <p class="text-[10px] font-medium mt-1.5 {{ $done ? 'text-green-700' : 'text-gray-400' }} text-center leading-tight w-full">{{ $step['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="px-5 py-4 border-b border-gray-100 bg-red-50">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-xmark text-red-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold text-red-700">Request Rejected</p>
                        @if($document->remarks)
                            <p class="text-xs text-red-600 mt-1">{{ $document->remarks }}</p>
                        @else
                            <p class="text-xs text-red-500 mt-1">Please visit the Barangay Hall for more information.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Details --}}
            <div class="px-5 py-4">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Date Submitted</dt>
                        <dd class="text-gray-800 font-medium mt-0.5">{{ $document->created_at->format('M d, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Purpose</dt>
                        <dd class="text-gray-800 font-medium mt-0.5">{{ $document->purpose }}</dd>
                    </div>
                    @if($document->issued_at)
                    <div>
                        <dt class="text-xs text-gray-400 font-medium">Date Issued</dt>
                        <dd class="text-gray-800 font-medium mt-0.5">{{ $document->issued_at->format('M d, Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-400 text-center">
                    For inquiries, please visit <strong>Barangay Caranas Hall</strong> or call your barangay office.
                </p>
            </div>
        </div>

        @else

        {{-- Not Found --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm py-12 text-center">
            <i class="fa-solid fa-file-circle-question text-4xl text-gray-300 mb-3 block"></i>
            <p class="text-gray-700 font-semibold">No request found</p>
            <p class="text-sm text-gray-400 mt-1">
                No record with tracking number <span class="font-mono font-bold">{{ request('tracking_number') }}</span> was found.
            </p>
            <p class="text-xs text-gray-400 mt-2">Please double-check your tracking number or visit the Barangay Hall.</p>
        </div>

        @endif
    @endif
</div>

@endsection
