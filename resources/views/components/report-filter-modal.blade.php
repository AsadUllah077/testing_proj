@props(['title' => 'Report Options', 'id' => 'filter-form'])

<div x-data="{ open: false }" @open-report-filter.window="open = true" @close-report-filter.window="open = false">
    <!-- Trigger Button (Optional slot or default) -->
    {{ $trigger ?? '' }}

    <!-- Modal -->
    <div x-show="open" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0" @click.self="open = false">
            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900">
                            {{ $title }}
                        </h3>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body (The Form) -->
                <form id="{{ $id }}" class="bg-white">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{ $slot }}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-3">
                        <button type="button" id="search-btn" @click="open = false"
                                class="inline-flex w-full justify-center rounded-md border border-transparent bg-[#0078C8] px-4 py-2 text-base font-medium text-white hover:bg-[#006bb3] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            <span id="search-text">Update Report</span>
                            <span id="search-loader" class="hidden ml-2">
                                <i class="fas fa-spinner fa-spin text-white"></i>
                            </span>
                        </button>
                        <button type="button" id="reset-btn"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Reset Filters
                        </button>
                        <button type="button" @click="open = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
