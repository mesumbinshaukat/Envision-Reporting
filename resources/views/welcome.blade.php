<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-black">
    <!-- Header -->
    <header class="bg-white border-b border-navy-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="{{ asset('assets/logo.png') }}" alt="Logo" class="h-16">
                    <div>
                        <h1 class="text-2xl font-bold text-navy-900">{{ config('app.name') }}</h1>
                        <p class="text-sm text-gray-600">Simple Client & Employee Manager</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="px-6 py-2 border border-navy-900 text-navy-900 rounded hover:bg-navy-900 hover:text-white">Login</a>
                        <a href="{{ route('register') }}" class="px-6 py-2 bg-navy-900 text-white rounded hover:bg-opacity-90">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Welcome Section -->
        <section class="text-center mb-16">
            <h2 class="text-4xl font-bold text-navy-900 mb-4">Welcome to Client Manager</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                A simple and efficient system to manage your clients, employees, invoices, salaries, expenses, and bonuses all in one place.
            </p>
        </section>

        <!-- Features Section -->
        <section class="mb-16">
            <h3 class="text-3xl font-bold text-navy-900 text-center mb-8">Features</h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Manage Clients</h4>
                    <p class="text-gray-600">Keep track of all your clients with detailed contact information, pictures, and websites.</p>
                </div>
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Employee Management</h4>
                    <p class="text-gray-600">Manage employee details, roles, salaries, and commission rates efficiently.</p>
                </div>
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Invoice Tracking</h4>
                    <p class="text-gray-600">Create and track invoices with automatic commission calculations for sales employees.</p>
                </div>
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Salary Management</h4>
                    <p class="text-gray-600">Release salaries with automated calculations including base pay, commissions, and bonuses.</p>
                </div>
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Expense Tracking</h4>
                    <p class="text-gray-600">Record and monitor all business expenses with date filtering and totals.</p>
                </div>
                <div class="border border-navy-900 rounded-lg p-6">
                    <h4 class="text-xl font-bold text-navy-900 mb-3">Comprehensive Reports</h4>
                    <p class="text-gray-600">Generate detailed audit reports with PDF exports for all transactions.</p>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="text-center bg-navy-900 text-white rounded-lg py-12 px-6">
            <h3 class="text-3xl font-bold mb-4">Ready to Get Started?</h3>
            <p class="text-lg mb-6">Join us today and streamline your business management.</p>
            <div class="flex justify-center space-x-4">
                @guest
                    <a href="{{ route('register') }}" class="px-8 py-3 bg-white text-navy-900 rounded font-semibold hover:bg-gray-100">Sign Up Now</a>
                    <a href="{{ route('login') }}" class="px-8 py-3 border border-white text-white rounded font-semibold hover:bg-white hover:text-navy-900">Login</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="px-8 py-3 bg-white text-navy-900 rounded font-semibold hover:bg-gray-100">Go to Dashboard</a>
                @endguest
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-navy-900 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-gray-600">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
