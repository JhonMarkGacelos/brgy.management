<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account — Brgy. Caranas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen font-sans antialiased flex bg-gray-100">

    {{-- ── LEFT PANEL ── --}}
    <div class="hidden lg:flex lg:w-[42%] flex-col justify-between p-10 relative overflow-hidden"
         style="background-color:#1a4731;">

        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/5"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full bg-white/5"></div>
        </div>

        <div class="relative z-10 flex flex-col items-center text-center">
            <div class="h-28 w-28 rounded-full bg-white p-0.5 ring-4 ring-white/20 mb-4 shadow-2xl shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="Brgy. Caranas Logo"
                     class="h-full w-full rounded-full object-cover">
            </div>
            <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Official Portal</p>
            <h2 class="text-white text-xl font-bold">Barangay Caranas</h2>
            <p class="text-white/50 text-xs mt-0.5">Municipality of Motiong, Samar</p>
        </div>

        <div class="relative z-10">
            <h1 class="text-white text-4xl font-bold leading-tight mb-4">
                Resident<br>Self-Service<br>Portal
            </h1>
            <p class="text-white/50 text-sm leading-relaxed max-w-xs">
                Create your resident account to request barangay documents, track your requests, and view community announcements — anytime, anywhere.
            </p>

            <div class="mt-8 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/10 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/40 mb-1">Request</p>
                    <p class="text-white text-sm font-semibold">Barangay Documents</p>
                </div>
                <div class="rounded-xl bg-white/10 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-white/40 mb-1">Track</p>
                    <p class="text-white text-sm font-semibold">Request Status</p>
                </div>
            </div>
        </div>

        <p class="relative z-10 text-white/30 text-xs text-center">
            Motiong, Samar &mdash; &copy; {{ date('Y') }} All rights reserved.
        </p>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 bg-gray-50">
        <div class="w-full max-w-[380px]">

            {{-- Mobile logo --}}
            <div class="flex items-center gap-3 mb-8 lg:hidden">
                <div class="h-12 w-12 rounded-full bg-white p-0.5 shadow-sm shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-full w-full rounded-full object-cover">
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900">Barangay Caranas</p>
                    <p class="text-xs text-gray-400">Motiong, Samar</p>
                </div>
            </div>

            {{-- Resident badge --}}
            <div class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 border border-sky-200 px-3 py-1 mb-4">
                <i class="fa-solid fa-user text-sky-600 text-[10px]"></i>
                <span class="text-xs font-semibold text-sky-700">Resident Account</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-1">Create your account</h2>
            <p class="text-sm text-gray-500 mb-7">Register to access the Barangay Caranas resident portal.</p>

            {{-- Errors --}}
            @if($errors->any())
                <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                {{-- Full Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Full Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="name" value="{{ old('name') }}"
                               required autofocus autocomplete="name"
                               placeholder="e.g. Juan dela Cruz"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('name') border-red-400 @enderror">
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}"
                               required autocomplete="username"
                               placeholder="e.g. juan@email.com"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('email') border-red-400 @enderror">
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input :type="show ? 'text' : 'password'" name="password"
                               required autocomplete="new-password"
                               placeholder="Min. 8 characters"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('password') border-red-400 @enderror">
                        <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input :type="show ? 'text' : 'password'" name="password_confirmation"
                               required autocomplete="new-password"
                               placeholder="Re-enter your password"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all">
                        <button type="button" @click="show = !show"
                                class="absolute inset-y-0 right-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white
                               transition-all duration-150 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-600/40"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    Create Account
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-500">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-green-700 hover:underline">Sign in</a>
            </p>

            <p class="mt-4 text-center text-xs text-gray-400">
                For Staff &amp; Admin access, contact your Barangay Administrator.
            </p>
        </div>
    </div>

</body>
</html>
