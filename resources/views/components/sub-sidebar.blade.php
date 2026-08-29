@props(['sub_modules'])
<nav class="py-4 overflow-y-auto h-[calc(100vh-120px)] ">
    {{-- back button — hidden for technician role --}}
    @php
        $isTechnician = auth()->check() && auth()->user()->role
            && str_contains(strtolower(auth()->user()->role->name ?? ''), 'technician');
    @endphp

    @if (!$isTechnician)
        @if (Request::routeIs('warehouse.*'))
            <a href="{{ route('products.dashboard') }}"
                class="flex items-baseline space-x-3 px-6 mb-3 py-3 text-gray-700 hover:bg-gray-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7m0 0l-7 7m7-7V19">
                    </path>
                </svg>
                <span class="font-medium">Back</span>
            </a>
        @elseif (Request::routeIs('logistics.*'))
            <a href="{{ route('products.dashboard') }}"
                class="flex items-baseline space-x-3 px-6 mb-3 py-3 text-gray-700 hover:bg-gray-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7m0 0l-7 7m7-7V19">
                    </path>
                </svg>
                <span class="font-medium">Back</span>
            </a>
        @else
            <a href="{{ route('dashboard') }}"
                class="flex items-baseline space-x-3 px-6 mb-3 py-3 text-gray-700 hover:bg-gray-50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7m0 0l-7 7m7-7V19">
                    </path>
                </svg>
                <span class="font-medium">Back</span>
            </a>
        @endif
    @endif

    @foreach ($sub_modules as $sub_module)
        @if (isset($sub_module['type']) && $sub_module['type'] === 'group')
            @php
                $isGroupActive = collect($sub_module['children'])->contains(
                    fn($c) => str_contains($c['is_active'], 'text-[#0078C8]')
                );
            @endphp
            <div x-data="{ open: {{ $isGroupActive ? 'true' : 'false' }} }">
                {{-- Group header (clickable) --}}
                <button @click="open = !open"
                    class="w-full flex items-center justify-between px-6 py-3 focus:outline-none
                           {{ $isGroupActive ? 'text-[#0078C8]' : 'text-gray-700' }} hover:bg-blue-50">
                    <div class="flex items-center">
                        {!! $sub_module['icon'] !!}
                        <span class="font-medium">{{ $sub_module['name'] }}</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200"
                         :class="{ 'rotate-180': open }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                {{-- Group children --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="border-l-2 border-[#0078C8] ml-5">
                    @foreach ($sub_module['children'] as $child)
                        <a href="{{ $child['url'] }}"
                            class="flex items-center pl-5 pr-4 py-2.5 text-sm {{ $child['is_active'] }}">
                            {!! $child['icon'] !!}
                            <span class="font-medium">{{ $child['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ $sub_module['url'] }}"
                class="flex items-center space-x-3 px-6 py-3 {{ $sub_module['is_active'] }} ">
                {!! $sub_module['icon'] !!}
                <span class="font-medium">{{ $sub_module['name'] }}</span>
            </a>
        @endif
    @endforeach

    @if ($isTechnician)
        {{-- Logout button for technicians --}}
        <div class="border-t border-gray-200 mt-4 pt-4 px-4">
            <form method="POST" action="{{ route('user.logout') }}" class="w-full">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    @endif
</nav>
