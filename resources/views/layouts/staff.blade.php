<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>Brgy. Caranas — @yield('title', 'Staff Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900" x-data="{ sidebarOpen: false }">

<div class="flex h-screen overflow-hidden">

    {{-- ── SIDEBAR ── --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-[220px] flex-col transition-transform duration-300 md:translate-x-0"
           style="background-color:#1a4731;"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        {{-- Brgy Logo & Name --}}
        <div class="flex flex-col items-center gap-3 px-4 py-6 border-b border-white/10">
            <div class="h-20 w-20 rounded-full bg-white p-0.5 ring-2 ring-white/20 shadow-lg shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="Brgy. Caranas"
                     class="h-full w-full rounded-full object-cover">
            </div>
            <div class="w-full rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 px-3 py-2.5 text-center">
                <p class="text-white font-bold text-sm leading-tight tracking-wide">Brgy. Caranas</p>
                <p class="text-white/60 text-[11px] mt-0.5">Motiong, Samar</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">

            {{-- Main Menu --}}
            <div>
                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-widest text-white/30">Main Menu</p>
                <div class="space-y-0.5">
                    @php
                        $staffNav = [
                            ['route' => 'staff.dashboard',            'label' => 'Dashboard',     'icon' => 'fa-house'],
                            ['route' => 'staff.residents.index',      'label' => 'Residents',     'icon' => 'fa-users'],
                            ['route' => 'staff.blotter.index',        'label' => 'Complaint',       'icon' => 'fa-shield-halved'],
                            ['route' => 'staff.documents.index',      'label' => 'Documents',     'icon' => 'fa-file-lines'],
                            ['route' => 'staff.announcements.index',  'label' => 'Announcements', 'icon' => 'fa-bullhorn'],
                        ];
                    @endphp
                    @foreach($staffNav as $item)
                        @php $active = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index','.*',$item['route'])); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-150
                                  {{ $active ? 'bg-white/20 text-white font-medium' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
                            <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-[13px] {{ $active ? 'text-white' : 'text-white/40' }}"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

        </nav>

        {{-- User Card --}}
        <div class="border-t border-white/10 px-3 py-4 space-y-2">
            <div class="flex items-center gap-3 px-2">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/20 text-white text-xs font-semibold">
                    {{ strtoupper(substr(Auth::user()->name ?? 'S', 0, 2)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-white">{{ Auth::user()->name ?? 'Staff' }}</p>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span class="inline-flex items-center rounded-full bg-amber-400/20 border border-amber-400/30 px-1.5 py-0.5 text-[9px] font-semibold text-amber-300">
                            <i class="fa-solid fa-id-badge mr-0.5 text-[8px]"></i> CLERK
                        </span>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 rounded-lg border border-white/20 py-2 text-xs font-medium text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div class="fixed inset-0 z-30 bg-black/50 md:hidden"
         x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

    {{-- ── MAIN ── --}}
    <div class="flex flex-col flex-1 md:pl-[220px] min-h-screen overflow-y-auto">

        {{-- Topbar --}}
        <div class="flex items-center justify-between px-4 pt-3 md:justify-end md:px-6">
            <button class="md:hidden rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition-colors"
                    @click="sidebarOpen = !sidebarOpen">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
            @include('partials.notification-bell')
        </div>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-5 mt-4 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <i class="fa-solid fa-circle-check text-green-500 shrink-0"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-5 mt-4 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="fa-solid fa-circle-exclamation text-red-500 shrink-0"></i> {{ session('error') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="mx-5 mt-4 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 shrink-0"></i> {{ session('warning') }}
            </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 p-5 md:p-6">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
