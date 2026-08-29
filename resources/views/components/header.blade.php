<style>
    .erp-translate-shell {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 9999px;
        padding: 0.375rem 0.75rem 0.375rem 0.45rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 10px 30px -20px rgba(15, 23, 42, 0.55);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .erp-translate-shell:hover {
        border-color: rgba(0, 120, 200, 0.35);
        box-shadow: 0 16px 30px -22px rgba(0, 120, 200, 0.55);
        transform: translateY(-1px);
    }

    .erp-translate-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #0078C8 0%, #005fa3 100%);
        color: #ffffff;
        flex-shrink: 0;
        box-shadow: 0 10px 20px -14px rgba(0, 120, 200, 0.85);
    }

    .erp-translate-select-wrap {
        position: relative;
        min-width: 0;
        flex: 1;
    }

    .erp-translate-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        min-width: 5.5rem;
        border: 0;
        background: transparent;
        color: #0f172a;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1.2;
        padding: 0.125rem 1rem 0.125rem 0;
        cursor: pointer;
        outline: none;
        box-shadow: none;
    }

    .erp-translate-select-label {
        display: block;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.125rem;
    }

    .erp-translate-shell::after {
        content: "";
        position: absolute;
        right: 0.8rem;
        top: 50%;
        width: 0.5rem;
        height: 0.5rem;
        margin-top: -0.3rem;
        border-right: 2px solid #64748b;
        border-bottom: 2px solid #64748b;
        transform: rotate(45deg);
        pointer-events: none;
    }

    #google_translate_element {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        clip-path: inset(50%);
    }

    body {
        top: 0 !important;
    }

    .goog-te-banner-frame.skiptranslate {
        display: none !important;
    }

    @media (max-width: 639px) {
        .erp-translate-shell {
            max-width: 8.75rem;
            padding-right: 0.95rem;
        }

        .erp-translate-select-label {
            display: none;
        }

        .erp-translate-select {
            min-width: 4rem;
            font-size: 0.7rem;
        }
    }
</style>

<header class="bg-white border-b border-gray-200">
    <div class="flex items-center justify-between px-4 py-4">
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Toggle -->
            <button id="menuToggle" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>

            <!-- Breadcrumb -->
            <nav class="hidden sm:flex items-center space-x-2 text-sm text-gray-600">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-900 transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span>Home</span>
                </a>
                @php
                    $segments = Request::segments();
                    $url = '';
                @endphp
                @foreach ($segments as $segment)
                    @php
                        $url .= '/' . $segment;
                        $name = str_replace(['-', '_'], ' ', $segment);
                        $name = ucwords($name);
                    @endphp
                    <span class="text-gray-400">/</span>
                    @if ($loop->last)
                        <span class="text-gray-900 font-medium">{{ $name }}</span>
                    @else
                        <a href="#" class="hover:text-gray-900 transition-colors">{{ $name }}</a>
                    @endif
                @endforeach
            </nav>
        </div>

        <!-- Search & Notifications -->
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="hidden md:block relative" x-data="{
                search: '{{ request('search') }}',
                results: [],
                loading: false,
                showDropdown: false,
                timer: null,
                fetchResults() {
                    if (this.search.length < 2) {
                        this.results = [];
                        this.showDropdown = false;
                        return;
                    }
                    this.loading = true;
                    this.showDropdown = true;
                    fetch(`/global-search?query=${encodeURIComponent(this.search)}`)
                        .then(res => res.json())
                        .then(data => {
                            this.results = data;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.loading = false;
                        });
                },
                handleInput() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.fetchResults(), 300);
                }
            }" @click.away="showDropdown = false">
                <form action="{{ url()->current() }}" method="GET" class="relative">
                    @foreach (request()->except('search') as $key => $value)
                        @if (is_array($value))
                            @foreach ($value as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <input type="text" name="search" x-model="search" @input="handleInput"
                        @focus="if(results.length > 0) showDropdown = true" placeholder="Search here..."
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0078C8] transition-all"
                        autocomplete="off">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-[#0078C8]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Search Results Dropdown -->
                <div x-show="showDropdown" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute left-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                    style="display: none;">
                    <div class="max-h-96 overflow-y-auto">
                        <template x-if="loading">
                            <div class="p-4 text-center text-gray-500">
                                <svg class="animate-spin h-5 w-5 mx-auto mb-2 text-[#0078C8]" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Searching...
                            </div>
                        </template>

                        <template x-if="!loading && results.length === 0">
                            <div class="p-4 text-center text-gray-500">
                                No results found for "<span x-text="search" class="font-medium"></span>"
                            </div>
                        </template>

                        <template x-for="result in results" :key="result.url + result.title">
                            <a :href="result.url" target="_blank"
                                class="block p-3 hover:bg-gray-50 border-b border-gray-50 last:border-0 transition-colors">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-semibold text-gray-900" x-text="result.title"></span>
                                    <span
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 uppercase tracking-wider"
                                        x-text="result.category"></span>
                                </div>
                                <div class="text-xs text-gray-500 truncate" x-text="result.description"></div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <div class="erp-translate-shell">
                <span class="erp-translate-icon" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M3 5h12M9 3v2m1.5 13.5L8 21m2.5-2.5L13 12m0 0l2.5 6.5M13 12h6m-6 0a13.8 13.8 0 01-4-7m4 7a13.8 13.8 0 004 7">
                        </path>
                    </svg>
                </span>
                <div class="erp-translate-select-wrap">
                    <select id="erp-translate-select" class="erp-translate-select" aria-label="Translate page">
                        <option value="en">English</option>
                        <option value="ur">Urdu</option>
                        <option value="ar">Arabic</option>
                        <option value="zh-CN">Chinese</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                        <option value="es">Spanish</option>
                        <option value="tr">Turkish</option>
                        <option value="hi">Hindi</option>
                        <option value="ru">Russian</option>
                    </select>
                    <div id="google_translate_element"></div>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="p-2 text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-xl border border-gray-100 z-50 py-1">

                    {{-- Edit Profile --}}
                    <button @click="open = false; $dispatch('open-edit-profile')"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Edit Profile
                    </button>

                    <div class="border-t border-gray-100 my-1"></div>

                    <button @click="open = false; $dispatch('open-company-switch')"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        Switch Company
                    </button>
                    @if (Auth::user()->is_admin ?? false)
                        <a href="{{ route('home.companies.index') }}"
                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            Company Settings
                        </a>
                    @endif
                    <div class="px-4 py-2 border-t border-gray-100 bg-gray-50/50">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Current Company</p>
                        <p class="text-sm font-semibold text-gray-700 truncate">
                            {{ loggedCompanyId()['company_name'] }}
                        </p>
                    </div>
                </div>
            </div>

            <button class="p-2 text-gray-600 hover:text-gray-900 relative">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div x-data="{
        show: false,
        loading: false,
        success: '',
        errors: {},
        showPassword: false,
        showConfirmPassword: false,
        previewUrl: '{{ Auth::user()->profile_pic ? asset(Auth::user()->profile_pic) : '' }}',
        form: {
            first_name: '{{ addslashes(Auth::user()->first_name) }}',
            last_name: '{{ addslashes(Auth::user()->last_name) }}',
            username: '{{ addslashes(Auth::user()->username) }}',
            email: '{{ addslashes(Auth::user()->email) }}',
            phone_number: '{{ addslashes(Auth::user()->phone_number ?? '') }}',
            password: '',
            password_confirmation: ''
        },
        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.previewUrl = URL.createObjectURL(file);
            }
        },
        async submit() {
            this.loading = true;
            this.errors = {};
            this.success = '';
            const fd = new FormData(this.$refs.profileForm);
            fd.append('_method', 'PUT');
            try {
                const res = await fetch('{{ route('profile.update') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: fd
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.success = data.message;
                    setTimeout(() => { this.show = false;
                        window.location.reload(); }, 1200);
                } else if (data.errors) {
                    this.errors = data.errors;
                } else {
                    this.errors._general = data.message ?? 'Something went wrong.';
                }
            } catch (e) {
                this.errors._general = 'Network error. Please try again.';
            }
            this.loading = false;
        }
    }" @open-edit-profile.window="show = true" x-show="show"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            {{-- Backdrop --}}
            <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50" @click="show = false"></div>

            {{-- Panel --}}
            <div x-show="show" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95" @click.stop
                class="relative z-10 w-full max-w-lg bg-white rounded-xl shadow-2xl">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-[#0078C8] rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Edit Profile</h3>
                            <p class="text-xs text-gray-400">Update your personal information</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form --}}
                <form x-ref="profileForm" @submit.prevent="submit" enctype="multipart/form-data"
                    class="px-6 py-5 space-y-4">

                    {{-- Success --}}
                    <div x-show="success" x-transition
                        class="flex items-center space-x-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="success"></span>
                    </div>
                    {{-- General error --}}
                    <div x-show="errors._general" x-transition
                        class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2.5"
                        x-text="errors._general"></div>

                    {{-- Avatar --}}
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <div
                                class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center">
                                <template x-if="previewUrl">
                                    <img :src="previewUrl" class="w-16 h-16 object-cover rounded-full">
                                </template>
                                <template x-if="!previewUrl">
                                    <span
                                        class="text-white font-bold text-xl">{{ strtoupper(substr(Auth::user()->first_name ?? '', 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name ?? '', 0, 1)) }}</span>
                                </template>
                            </div>
                            <label for="profile_pic_input"
                                class="absolute -bottom-1 -right-1 w-6 h-6 bg-[#0078C8] rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition-colors">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.914l-3.414.672.672-3.414a4 4 0 01.914-1.414z" />
                                </svg>
                            </label>
                        </div>
                        <div>
                            <input id="profile_pic_input" type="file" name="profile_pic" accept="image/*"
                                class="hidden" @change="handleFileChange">
                            <label for="profile_pic_input"
                                class="text-xs text-[#0078C8] hover:text-blue-700 font-medium cursor-pointer">Change
                                photo</label>
                            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WebP — max 5 MB</p>
                        </div>
                    </div>

                    {{-- Name row --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">First Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="first_name" x-model="form.first_name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                :class="errors.first_name ? 'border-red-400' : ''">
                            <p x-show="errors.first_name" class="text-xs text-red-500 mt-1"
                                x-text="errors.first_name ? errors.first_name[0] : ''"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Last Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="last_name" x-model="form.last_name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent"
                                :class="errors.last_name ? 'border-red-400' : ''">
                            <p x-show="errors.last_name" class="text-xs text-red-500 mt-1"
                                x-text="errors.last_name ? errors.last_name[0] : ''"></p>
                        </div>
                    </div>

                    {{-- Username --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                        <input type="text" name="username" x-model="form.username" readonly
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500 cursor-not-allowed select-none">
                        <p class="text-xs text-gray-400 mt-1">Username cannot be changed.</p>
                    </div>

                    {{-- Email + Phone --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" x-model="form.email" readonly
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-500 cursor-not-allowed select-none">
                            <p class="text-xs text-gray-400 mt-1">Email cannot be changed.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input type="text" name="phone_number" x-model="form.phone_number"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent">
                        </div>
                    </div>

                    {{-- Password section --}}
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Change Password
                            <span class="normal-case text-gray-400 font-normal">(leave blank to keep current)</span>
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                                <div class="relative group">
                                    <input :type="showPassword ? 'text' : 'password'" name="password"
                                        x-model="form.password"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent pr-10"
                                        :class="errors.password ? 'border-red-400' : ''"
                                        placeholder="Min. 8 characters">
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-[#0078C8] transition-colors">
                                        <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 hidden" :class="{ 'hidden': !showPassword }" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                                <p x-show="errors.password" class="text-xs text-red-500 mt-1"
                                    x-text="errors.password ? errors.password[0] : ''"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password</label>
                                <div class="relative group">
                                    <input :type="showConfirmPassword ? 'text' : 'password'"
                                        name="password_confirmation" x-model="form.password_confirmation"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0078C8] focus:border-transparent pr-10"
                                        placeholder="Repeat new password">
                                    <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-[#0078C8] transition-colors">
                                        <svg x-show="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <svg x-show="showConfirmPassword" xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 hidden" :class="{ 'hidden': !showConfirmPassword }"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password Rules -->
                        <div class="mt-3 space-y-1">
                            <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wider">Password
                                Requirements:</p>
                            <ul class="text-[11px] text-gray-400 space-y-0.5 list-disc list-inside px-1">
                                <li>Minimum 8 characters</li>
                                <li>At least one uppercase & one lowercase letter</li>
                                <li>At least one number (0-9)</li>
                                <li>At least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" @click="show = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="loading"
                            class="inline-flex items-center px-5 py-2 text-sm font-medium text-white bg-[#0078C8] rounded-lg hover:bg-blue-700 disabled:opacity-60 transition-colors">
                            <svg x-show="loading" class="animate-spin w-4 h-4 mr-2" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="loading ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Company Switch Modal -->
    <div x-data="{
        show: false,
        password: '',
        selectedCompany: '{{ Auth::user()->company_id }}',
        loading: false,
        error: '',
        companies: @js(
    Auth::user()
        ->companies()
        ->get(['companies.id', 'name']),
)
    }" @open-company-switch.window="show = true" x-show="show"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-black/50" aria-hidden="true" @click="show = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" @click.stop
                class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-[#0078C8] rounded-full">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-5">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Switch Company</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            Select the company you want to switch to and enter your password to confirm.
                        </div>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700">Select Company</label>
                        <select id="company_id" x-model="selectedCompany"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                            <template x-if="companies.length > 0">
                                <template x-for="company in companies" :key="company.id">
                                    <option :value="company.id" :selected="company.id == selectedCompany"
                                        x-text="company.name"></option>
                                </template>
                            </template>
                            <template x-else>
                                <option value="">No companies available</option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="switch_password" class="block text-sm font-medium text-gray-700">Confirm
                            Password</label>
                        <input type="password" id="switch_password" x-model="password"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100"
                            placeholder="Enter your password">
                    </div>

                    <div x-show="error" class="text-sm text-red-600" x-text="error"></div>
                </div>

                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="button"
                        @click="
                        loading = true;
                        error = '';
                        fetch('{{ route('company.switch') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                company_id: selectedCompany,
                                password: password
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            loading = false;
                            if (data.success) {
                                window.location.reload();
                            } else {
                                error = data.message;
                            }
                        })
                        .catch(err => {
                            loading = false;
                            error = 'An error occurred. Please try again.';
                        })
                    "
                        :disabled="loading"
                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-[#0078C8] border border-transparent rounded-md shadow-sm hover:bg-[#0078C8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm">
                        <span x-show="!loading">Confirm Switch</span>
                        <span x-show="loading">Switching...</span>
                    </button>
                    <button type="button" @click="show = false; password = ''; error = ''"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            if (window.erpTranslateWidgetBooted) {
                return;
            }

            window.erpTranslateWidgetBooted = true;

            const translateSelectId = 'erp-translate-select';
            const translateContainerId = 'google_translate_element';
            const languageStorageKey = 'erp_translate_language';
            const defaultLanguage = 'en';

            function getTranslateSelect() {
                return document.getElementById(translateSelectId);
            }

            function normalizeLanguage(language) {
                return language && language !== '' ? language : defaultLanguage;
            }

            function readGoogleTranslateCookie() {
                const cookieValue = document.cookie
                    .split('; ')
                    .find((row) => row.startsWith('googtrans='));

                if (!cookieValue) {
                    return null;
                }

                const value = decodeURIComponent(cookieValue.split('=')[1] || '');
                const parts = value.split('/');
                const language = parts[parts.length - 1];

                return language ? normalizeLanguage(language) : null;
            }

            function setGoogleTranslateCookie(language) {
                const nextLanguage = normalizeLanguage(language);
                const cookieValue = '/en/' + nextLanguage;
                const hostname = window.location.hostname;
                const rootDomain = hostname.includes('.') ? '.' + hostname.split('.').slice(-2).join('.') : hostname;

                document.cookie = 'googtrans=' + cookieValue + '; path=/';
                document.cookie = 'googtrans=' + cookieValue + '; path=/; domain=' + hostname;
                document.cookie = 'googtrans=' + cookieValue + '; path=/; domain=' + rootDomain;
            }

            function syncSelectValue(language) {
                const select = getTranslateSelect();

                if (select) {
                    select.value = normalizeLanguage(language);
                }
            }

            function applyLanguage(language, reloadPage = false) {
                const nextLanguage = normalizeLanguage(language);

                localStorage.setItem(languageStorageKey, nextLanguage);
                setGoogleTranslateCookie(nextLanguage);
                syncSelectValue(nextLanguage);

                const googleCombo = document.querySelector('#' + translateContainerId + ' select.goog-te-combo');

                if (googleCombo && nextLanguage !== defaultLanguage) {
                    googleCombo.value = nextLanguage;
                    googleCombo.dispatchEvent(new Event('change'));
                    return;
                }

                if (reloadPage || nextLanguage === defaultLanguage) {
                    window.location.reload();
                }
            }

            function resolveInitialLanguage() {
                return readGoogleTranslateCookie() ||
                    localStorage.getItem(languageStorageKey) ||
                    defaultLanguage;
            }

            function bootGoogleTranslate() {
                const container = document.getElementById(translateContainerId);

                if (!container || container.dataset.loaded === 'true') {
                    return;
                }

                if (!(window.google && google.translate && google.translate.TranslateElement)) {
                    return;
                }

                new google.translate.TranslateElement({
                    pageLanguage: 'en',
                    autoDisplay: false,
                    includedLanguages: 'ar,ur,zh-CN,fr,de,es,tr,hi,ru'
                }, translateContainerId);

                container.dataset.loaded = 'true';

                const observer = new MutationObserver(() => {
                    const googleCombo = container.querySelector('select.goog-te-combo');

                    if (!googleCombo) {
                        return;
                    }

                    googleCombo.addEventListener('change', function() {
                        const selectedLanguage = normalizeLanguage(googleCombo.value);
                        localStorage.setItem(languageStorageKey, selectedLanguage);
                        syncSelectValue(selectedLanguage);
                    });

                    const initialLanguage = resolveInitialLanguage();
                    syncSelectValue(initialLanguage);

                    if (initialLanguage !== defaultLanguage) {
                        googleCombo.value = initialLanguage;
                        googleCombo.dispatchEvent(new Event('change'));
                    }

                    observer.disconnect();
                });

                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            }

            window.googleTranslateElementInit = bootGoogleTranslate;

            document.addEventListener('DOMContentLoaded', function() {
                const select = getTranslateSelect();

                syncSelectValue(resolveInitialLanguage());

                if (select) {
                    select.addEventListener('change', function(event) {
                        const selectedLanguage = event.target.value;
                        const googleCombo = document.querySelector('#' + translateContainerId +
                            ' select.goog-te-combo');

                        if (googleCombo && selectedLanguage !== defaultLanguage) {
                            applyLanguage(selectedLanguage);
                            return;
                        }

                        applyLanguage(selectedLanguage, true);
                    });
                }

                if (!document.getElementById('google-translate-script')) {
                    const script = document.createElement('script');
                    script.id = 'google-translate-script';
                    script.src =
                        'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                    script.async = true;
                    document.body.appendChild(script);
                } else {
                    bootGoogleTranslate();
                }
            });
        })();
    </script>
</header>
