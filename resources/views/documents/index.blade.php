@extends(Auth::user()->role === 'staff' ? 'layouts.staff' : 'layouts.app')
@section('title', 'Document Issuance')

@section('content')
@php $isStaff = Auth::user()->role === 'staff'; @endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Document Issuance</h2>
        <p class="text-sm text-gray-500">Issue and track barangay documents</p>
    </div>
    <a href="{{ route($isStaff ? 'staff.documents.create' : 'documents.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white transition-colors"
       style="background-color:#1a4731;"
       onmouseover="this.style.backgroundColor='#2d6a4f'"
       onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-file-circle-plus"></i> Issue New Document
    </a>
</div>

{{-- Document Type Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    @php
        $docTypes = [
            ['label'=>'Barangay Clearance',       'icon'=>'fa-certificate',       'count'=>$byType['Barangay Clearance']       ?? 0, 'color'=>'bg-blue-500',   'text'=>'text-blue-600',  'bg'=>'bg-blue-50',  'border'=>'border-blue-200'],
            ['label'=>'Certificate of Residency', 'icon'=>'fa-house-circle-check','count'=>$byType['Certificate of Residency'] ?? 0, 'color'=>'bg-green-500',  'text'=>'text-green-600', 'bg'=>'bg-green-50', 'border'=>'border-green-200'],
            ['label'=>'Certificate of Indigency', 'icon'=>'fa-hand-holding-heart','count'=>$byType['Certificate of Indigency'] ?? 0, 'color'=>'bg-yellow-500', 'text'=>'text-yellow-600','bg'=>'bg-yellow-50','border'=>'border-yellow-200'],
            ['label'=>'Business Clearance',       'icon'=>'fa-shop',              'count'=>$byType['Business Clearance']       ?? 0, 'color'=>'bg-purple-500', 'text'=>'text-purple-600','bg'=>'bg-purple-50','border'=>'border-purple-200'],
        ];
    @endphp
    @foreach($docTypes as $doc)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-xl {{ $doc['bg'] }} flex items-center justify-center text-xl {{ $doc['text'] }}">
                <i class="fa-solid {{ $doc['icon'] }}"></i>
            </div>
            <span class="text-2xl font-bold text-gray-800">{{ $doc['count'] }}</span>
        </div>
        <p class="text-sm font-semibold text-gray-700">{{ $doc['label'] }}</p>
        <p class="text-xs text-gray-400 mt-0.5">Issued this month</p>
    </div>
    @endforeach
</div>

{{-- Recent Documents --}}
<form method="GET" action="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}">
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center gap-3">
        <h3 class="font-semibold text-gray-800 flex-1">Document Requests</h3>
        <div class="flex flex-wrap gap-2">
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                    <i class="fa-solid fa-search text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, OR no..."
                       class="pl-8 pr-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-600 w-44">
            </div>
            <select name="type" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
                <option value="">All Types</option>
                @foreach(['Barangay Clearance','Certificate of Residency','Certificate of Indigency','Business Clearance'] as $t)
                <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-600">
                <option value="">All Status</option>
                @foreach(['Pending','Pending Official','Issued','Rejected'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </button>
            @if(request('search') || request('type') || request('status'))
            <a href="{{ route($isStaff ? 'staff.documents.index' : 'documents.index') }}"
               class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-400 hover:bg-gray-50">
                <i class="fa-solid fa-xmark text-xs"></i>
            </a>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs text-gray-500 uppercase border-b">
                    <th class="px-4 py-3">OR No.</th>
                    <th class="px-4 py-3">Resident Name</th>
                    <th class="px-4 py-3">Document Type</th>
                    <th class="px-4 py-3">Date Issued</th>
                    <th class="px-4 py-3">Fee</th>
                    <th class="px-4 py-3">Requested By</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-mono text-xs font-medium text-gray-700">{{ $document->tracking_number }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $document->resident?->full_name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $document->document_type === 'Barangay Clearance' ? 'bg-blue-100 text-blue-700' : ($document->document_type === 'Certificate of Residency' ? 'bg-green-100 text-green-700' : ($document->document_type === 'Certificate of Indigency' ? 'bg-yellow-100 text-yellow-700' : 'bg-purple-100 text-purple-700')) }}">
                            {{ $document->document_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ $document->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-gray-700 font-medium">{{ $document->fee ? '₱'.number_format($document->fee, 2) : 'Free' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $document->requestedBy->name }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route($isStaff ? 'staff.documents.show' : 'documents.show', $document->id) }}" title="View"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                <i class="fa-solid fa-eye text-[11px]"></i> View
                            </a>
                            @if(!$isStaff)
                            <a href="{{ route('documents.edit', $document->id) }}" title="Edit"
                               class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 transition-colors">
                                <i class="fa-solid fa-pen text-[11px]"></i> Edit
                            </a>
                            <form action="{{ route('documents.destroy', $document->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document request?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                    <i class="fa-solid fa-trash text-[11px]"></i> Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                <i class="fa-solid fa-{{ request('search') || request('type') || request('status') ? 'magnifying-glass' : 'file-circle-xmark' }} text-lg"></i>
                            </div>
                            <div>
                                @if(request('search') || request('type') || request('status'))
                                <p class="text-sm font-semibold text-gray-900">No documents found</p>
                                <p class="text-xs text-gray-400 mt-1">No results match your search or filter. Try different keywords.</p>
                                @else
                                <p class="text-sm font-semibold text-gray-900">No document requests yet</p>
                                <p class="text-xs text-gray-400 mt-1">Start by issuing a new document</p>
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
        <span>{{ $documents->total() }} document requests</span>
        {{ $documents->links() }}
    </div>
</div>
</form>

@endsection
