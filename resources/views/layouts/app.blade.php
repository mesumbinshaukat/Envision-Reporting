<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-black">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside id="sidebar" class="w-64 bg-white border-r border-navy-900 flex flex-col transition-all duration-300">
                <!-- Logo & Toggle -->
                <div class="p-6 border-b border-navy-900 flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="{{ asset('assets/logo.png') }}" alt="{{ config('app.name') }}" class="h-12">
                        <!-- <h2 id="sidebarTitle" class="ml-3 font-bold text-navy-900 whitespace-nowrap">{{ config('app.name') }}</h2> -->
                    </div>
                    <button onclick="toggleSidebar()" class="text-navy-900 hover:bg-navy-900 hover:text-white p-2 rounded">
                        <svg id="toggleIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('dashboard') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Dashboard">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3 sidebar-text">Dashboard</span>
                    </a>
                    <a href="{{ route('clients.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('clients.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Clients">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="ml-3 sidebar-text">Clients</span>
                    </a>
                    
                    @if(auth()->guard('web')->check())
                        <a href="{{ route('employees.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('employees.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Employees">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Employees</span>
                        </a>
                    @endif
                    
                    <a href="{{ route('invoices.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('invoices.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Invoices">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3 sidebar-text">Invoices</span>
                    </a>
                    
                    @if(auth()->guard('web')->check())
                        <a href="{{ route('expenses.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('expenses.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Expenses">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Expenses</span>
                        </a>
                        <a href="{{ route('bonuses.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('bonuses.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Bonuses">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Bonuses</span>
                        </a>
                        <a href="{{ route('salary-releases.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('salary-releases.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Salary Releases">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Salary Releases</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center px-4 py-2 rounded {{ request()->routeIs('reports.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}" title="Reports">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Reports</span>
                        </a>
                    @endif
                </nav>

                <!-- User Menu -->
                <div class="p-4 border-t border-navy-900">
                    <div class="text-sm text-navy-900 mb-2 sidebar-text">
                        {{ auth()->guard('web')->check() ? auth()->guard('web')->user()->name : auth()->guard('employee')->user()->name }}
                        @if(auth()->guard('employee')->check())
                            <span class="text-xs text-gray-600 block">(Employee)</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center px-4 py-2 text-navy-900 hover:bg-navy-900 hover:text-white rounded" title="Logout">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span class="ml-3 sidebar-text">Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const sidebarTexts = document.querySelectorAll('.sidebar-text');
                    const sidebarTitle = document.getElementById('sidebarTitle');
                    const toggleIcon = document.getElementById('toggleIcon');
                    
                    if (sidebar.classList.contains('w-64')) {
                        // Collapse
                        sidebar.classList.remove('w-64');
                        sidebar.classList.add('w-20');
                        sidebarTexts.forEach(text => text.classList.add('hidden'));
                        sidebarTitle.classList.add('hidden');
                        toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>';
                    } else {
                        // Expand
                        sidebar.classList.remove('w-20');
                        sidebar.classList.add('w-64');
                        sidebarTexts.forEach(text => text.classList.remove('hidden'));
                        sidebarTitle.classList.remove('hidden');
                        toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>';
                    }
                }
            </script>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-navy-900">
                        <div class="py-6 px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 p-8 bg-white">
                    <!-- Flash Messages -->
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
