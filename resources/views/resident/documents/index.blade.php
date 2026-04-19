@extends('layouts.resident')
@section('title', 'My Document Requests')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">My Document Requests</h2>
        <p class="text-sm text-gray-500">Track all your barangay document requests</p>
    </div>
    <a href="{{ route('resident.documents.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-file-circle-plus text-xs"></i> New Request
    </a>
</div>

{{-- Track Form --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
    <form action="{{ route('resident.documents.track') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <i class="fa-solid fa-barcode text-sm"></i>
            </span>
            <input type="text" name="tracking_number" placeholder="Enter tracking number (e.g. DOC-2024-0012)"
                   class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors shrink-0"
                style="background-color:#1a4731;"
                onmouseover="this.style.backgroundColor='#2d6a4f'"
                onmouseout="this.style.backgroundColor='#1a4731'">
            <i class="fa-solid fa-magnifying-glass text-xs"></i> Track Request
        </button>
    </form>
</div>

{{-- Requests Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    @php
        $statusColors = [
            'Issued'           => 'bg-blue-100 text-blue-700',
            'Approved'         => 'bg-green-100 text-green-700',
            'Pending'          => 'bg-amber-100 text-amber-700',
            'Pending Official' => 'bg-amber-100 text-amber-700',
            'Rejected'         => 'bg-red-100 text-red-700',
        ];
        $statusIcons = [
            'Issued'           => 'fa-file-circle-check',
            'Approved'         => 'fa-circle-check',
            'Pending'          => 'fa-clock',
            'Pending Official' => 'fa-clock',
            'Rejected'         => 'fa-circle-xmark',
        ];
    @endphp
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs text-green-100 uppercase">
                    <th class="px-4 py-3">Tracking No.</th>
                    <th class="px-4 py-3">Document Type</th>
                    <th class="px-4 py-3">Purpose</th>
                    <th class="px-4 py-3">Date Requested</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myRequests as $req)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-mono font-medium text-gray-700 text-xs">{{ $req->tracking_number }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800 text-xs">{{ $req->document_type }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs max-w-[200px] truncate">{{ $req->purpose }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $req->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$req->status] ?? 'bg-gray-100 text-gray-600' }}">
                            <i class="fa-solid {{ $statusIcons[$req->status] ?? 'fa-circle' }} text-[9px]"></i>
                            {{ $req->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                        No document requests found.
                        <a href="{{ route('resident.documents.create') }}" class="text-green-700 font-medium hover:underline">Make your first request.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($myRequests->hasPages())
    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 text-sm text-gray-500">
        <span>Showing {{ $myRequests->firstItem() }}–{{ $myRequests->lastItem() }} of {{ $myRequests->total() }} requests</span>
        <div>{{ $myRequests->links() }}</div>
    </div>
    @endif
</div>

@endsection
