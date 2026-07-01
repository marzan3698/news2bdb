<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('News Dashboard') }}
            </h2>
            <span class="px-3 py-1 bg-indigo-500 text-white text-xs font-bold rounded-full uppercase tracking-wider">
                Subscriber Portal
            </span>
        </div>
    </x-slot>

    <div class="py-12 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if(session('error'))
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-rose-850">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Welcome Greeting Card -->
            <div class="bg-gradient-to-r from-indigo-650 to-indigo-800 text-white p-8 rounded-2xl shadow-md relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 translate-y-6 translate-x-6">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <h3 class="text-3xl font-extrabold mb-2">Welcome Back, {{ Auth::user()->name }}!</h3>
                <p class="text-indigo-150 max-w-xl">Enjoy your premium subscription to BDB News. Check out the latest headlines, tailored content, and system announcements curated just for you.</p>
                <div class="mt-6 flex gap-4">
                    <a href="#" class="px-5 py-2.5 bg-white text-indigo-750 font-bold rounded-xl shadow-sm hover:shadow transition-all text-sm">
                        Browse Latest News
                    </a>
                    <a href="{{ route('profile.edit') }}" class="px-5 py-2.5 bg-indigo-600/40 text-white border border-indigo-500/30 hover:bg-indigo-600/60 font-semibold rounded-xl text-sm transition-colors">
                        Manage Profile
                    </a>
                </div>
            </div>

            <!-- Dashboard Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Main News Section (Col-span 2) -->
                <div class="md:col-span-2 space-y-6">
                    <div class="flex justify-between items-center">
                        <h4 class="font-bold text-xl text-slate-800 dark:text-white">Recent News For You</h4>
                        <span class="text-sm text-indigo-500 font-semibold cursor-pointer hover:underline">View all</span>
                    </div>

                    <!-- Mocked Premium News Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow group">
                            <div class="h-40 bg-indigo-100 dark:bg-slate-700 flex items-center justify-center relative">
                                <span class="absolute top-3 left-3 px-2 py-0.5 bg-rose-500 text-white text-xs font-bold rounded">BREAKING</span>
                                <svg class="h-12 w-12 text-indigo-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2m2 2a2 2 0 11-2 2m2-2v10a2 2 0 01-2 2h-2" />
                                </svg>
                            </div>
                            <div class="p-5">
                                <span class="text-xs text-indigo-500 font-bold uppercase">Technology</span>
                                <h5 class="font-bold text-base text-slate-850 dark:text-white mt-1 group-hover:text-indigo-600 transition-colors">Vite 7.0 Officially Released for Production</h5>
                                <p class="text-sm text-slate-400 mt-2 line-clamp-2">Exploring the lightning fast bundler improvements, memory optimizations, and full support for modern ECMAScript standards...</p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow group">
                            <div class="h-40 bg-teal-50 dark:bg-slate-700 flex items-center justify-center relative">
                                <span class="absolute top-3 left-3 px-2 py-0.5 bg-teal-500 text-white text-xs font-bold rounded">TRENDING</span>
                                <svg class="h-12 w-12 text-teal-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="p-5">
                                <span class="text-xs text-teal-500 font-bold uppercase">Economy</span>
                                <h5 class="font-bold text-base text-slate-850 dark:text-white mt-1 group-hover:text-teal-650 transition-colors">Global Markets Bounce Back in Q2 2026</h5>
                                <p class="text-sm text-slate-400 mt-2 line-clamp-2">Detailed stock analysis and projections for technological sectors showing strong recovery and record breaking earnings reports...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Widget -->
                <div class="space-y-6">
                    <!-- Profile Info Widget -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 text-center">
                        <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-900/50 rounded-full mx-auto flex items-center justify-center border border-indigo-100 dark:border-indigo-850">
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 uppercase">
                                {{ substr(Auth::user()->name, 0, 2) }}
                            </span>
                        </div>
                        <h4 class="font-bold text-lg text-slate-850 dark:text-white mt-4">{{ Auth::user()->name }}</h4>
                        <p class="text-sm text-slate-400 mt-0.5">{{ Auth::user()->email }}</p>
                        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex justify-around text-sm text-slate-500">
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-white">Role</span>
                                <span class="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-650 rounded-full dark:bg-indigo-900/30 dark:text-indigo-400 font-semibold border border-indigo-100 dark:border-indigo-850 uppercase tracking-wider">
                                    {{ Auth::user()->role }}
                                </span>
                            </div>
                            <div>
                                <span class="block font-bold text-slate-800 dark:text-white">Joined</span>
                                <span class="text-xs">{{ Auth::user()->created_at->format('M Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- System Notices -->
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h4 class="font-bold text-base text-slate-850 dark:text-white mb-4">Latest System Updates</h4>
                        <div class="space-y-4">
                            <div class="flex space-x-3 items-start">
                                <div class="w-2 h-2 mt-1.5 bg-indigo-500 rounded-full"></div>
                                <div class="text-xs">
                                    <p class="font-bold text-slate-800 dark:text-slate-200">Database optimization completed</p>
                                    <p class="text-slate-400">System maintenance succeeded on June 30, 2026.</p>
                                </div>
                            </div>
                            <div class="flex space-x-3 items-start">
                                <div class="w-2 h-2 mt-1.5 bg-rose-500 rounded-full"></div>
                                <div class="text-xs">
                                    <p class="font-bold text-slate-800 dark:text-slate-200">New Role Middleware deployed</p>
                                    <p class="text-slate-400">Restricted Admin dashboard access checks are now active.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
