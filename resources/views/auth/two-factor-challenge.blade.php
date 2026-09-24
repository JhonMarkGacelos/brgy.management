<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>Verify Login — Brgy. Caranas</title>
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
            <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">{{ __('auth_pages.login.official_portal') }}</p>
            <h2 class="text-white text-xl font-bold">Barangay Caranas</h2>
            <p class="text-white/50 text-xs mt-0.5">Municipality of Motiong, Samar</p>
        </div>

        <div class="relative z-10">
            <h1 class="text-white text-4xl font-bold leading-tight mb-4">
                {!! __('auth_pages.login.system_title') !!}
            </h1>
            <p class="text-white/50 text-sm leading-relaxed max-w-xs">
                {{ __('auth_pages.login.tagline') }}
            </p>
        </div>

        <p class="relative z-10 text-white/30 text-xs text-center">
            {{ __('auth_pages.login.footer', ['year' => date('Y')]) }}
        </p>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 bg-gray-50">
        <div class="w-full max-w-[360px]">

            {{-- Mobile logo --}}
            <div class="flex items-center justify-between gap-3 mb-8 lg:hidden">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-white p-0.5 shadow-sm shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-full w-full rounded-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Barangay Caranas</p>
                        <p class="text-xs text-gray-400">Motiong, Samar</p>
                    </div>
                </div>
            </div>

            {{-- Icon --}}
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl mb-5 shadow-sm"
                 style="background-color:#1a4731;">
                <i class="fa-solid fa-shield-halved text-white text-lg"></i>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ __('auth_pages.two_factor.title') }}</h2>
            <p class="text-sm text-gray-500 mb-7">
                @php
                    $maskedEmail = \Illuminate\Support\Str::mask($user->email, '*', 2, strpos($user->email, '@') - 2);
                @endphp
                {{ __('auth_pages.two_factor.subtitle', ['email' => $maskedEmail]) }}
            </p>

            {{-- Status --}}
            @if(session('status'))
            <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="fa-solid fa-circle-check shrink-0"></i> {{ session('status') }}
            </div>
            @endif

            {{-- Error --}}
            @if($errors->any())
            <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i> {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('two-factor.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('auth_pages.two_factor.code_label') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 text-sm">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                               required autofocus autocomplete="one-time-code"
                               placeholder="000000"
                               class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 py-3 text-sm tracking-[0.3em] text-gray-900 placeholder-gray-300
                                      focus:border-green-600 focus:ring-2 focus:ring-green-600/20 focus:outline-none transition-all
                                      @error('code') border-red-400 @enderror">
                    </div>
                </div>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold text-white
                               transition-all duration-150 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-600/40"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    {!! __('auth_pages.two_factor.verify_button') !!}
                </button>
            </form>

            <form method="POST" action="{{ route('two-factor.resend') }}" class="mt-4">
                @csrf
                <button type="submit"
                        class="w-full text-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                    {{ __('auth_pages.two_factor.resend_prompt') }} <span class="text-green-700 font-semibold">{{ __('auth_pages.two_factor.resend_button') }}</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left text-xs"></i> {{ __('auth_pages.two_factor.not_you') }}
                </a>
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                {{ __('auth_pages.login.authorized_only') }}
            </p>
        </div>
    </div>

</body>
</html>
