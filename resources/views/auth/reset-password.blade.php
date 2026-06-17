<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>Reset Password — Brgy. Caranas</title>
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
                Integrated<br>Barangay<br>Management<br>System
            </h1>
            <p class="text-white/50 text-sm leading-relaxed max-w-xs">
                A centralized platform for managing resident records, blotter cases, document issuance, and community announcements.
            </p>
        </div>

        <p class="relative z-10 text-white/30 text-xs text-center">
            Motiong, Samar &mdash; &copy; {{ date('Y') }} All rights reserved.
        </p>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 bg-gray-50">
        <div class="w-full max-w-[360px]">

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

            {{-- Icon --}}
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl mb-5 shadow-sm"
                 style="background-color:#1a4731;">
                <i class="fa-solid fa-lock-open text-white text-lg"></i>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-1">Set New Password</h2>
            <p class="text-sm text-gray-500 mb-7">Choose a strong password for your account.</p>

            {{-- Errors --}}
            @if($errors->any())
            <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i> {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4" x-data="{ showPw: false, showConfirm: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input id="email" type="email" name="email"
                               value="{{ old('email', $request->email) }}"
                               required autofocus autocomplete="username"
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-4 py-3 text-sm text-gray-900
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('email') border-red-400 @enderror">
                    </div>
                </div>

                {{-- New Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">New Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input id="password" :type="showPw ? 'text' : 'password'" name="password"
                               required autocomplete="new-password"
                               placeholder="Enter new password"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('password') border-red-400 @enderror">
                        <button type="button" @click="showPw = !showPw"
                                class="absolute inset-y-0 right-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i :class="showPw ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input id="password_confirmation" :type="showConfirm ? 'text' : 'password'"
                               name="password_confirmation"
                               required autocomplete="new-password"
                               placeholder="Re-enter new password"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 py-3 text-sm text-gray-900 placeholder-gray-400
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('password_confirmation') border-red-400 @enderror">
                        <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute inset-y-0 right-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white
                               transition-all duration-150 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-600/40"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-check text-xs"></i>
                    Reset Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Back to Sign In
                </a>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                Authorized personnel only &mdash; Brgy. Caranas, Motiong, Samar
            </p>
        </div>
    </div>

</body>
</html>
