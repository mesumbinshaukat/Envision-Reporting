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
            <aside class="w-64 bg-white border-r border-navy-900 flex flex-col">
                <!-- Logo -->
                <div class="p-6 border-b border-navy-900">
                    <img src="{{ asset('assets/logo.png') }}" alt="{{ config('app.name') }}" class="h-16 mx-auto">
                    <h2 class="text-center mt-2 font-bold text-navy-900">{{ config('app.name') }}</h2>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded {{ request()->routeIs('dashboard') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('clients.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('clients.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Clients
                    </a>
                    <a href="{{ route('employees.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('employees.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Employees
                    </a>
                    <a href="{{ route('invoices.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('invoices.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Invoices
                    </a>
                    <a href="{{ route('expenses.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('expenses.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Expenses
                    </a>
                    <a href="{{ route('bonuses.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('bonuses.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Bonuses
                    </a>
                    <a href="{{ route('salary-releases.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('salary-releases.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Salary Releases
                    </a>
                    <a href="{{ route('reports.index') }}" class="block px-4 py-2 rounded {{ request()->routeIs('reports.*') ? 'bg-navy-900 text-white' : 'text-navy-900 hover:bg-navy-900 hover:text-white' }}">
                        Reports
                    </a>
                </nav>

                <!-- User Menu -->
                <div class="p-4 border-t border-navy-900">
                    <div class="text-sm text-navy-900 mb-2">{{ Auth::user()->name }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-navy-900 hover:bg-navy-900 hover:text-white rounded">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

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
