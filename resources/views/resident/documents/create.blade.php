@extends('layouts.resident')
@section('title', 'Request a Document')

@section('content')

<div class="mb-5">
    <a href="{{ route('resident.documents.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-700 transition-colors">
        <i class="fa-solid fa-chevron-left text-xs"></i> Back to My Requests
    </a>
</div>

<div class="max-w-2xl mx-auto">

    @if(session('resident_not_found'))
    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3 bg-red-100 border-b border-red-200">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <p class="text-sm font-semibold text-red-700">Resident Record Not Found</p>
        </div>
        <div class="px-5 py-4 space-y-2">
            <p class="text-sm text-red-700">
                No active resident profile was found matching the name you entered. Your name must be registered in the Barangay Profiling Database before you can request a document.
            </p>
            <p class="text-sm font-semibold text-red-700 mt-2">Please contact any of the following to have your profile added:</p>
            <ul class="mt-1 space-y-1.5">
                <li class="flex items-center gap-2 text-sm text-red-700">
                    <i class="fa-solid fa-star text-red-400 text-xs w-4 text-center"></i>
                    <span><strong>Punong Barangay</strong> (Barangay Captain)</span>
                </li>
                <li class="flex items-center gap-2 text-sm text-red-700">
                    <i class="fa-solid fa-id-badge text-red-400 text-xs w-4 text-center"></i>
                    <span><strong>Barangay Staff / Clerk</strong></span>
                </li>
                <li class="flex items-center gap-2 text-sm text-red-700">
                    <i class="fa-solid fa-people-group text-red-400 text-xs w-4 text-center"></i>
                    <span><strong>Barangay Kagawad</strong> (Council Member)</span>
                </li>
            </ul>
            <p class="text-xs text-red-500 mt-3">Visit the Barangay Caranas Hall during office hours to get registered.</p>
        </div>
    </div>
    @endif

    <div class="mb-5">
        <h2 class="text-xl font-bold text-gray-800">Request a Barangay Document</h2>
        <p class="text-sm text-gray-500 mt-0.5">Fill out the form below. Your request will be processed by barangay staff.</p>
    </div>

    <form action="{{ route('resident.documents.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- Step 1: Document Type --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100" style="background-color:#1a4731;">
                <p class="text-sm font-semibold text-white">
                    <i class="fa-solid fa-file-lines mr-2"></i>Step 1: Select Document Type
                </p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @php
                        $docTypes = [
                            ['value' => 'Barangay Clearance',      'icon' => 'fa-file-shield',        'desc' => 'For employment, NBI, etc.', 'fee' => '₱50'],
                            ['value' => 'Certificate of Residency','icon' => 'fa-house-flag',          'desc' => 'Proof of residence',        'fee' => '₱50'],
                            ['value' => 'Certificate of Indigency','icon' => 'fa-hand-holding-heart',  'desc' => 'For financial assistance',  'fee' => 'Free'],
                        ];
                    @endphp
                    @foreach($docTypes as $dt)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="document_type" value="{{ $dt['value'] }}"
                               class="peer sr-only" {{ old('document_type') === $dt['value'] ? 'checked' : '' }}>
                        <div class="rounded-xl border-2 border-gray-200 p-4 text-center transition-all peer-checked:border-green-600 peer-checked:bg-green-50 hover:border-gray-300">
                            <i class="fa-solid {{ $dt['icon'] }} text-xl text-gray-400 peer-checked:text-green-600 mb-2 block"></i>
                            <p class="text-xs font-semibold text-gray-800 leading-tight">{{ $dt['value'] }}</p>
                            <p class="text-[11px] text-gray-400 mt-1">{{ $dt['desc'] }}</p>
                            <p class="text-[11px] font-bold text-green-700 mt-1">{{ $dt['fee'] }}</p>
                        </div>
                        <div class="absolute top-2 right-2 hidden peer-checked:flex h-5 w-5 items-center justify-center rounded-full bg-green-600">
                            <i class="fa-solid fa-check text-white text-[9px]"></i>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('document_type')
                    <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Step 2: Resident Lookup --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-semibold text-gray-800">
                    <i class="fa-solid fa-user-magnifying-glass mr-2 text-gray-500"></i>Step 2: Resident Verification
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Enter your name exactly as registered in the Barangay Profiling Database.</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}"
                           placeholder="e.g. Juan"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 @error('first_name') border-red-400 @enderror">
                    @error('first_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}"
                           placeholder="e.g. dela Cruz"
                           class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 @error('last_name') border-red-400 @enderror">
                    @error('last_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Step 3: Purpose --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-semibold text-gray-800">
                    <i class="fa-solid fa-pen-to-square mr-2 text-gray-500"></i>Step 3: Purpose
                </p>
            </div>
            <div class="p-5">
                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Purpose of Request <span class="text-red-500">*</span></label>
                <textarea name="purpose" rows="3" placeholder="e.g. For employment purposes, For school enrollment, etc."
                          class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600 resize-none @error('purpose') border-red-400 @enderror">{{ old('purpose') }}</textarea>
                @error('purpose')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Step 4: ID Photo Upload --}}
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <p class="text-sm font-semibold text-gray-800">
                    <i class="fa-solid fa-id-card mr-2 text-gray-500"></i>Step 4: Upload Valid ID
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Required for identity verification. Accepted: PhilSys, Driver's License, Passport, UMID, Voter's ID, etc.</p>
            </div>
            <div class="p-5" x-data="{ preview: null, fileName: null }">
                <label class="block w-full cursor-pointer">
                    <input type="file" name="id_photo" accept="image/*" class="sr-only"
                           @change="
                               const f = $event.target.files[0];
                               if (f) {
                                   fileName = f.name;
                                   const r = new FileReader();
                                   r.onload = e => preview = e.target.result;
                                   r.readAsDataURL(f);
                               }
                           ">
                    <div x-show="!preview"
                         class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 hover:border-green-400 hover:bg-green-50 transition-all">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100">
                            <i class="fa-solid fa-camera text-gray-400 text-lg"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-gray-700">Click to upload your ID photo</p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG or WEBP · Max 5 MB</p>
                        </div>
                    </div>
                    <div x-show="preview" style="display:none" class="relative rounded-xl overflow-hidden border-2 border-green-400">
                        <img :src="preview" class="w-full max-h-64 object-contain bg-gray-100">
                        <div class="absolute bottom-0 inset-x-0 bg-black/50 px-4 py-2 flex items-center justify-between">
                            <span class="text-xs text-white truncate" x-text="fileName"></span>
                            <span class="text-xs text-green-300 font-semibold">
                                <i class="fa-solid fa-check mr-1"></i>Ready to upload
                            </span>
                        </div>
                    </div>
                </label>
                @error('id_photo')
                <p class="text-xs text-red-600 mt-2"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Notice --}}
        <div class="flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
            <i class="fa-solid fa-circle-info text-amber-500 mt-0.5 shrink-0"></i>
            <p class="text-xs text-amber-800 leading-relaxed">
                Your request will be verified against the Barangay Profiling Database. If your name is not found, please visit the <strong>Barangay Hall</strong> to register your profile first. Processing typically takes <strong>1–3 business days</strong>.
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('resident.documents.index') }}"
               class="flex-1 text-center rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="flex-1 rounded-xl py-3 text-sm font-semibold text-white transition-colors"
                    style="background-color:#1a4731;"
                    onmouseover="this.style.backgroundColor='#2d6a4f'"
                    onmouseout="this.style.backgroundColor='#1a4731'">
                <i class="fa-solid fa-paper-plane mr-1.5 text-xs"></i> Submit Request
            </button>
        </div>
    </form>
</div>

@endsection
