<div x-data="vendorModal()" class="relative">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Vendor <span class="text-red-500">*</span>
    </label>

    <div @click="openModal"
        class="bg-white py-2.5 px-3 border border-gray-300 rounded-md text-sm focus:ring-0 focus:outline-none cursor-pointer hover:border-[#0078C8] transition-all flex items-center justify-between group">
        <span x-text="selected?.name ?? 'Select vendor'" :class="selected ? 'text-gray-800' : 'text-gray-400'"></span>
        <svg class="w-4 h-4 text-gray-400 group-hover:text-[#0078C8] transition-colors" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>

    <div x-show="isOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="closeModal"
        @keydown.escape.window="closeModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        style="display: none;">

        <div @click.stop x-show="isOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">

            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-200">
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <span x-show="!showForm">Select Vendor</span>
                        <span x-show="showForm">Add New Vendor</span>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        <span x-show="!showForm">Search and select from existing vendors</span>
                        <span x-show="showForm">Fill in the details to create a new vendor</span>
                    </p>
                </div>
                <button type="button" @click="closeModal"
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2.5 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-hidden flex relative">

                <div x-show="loading && !showForm && filtered.length === 0"
                    class="absolute inset-0 bg-white/70 z-10 flex items-center justify-center transition-opacity duration-300">
                    <div class="flex flex-col items-center">
                        <div
                            class="inline-block w-8 h-8 border-4 border-[#0078C8] border-t-transparent rounded-full animate-spin">
                        </div>
                        <p class="text-sm text-gray-500 mt-3">Loading vendors...</p>
                    </div>
                </div>

                <div x-show="!showForm" class="flex-1 flex flex-col border-r border-gray-200">

                    <div class="p-6 border-b border-gray-200">
                        <div class="relative">
                            <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="search" @input.debounce.300ms="filterVendors"
                                placeholder="Search by name, email, or phone..."
                                class="w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-xl text-sm focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 transition-all outline-none">
                        </div>
                    </div>

                    <div @scroll="handleScroll" class="flex-1 overflow-y-auto p-6">

                        <template x-if="filtered.length > 0">
                            <div class="space-y-3">
                                <template x-for="vendor in filtered" :key="vendor.id">
                                    <div @click="selectVendor(vendor)"
                                        class="p-5 border-2 border-gray-200 rounded-xl hover:border-[#0078C8] hover:shadow-lg cursor-pointer transition-all group bg-white">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4 flex-1">
                                                <div
                                                    class="w-12 h-12 bg-gradient-to-br from-[#0078C8] to-[#006bb0] rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-white font-semibold text-lg"
                                                        x-text="vendor.name.charAt(0).toUpperCase()"></span>
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-semibold text-gray-800 group-hover:text-[#0078C8] transition-colors text-base mb-1"
                                                        x-text="vendor.name"></h4>
                                                    <div class="space-y-1">
                                                        <p class="text-sm text-gray-500 flex items-center truncate"
                                                            x-show="vendor.email">
                                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            <span class="truncate" x-text="vendor.email"></span>
                                                        </p>
                                                        <p class="text-sm text-gray-500 flex items-center"
                                                            x-show="vendor.phone">
                                                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            <span x-text="vendor.phone"></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <svg class="w-6 h-6 text-gray-300 group-hover:text-[#0078C8] transition-colors flex-shrink-0 ml-4"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="filtered.length === 0 && search.length > 0 && !loading">
                            <div class="text-center py-16">
                                <div
                                    class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">No Vendors Found</h4>
                                <p class="text-gray-500 mb-6">
                                    We couldn't find any vendors matching "<span class="font-medium"
                                        x-text="search"></span>"
                                </p>
                                <button type="button" @click="showAddForm()"
                                    class="px-6 py-3 bg-[#0078C8] text-white rounded-xl hover:bg-[#006bb0] transition-all shadow-lg hover:shadow-xl inline-flex items-center gap-2 font-medium">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create New Vendor
                                </button>
                            </div>
                        </template>

                        <template x-if="filtered.length === 0 && search.length === 0 && !loading">
                            <div class="text-center py-16">
                                <div
                                    class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <svg class="w-10 h-10 text-[#0078C8]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">Search Vendors</h4>
                                <p class="text-gray-500">Start typing to find your vendors</p>
                            </div>
                        </template>

                        <div x-show="loading && filtered.length > 0" class="text-center py-6">
                            <div
                                class="inline-block w-8 h-8 border-3 border-[#0078C8] border-t-transparent rounded-full animate-spin">
                            </div>
                            <p class="text-sm text-gray-500 mt-3">Loading more vendors...</p>
                        </div>
                    </div>
                </div>

                <div x-show="showForm" x-transition class="flex-1 overflow-y-auto">

                    <div class="p-8">
                        <div class="max-w-xl mx-auto">

                            <template x-if="errors.global">
                                <div
                                    class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-sm text-red-800">
                                        <p class="font-medium mb-1">Error Saving Vendor</p>
                                        <p class="text-red-700" x-text="errors.global"></p>
                                    </div>
                                </div>
                            </template>

                            <div class="space-y-6">

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Vendor Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" x-model="form.name"
                                            :class="errors.name ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' :
                                                'border-gray-300 focus:border-[#0078C8] focus:ring-[#0078C8]/20'"
                                            class="w-full pl-12 pr-4 py-3.5 border rounded-xl text-sm outline-none focus:ring-2 transition-all"
                                            placeholder="e.g., ABC Corporation">
                                    </div>
                                    <template x-if="errors.name">
                                        <div
                                            class="mt-2 flex items-start gap-2 text-red-600 text-sm bg-red-50 p-3 rounded-lg">
                                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="errors.name[0]"></span>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="email" x-model="form.email"
                                            :class="errors.email ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' :
                                                'border-gray-300 focus:border-[#0078C8] focus:ring-[#0078C8]/20'"
                                            class="w-full pl-12 pr-4 py-3.5 border rounded-xl text-sm outline-none focus:ring-2 transition-all"
                                            placeholder="vendor@company.com">
                                    </div>
                                    <template x-if="errors.email">
                                        <div
                                            class="mt-2 flex items-start gap-2 text-red-600 text-sm bg-red-50 p-3 rounded-lg">
                                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="errors.email[0]"></span>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <input type="text" x-model="form.phone"
                                            :class="errors.phone ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' :
                                                'border-gray-300 focus:border-[#0078C8] focus:ring-[#0078C8]/20'"
                                            class="w-full pl-12 pr-4 py-3.5 border rounded-xl text-sm outline-none focus:ring-2 transition-all"
                                            placeholder="+1 (555) 000-0000">
                                    </div>
                                    <template x-if="errors.phone">
                                        <div
                                            class="mt-2 flex items-start gap-2 text-red-600 text-sm bg-red-50 p-3 rounded-lg">
                                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <span x-text="errors.phone[0]"></span>
                                        </div>
                                    </template>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-sm text-blue-800">
                                        <p class="font-medium mb-1">Quick Tip</p>
                                        <p class="text-blue-700">Make sure the email and phone number are correct for
                                            future communication and invoicing.</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <div x-show="!showForm" class="flex justify-between items-center">
                    <p class="text-sm text-gray-500">
                        <span x-text="filtered.length"></span> vendor<span x-show="filtered.length !== 1">s</span>
                        found
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="showAddForm()"
                            class="px-6 py-3 text-[#0078C8] bg-white border-2 border-[#0078C8] rounded-xl hover:bg-[#0078C8] hover:text-white transition-all font-semibold text-sm inline-flex items-center gap-2 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Add New Vendor
                        </button>
                        <button type="button" @click="closeModal"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-semibold text-sm">
                            Cancel
                        </button>
                    </div>
                </div>

                <div x-show="showForm" class="flex justify-end gap-3">
                    <button type="button" @click="cancelAdd" :disabled="isSaving"
                        class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-all font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                    <button type="button" @click="saveVendor" :disabled="isSaving"
                        class="px-6 py-3 bg-gradient-to-r from-[#0078C8] to-[#006bb0] text-white rounded-xl hover:shadow-xl transition-all font-semibold text-sm inline-flex items-center gap-2 disabled:opacity-75 disabled:cursor-not-allowed">
                        <span x-show="isSaving">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </span>
                        <span x-show="!isSaving">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span x-text="isSaving ? 'Saving...' : 'Save Vendor'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="vendor_id" :value="selected?.id ?? ''">
</div>

<script>
    function vendorModal() {
        return {
            isOpen: false,
            search: "",
            showForm: false,
            selected: null,

            vendors: @json($vendors),
            filtered: [],

            form: {
                name: "",
                email: "",
                phone: "",
            },

            // New states for loading and saving
            loading: false, // For initial/search vendor list loading
            isSaving: false, // For the "Add New Vendor" form submission

            // Errors object is now managed more carefully
            errors: {},

            page: 1,
            hasMore: true,

            openModal() {
                this.isOpen = true;
                this.page = 1;
                this.hasMore = true;
                this.filtered = [];
                this.loadVendors();
                this.showForm = false;
                this.search = "";
                this.errors = {};
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.isOpen = false;
                this.showForm = false;
                this.search = "";
                this.errors = {};
                this.form = {
                    name: "",
                    email: "",
                    phone: ""
                }; // Reset form on close
                document.body.style.overflow = '';
            },

            loadVendors() {
                if (!this.hasMore || this.loading) return;

                this.loading = true;

                // Added .finally() for guaranteed loading reset
                fetch(`{{ route('account.bill.vendors.paginate') }}?page=${this.page}&q=${this.search}`)
                    .then(res => {
                        if (!res.ok) throw new Error("Failed to load vendors.");
                        return res.json();
                    })
                    .then(data => {
                        if (this.page === 1) {
                            this.filtered = data.data;
                        } else {
                            this.filtered.push(...data.data);
                        }

                        if (data
                            .next_page_url
                            ) { // Changed to check for `next_page_url` which is common in Laravel pagination
                            this.page++;
                        } else {
                            this.hasMore = false;
                        }
                    })
                    .catch(e => {
                        console.error("Vendor load error:", e);
                        // Optional: Show a temporary alert to the user
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            // Added debounce to the input in HTML: @input.debounce.300ms="filterVendors"
            filterVendors() {
                this.page = 1;
                this.hasMore = true;
                this.filtered = [];
                this.loadVendors();
            },

            handleScroll(event) {
                // Load more only if we have more, aren't already loading, and scrolled near the bottom
                const bottom = event.target.scrollHeight - event.target.scrollTop <= event.target.clientHeight + 50;
                if (bottom && this.hasMore && !this.loading) {
                    this.loadVendors();
                }
            },

            selectVendor(vendor) {
                this.selected = vendor;
                this.closeModal();
                
                // Dispatch change event to notify duplicate detector (hidden inputs don't fire it automatically)
                this.$nextTick(() => {
                    const input = document.querySelector('input[name="vendor_id"]');
                    if (input) {
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            },

            showAddForm() {
                this.showForm = true;
                // Move search text to the new vendor name field
                this.form.name = this.search;
                this.errors = {};
            },

            cancelAdd() {
                this.showForm = false;
                this.form = {
                    name: "",
                    email: "",
                    phone: ""
                };
                this.errors = {};
            },

            async saveVendor(event) {
                event.preventDefault();
                event.stopPropagation();
                this.errors = {};
                this.isSaving = true; // Start loading indicator

                try {
                    const res = await fetch("{{ route('account.bill.vendors.store.inline') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(this.form)
                    });

                    const data = await res.json();

                    if (res.status === 422) {
                        // Validation error
                        this.errors = data.errors;
                        // Reset global error just in case
                        this.errors.global = null;
                    } else if (!res.ok) {
                        // General server error (500, etc.)
                        throw new Error(data.message || `Server error: ${res.status}`);
                    } else if (data.success) {
                        // Success!
                        // Assuming the server returns the new vendor data.
                        this.vendors.push(data.vendor);
                        this.selected = data.vendor;
                        this.form = {
                            name: "",
                            email: "",
                            phone: ""
                        };
                        this.closeModal();
                    } else {
                        // Custom application error (e.g., business logic)
                        throw new Error(data.message || 'An unknown error occurred.');
                    }
                } catch (e) {
                    console.error("Vendor save error:", e);
                    // Set a global error message for the form
                    this.errors = {
                        ...this.errors,
                        global: e.message || 'Could not save vendor due to a network error.'
                    };
                } finally {
                    this.isSaving = false; // Stop loading indicator
                }
            }
        }
    }
</script>
