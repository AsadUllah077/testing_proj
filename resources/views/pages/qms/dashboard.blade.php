@extends('layouts.auth')
@section('title')
    Dashboard
@endsection

@section('content')
    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Home Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-indigo-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Home</h3>
                    <p class="text-gray-500 text-sm mb-4">Dashboard Overview</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">22</p>
                    <p class="text-gray-500 text-sm">Total Users</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">3</p>
                            <p class="text-xs text-gray-500 mt-1">TOTAL GROUPS</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">21</p>
                            <p class="text-xs text-gray-500 mt-1">ENABLED USERS</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-500">1</p>
                            <p class="text-xs text-gray-500 mt-1">2025-10-28</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Members Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-pink-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Members</h3>
                    <p class="text-gray-500 text-sm mb-4">User Management</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">3</p>
                    <p class="text-gray-500 text-sm">Total Members</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">3</p>
                            <p class="text-xs text-gray-500 mt-1">VALIDATED</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">EXCLUDED</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">TERMINATED</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Party Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-cyan-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Third Party</h3>
                    <p class="text-gray-500 text-sm mb-4">Partner Relations</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">3</p>
                    <p class="text-gray-500 text-sm">Total ThirdParty</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">OPEN</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">3</p>
                            <p class="text-xs text-gray-500 mt-1">CLOSED</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">PARTNERSHIP</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Product Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Product</h3>
                    <p class="text-gray-500 text-sm mb-4">Inventory Management</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">42</p>
                    <p class="text-gray-500 text-sm">Total Products</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">413</p>
                            <p class="text-xs text-gray-500 mt-1">IN STOCK</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-500">396</p>
                            <p class="text-xs text-gray-500 mt-1">LOW STOCK</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">17</p>
                            <p class="text-xs text-gray-500 mt-1">OUT STOCK</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounting Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Accounting</h3>
                    <p class="text-gray-500 text-sm mb-4">Financial Management</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">306</p>
                    <p class="text-gray-500 text-sm">Total Transactions</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">7</p>
                            <p class="text-xs text-gray-500 mt-1">TOTAL SALES</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">295</p>
                            <p class="text-xs text-gray-500 mt-1">TOTAL EXPENSE</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">TOTAL PURCHASE</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Project</h3>
                    <p class="text-gray-500 text-sm mb-4">Project Management</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">2</p>
                    <p class="text-gray-500 text-sm">Total Projects</p>

                    <!-- Sub Stats -->
                    <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">2</p>
                            <p class="text-xs text-gray-500 mt-1">DRAFT</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">OPENED</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">0</p>
                            <p class="text-xs text-gray-500 mt-1">CLOSED</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
