<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - School Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased text-white">
    <!-- Navbar -->
    <nav class="fixed w-full z-50 bg-black/20 backdrop-blur-lg border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="/logo.svg" alt="Logo">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="{{ route('pricing') }}" class="text-white/80 hover:text-white">Pricing</a>
                    <a href="{{ route('login') }}" class="text-white/80 hover:text-white">Login</a>
                    <a href="{{ route('register') }}"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Enhanced Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-black">
        <!-- Decorative background elements -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-blue-500/20 to-purple-500/20"></div>
            <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-gray-200/30 to-transparent"></div>
        </div>
        
        <div class="relative pt-32 pb-20 sm:pt-40 sm:pb-24">
            <div class="container max-w-7xl mx-auto px-4 sm:px-6">
                <div class="text-center space-y-8">
                    <div class="space-y-4">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-blue-500/10 text-blue-400 ring-1 ring-blue-400/30">
                            Built for modern schools
                        </span>
                        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight">
                            <span class="block text-white mb-2">Transform Your School's</span>
                            <span class="block bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                                Digital Experience
                            </span>
                        </h1>
                    </div>
                    
                    <p class="max-w-2xl mx-auto text-lg sm:text-xl text-gray-400 leading-relaxed">
                        Stop juggling multiple tools. Get everything you need to run your school efficiently - 
                        <span class="text-gray-300 font-medium">from enrollment to graduation</span>, all in one powerful platform.
                    </p>

                    <!-- Enhanced CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-8">
                        <a href="{{ route('register') }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 text-base font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-500 transition-colors duration-200 shadow-lg shadow-blue-500/25">
                            Start Free Trial
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                        <a href="#pricing"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 text-base font-medium rounded-lg bg-white/10 text-white hover:bg-white/20 transition-colors duration-200 backdrop-blur-sm">
                            View Pricing
                        </a>
                    </div>

                    <!-- Trust Indicators -->
                    <div class="pt-8 mt-8 border-t border-gray-800">
                        <p class="text-sm text-gray-500 mb-4">Trusted by forward-thinking schools</p>
                        <div class="flex justify-center space-x-8">
                            <div class="text-gray-400 text-sm font-semibold">
                                <span class="block text-2xl font-bold text-white">500+</span>
                                Schools
                            </div>
                            <div class="text-gray-400 text-sm font-semibold">
                                <span class="block text-2xl font-bold text-white">50k+</span>
                                Students
                            </div>
                            <div class="text-gray-400 text-sm font-semibold">
                                <span class="block text-2xl font-bold text-white">99%</span>
                                Satisfaction
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section with light background -->
    <section id="pricing" class="bg-gray-50/95 relative">
        <div class="absolute inset-0 bg-gradient-to-b from-gray-100 to-transparent"></div>
        <div class="relative">
            @livewire('pricing-component')
        </div>
    </section>

    <!-- Features Grid Section -->
    <section class="py-20 bg-gray-50">
        <div class="container max-w-7xl mx-auto px-4 sm:px-6">
            <div class="lg:text-center mb-16">
                <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Powerful tools for school management
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Student Management -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Student Management</h3>
                    <p class="text-gray-600">Complete student records, attendance tracking, and performance monitoring
                        in one place.</p>
                </div>

                <!-- Financial Management -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Financial Management</h3>
                    <p class="text-gray-600">Streamline fee collection, track expenses, and manage payroll
                        effortlessly.</p>
                </div>

                <!-- Report Cards -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Report Cards</h3>
                    <p class="text-gray-600">Generate and manage student report cards with customizable templates and
                        grading systems.</p>
                </div>

                <!-- Communication -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Communication Hub</h3>
                    <p class="text-gray-600">Keep parents informed with SMS notifications, announcements, and direct
                        messaging.</p>
                </div>

                <!-- Attendance -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Attendance Tracking</h3>
                    <p class="text-gray-600">Digital attendance management with automated reports and parent
                        notifications.</p>
                </div>

                <!-- Analytics -->
                <div class="p-6 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Analytics & Insights</h3>
                    <p class="text-gray-600">Data-driven insights to track performance, attendance trends, and
                        financial metrics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-blue-600 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                    <span class="block">Ready to transform your school?</span>
                    <span class="block text-blue-200">Get started with our 30-day free trial.</span>
                </h2>
                <div class="mt-8 flex justify-center">
                    <div class="inline-flex rounded-md shadow">
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50">
                            Start Free Trial
                        </a>
                    </div>
                    <div class="ml-3 inline-flex">
                        <a href="#"
                            class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-800 hover:bg-blue-700">
                            Schedule Demo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-50">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Product</h3>
                    <ul class="mt-4 space-y-4">
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Features</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Pricing</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Demo</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Support</h3>
                    <ul class="mt-4 space-y-4">
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Documentation</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Guides</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Help Center</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Company</h3>
                    <ul class="mt-4 space-y-4">
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">About</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Blog</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Contact</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Legal</h3>
                    <ul class="mt-4 space-y-4">
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Privacy</a>
                        </li>
                        <li>
                            <a href="#" class="text-base text-gray-500 hover:text-gray-900">Terms</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-200 pt-8 md:flex md:items-center md:justify-between">
                <div class="flex space-x-6 md:order-2">
                    <!-- Social Links -->
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"
                                clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">
                    &copy; 2024 School Management System. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    {{-- @livewire(\'feedback-modal\' --}}
    @livewire('feedback-modal')

    {{-- @livewire(\'system-announcement-banner\' --}}
</body>

</html>
