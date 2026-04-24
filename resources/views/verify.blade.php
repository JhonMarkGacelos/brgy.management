<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Document — Barangay Caranas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen font-sans antialiased bg-gray-50 flex flex-col">

    {{-- Top bar --}}
    <div class="w-full py-3 px-6 flex items-center justify-between border-b border-gray-200 bg-white shadow-sm">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-white ring-2 ring-gray-100 shrink-0 overflow-hidden shadow-sm">
                <img src="{{ asset('images/logo.png') }}" alt="Brgy. Caranas" class="h-full w-full object-cover">
            </div>
            <div>
                <p class="text-sm font-bold text-gray-900 leading-none">Barangay Caranas</p>
                <p class="text-[10px] text-gray-400">Motiong, Samar</p>
            </div>
        </div>
        <a href="{{ route('login') }}"
           class="text-xs font-semibold text-green-700 hover:text-green-800 hover:underline transition-colors flex items-center gap-1">
            <i class="fa-solid fa-right-to-bracket text-[10px]"></i> Staff Login
        </a>
    </div>

    {{-- Hero --}}
    <div class="w-full py-10 px-6 text-center" style="background-color:#1a4731;">
        <div class="flex items-center justify-center gap-3 mb-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15">
                <i class="fa-solid fa-shield-halved text-white text-lg"></i>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-white">Document Verification</h1>
        <p class="text-white/60 text-sm mt-1 max-w-sm mx-auto">
            Verify the authenticity of a Barangay Caranas-issued document using its OR Number or Tracking Number.
        </p>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col items-center px-4 py-10">
        <div class="w-full max-w-lg space-y-6">

            {{-- Search card --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-100 text-green-700 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Enter Reference Number</p>
                </div>
                <form method="POST" action="{{ route('document.verify.check') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            OR Number or Tracking Number
                        </label>
                        <input type="text" name="code"
                               value="{{ old('code', $code ?? '') }}"
                               placeholder="e.g. OR-2026-00001 or DOC-2026-0001"
                               class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-mono
                                      focus:outline-none focus:ring-2 focus:ring-green-600/30 focus:border-green-600
                                      @error('code') border-red-400 @enderror"
                               autofocus>
                        @error('code')
                            <p class="text-xs text-red-600 mt-1.5"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                            style="background-color:#1a4731;"
                            onmouseover="this.style.backgroundColor='#2d6a4f'"
                            onmouseout="this.style.backgroundColor='#1a4731'">
                        <i class="fa-solid fa-shield-halved text-xs"></i> Verify Document
                    </button>
                </form>
            </div>

            {{-- Result --}}
            @if(isset($code))
                @if(isset($document) && $document)
                    @php
                        $isValid   = $document->status === 'Issued';
                        $isPending = $document->status === 'Pending' || $document->status === 'Pending Official';
                        $issuedAt  = $document->issued_at;
                    @endphp

                    {{-- Valid document --}}
                    @if($isValid)
                    <div class="rounded-2xl border-2 border-green-400 bg-green-50 overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 bg-green-600">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                <i class="fa-solid fa-circle-check text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Document Verified</p>
                                <p class="text-xs text-white/70">This document is authentic and was issued by Barangay Caranas.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Document Type</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $document->document_type }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">OR Number</p>
                                    <p class="text-sm font-mono text-gray-900">{{ $document->or_number ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Tracking Number</p>
                                    <p class="text-sm font-mono text-gray-900">{{ $document->tracking_number }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Issued To</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $document->resident?->full_name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Purpose</p>
                                    <p class="text-sm text-gray-900">{{ $document->purpose }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Date Issued</p>
                                    <p class="text-sm text-gray-900">{{ $issuedAt ? $issuedAt->format('F j, Y') : '—' }}</p>
                                </div>
                                @if($document->processedBy)
                                <div class="col-span-2">
                                    <p class="text-[10px] font-semibold text-green-600 uppercase tracking-wide mb-1">Processed By</p>
                                    <p class="text-sm text-gray-900">{{ $document->processedBy->name }} — Barangay Caranas Office</p>
                                </div>
                                @endif
                            </div>

                            <div class="mt-2 flex items-center gap-2 rounded-xl bg-green-100 border border-green-200 px-4 py-3">
                                <i class="fa-solid fa-lock text-green-600 text-xs shrink-0"></i>
                                <p class="text-xs text-green-800">
                                    This verification confirms the document is on record with the Barangay Caranas Management System as of <strong>{{ now()->format('F j, Y') }}</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Pending/Processing --}}
                    @elseif($isPending)
                    <div class="rounded-2xl border-2 border-yellow-300 bg-yellow-50 overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 bg-yellow-400">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/30">
                                <i class="fa-solid fa-clock text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Request Pending</p>
                                <p class="text-xs text-white/80">This document request is still being processed and has not been issued yet.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold text-yellow-700 uppercase tracking-wide mb-1">Document Type</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $document->document_type }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-yellow-700 uppercase tracking-wide mb-1">Tracking Number</p>
                                    <p class="text-sm font-mono text-gray-900">{{ $document->tracking_number }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-yellow-700 uppercase tracking-wide mb-1">Requested By</p>
                                    <p class="text-sm text-gray-900">{{ $document->resident?->full_name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-yellow-700 uppercase tracking-wide mb-1">Date Requested</p>
                                    <p class="text-sm text-gray-900">{{ $document->created_at->format('F j, Y') }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-xl px-4 py-3">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                This document has not yet been officially issued. Please verify with the Barangay Caranas office before accepting it.
                            </p>
                        </div>
                    </div>

                    {{-- Rejected --}}
                    @else
                    <div class="rounded-2xl border-2 border-red-300 bg-red-50 overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 bg-red-500">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                <i class="fa-solid fa-circle-xmark text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Request Rejected / Cancelled</p>
                                <p class="text-xs text-white/70">This document request was rejected and is not valid.</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <p class="text-xs text-red-800 bg-red-100 border border-red-200 rounded-xl px-4 py-3">
                                <i class="fa-solid fa-ban mr-1"></i>
                                The document with reference <strong class="font-mono">{{ $code }}</strong> was rejected. Do not accept this document. Contact the Barangay Caranas office for details.
                            </p>
                        </div>
                    </div>
                    @endif

                @else
                    {{-- Not found --}}
                    <div class="rounded-2xl border-2 border-gray-200 bg-white overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 bg-gray-600">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                <i class="fa-solid fa-circle-question text-white"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Document Not Found</p>
                                <p class="text-xs text-white/70">No document matches the reference number entered.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-3">
                            <p class="text-sm text-gray-600">
                                No issued document was found for: <span class="font-mono font-semibold text-gray-900">{{ $code }}</span>
                            </p>
                            <ul class="space-y-1.5 text-xs text-gray-500">
                                <li class="flex items-start gap-2"><i class="fa-solid fa-circle-dot text-gray-300 mt-0.5 text-[8px]"></i> Double-check the OR Number or Tracking Number on the document.</li>
                                <li class="flex items-start gap-2"><i class="fa-solid fa-circle-dot text-gray-300 mt-0.5 text-[8px]"></i> OR Numbers follow the format <span class="font-mono">OR-YYYY-00000</span>.</li>
                                <li class="flex items-start gap-2"><i class="fa-solid fa-circle-dot text-gray-300 mt-0.5 text-[8px]"></i> Tracking Numbers follow the format <span class="font-mono">DOC-YYYY-0000</span>.</li>
                                <li class="flex items-start gap-2"><i class="fa-solid fa-circle-dot text-gray-300 mt-0.5 text-[8px]"></i> If the document appears legitimate, contact the Barangay Caranas office to confirm.</li>
                            </ul>
                        </div>
                    </div>
                @endif
            @endif

            {{-- How to verify instructions --}}
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-500 text-xs">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">How to Verify a Document</p>
                </div>
                <div class="p-6 space-y-3">
                    <ol class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white mt-0.5" style="background-color:#1a4731;">1</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Locate the reference number</p>
                                <p class="text-xs text-gray-500 mt-0.5">Find the <strong>OR Number</strong> (e.g. OR-2026-00001) at the bottom-left of the document, or the <strong>Tracking Number</strong> (e.g. DOC-2026-0001) printed below the verification box.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white mt-0.5" style="background-color:#1a4731;">2</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Enter the number above</p>
                                <p class="text-xs text-gray-500 mt-0.5">Type or paste the OR Number or Tracking Number into the search box and click <strong>Verify Document</strong>.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white mt-0.5" style="background-color:#1a4731;">3</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Check the result</p>
                                <p class="text-xs text-gray-500 mt-0.5">A <span class="text-green-700 font-semibold">green verified badge</span> confirms the document is authentic and officially issued. Any other result means the document has not been issued.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white mt-0.5" style="background-color:#1a4731;">4</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">When in doubt, contact the Barangay</p>
                                <p class="text-xs text-gray-500 mt-0.5">Visit or call the <strong>Barangay Caranas Hall</strong>, Motiong, Samar during office hours for manual verification.</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>

        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center py-5 text-xs text-gray-400 border-t border-gray-100 bg-white">
        Barangay Caranas, Motiong, Samar &mdash; Document Verification Portal &mdash; &copy; {{ date('Y') }}
    </div>

</body>
</html>
