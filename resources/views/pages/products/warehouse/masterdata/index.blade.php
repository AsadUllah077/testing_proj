@extends('layouts.auth')
@section('title', 'Master Data')
@section('content')
    <div class="p-4">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.masterData') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>

        <!-- Master Data Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- CATEGORY Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Category</h3>
                    <p class="text-gray-500 text-sm mb-4">Product Categories</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $categoryCount }}</p>
                    <p class="text-gray-500 text-sm">Total Categories</p>

                    <!-- Sub Stats - Uncomment if you have status counts -->
                    {{-- <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">{{ $categoryStatusCounts->active ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-1">ACTIVE</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">{{ $categoryStatusCounts->inactive ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-1">INACTIVE</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-red-500">{{ $categoryStatusCounts->archived ?? 0 }}</p>
                            <p class="text-xs text-gray-500 mt-1">ARCHIVED</p>
                        </div>
                    </div> --}}
                </div>
            </div>

            <!-- LOCATIONS Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Locations</h3>
                    <p class="text-gray-500 text-sm mb-4">Warehouse Locations</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $locationCount }}</p>
                    <p class="text-gray-500 text-sm">Total Locations</p>

                    <!-- Sub Stats -->
                    {{-- <div class="grid grid-cols-3 gap-4 w-full mt-6 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-500">25</p>
                            <p class="text-xs text-gray-500 mt-1">ACTIVE</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-yellow-500">2</p>
                            <p class="text-xs text-gray-500 mt-1">MAINTENANCE</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-500">1</p>
                            <p class="text-xs text-gray-500 mt-1">NEW</p>
                        </div>
                    </div> --}}
                </div>
            </div>

            <!-- DISPOSITIONS Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Dispositions</h3>
                    <p class="text-gray-500 text-sm mb-4">Product Dispositions</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $dispositionCount }}</p>
                    <p class="text-gray-500 text-sm">Total Dispositions</p>
                </div>
            </div>

            <!-- WORKFLOW STAGES Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Workflow Stages</h3>
                    <p class="text-gray-500 text-sm mb-4">Process Workflows</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $workflowStageCount }}</p>
                    <p class="text-gray-500 text-sm">Total Stages</p>
                </div>
            </div>

            <!-- SCRAP TYPES Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Scrap Types</h3>
                    <p class="text-gray-500 text-sm mb-4">Scrap Classification</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $scrapTypeCount }}</p>
                    <p class="text-gray-500 text-sm">Total Types</p>
                </div>
            </div>

            <!-- SCRAP CATEGORY Card -->
            <div class="bg-white rounded-lg  p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Scrap Category</h3>
                    <p class="text-gray-500 text-sm mb-4">Scrap Categories</p>
                    <p class="text-4xl font-bold text-gray-900 mb-2">{{ $scrapCategoryCount }}</p>
                    <p class="text-gray-500 text-sm">Total Categories</p>
                </div>
            </div>
        </div>
    </div>
@endsection
