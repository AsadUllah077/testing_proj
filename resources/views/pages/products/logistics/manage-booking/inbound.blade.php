@extends('layouts.auth')

@section('title', 'Logistics Inbound')

@section('content')
    <x-acccount.loader />
    <div class="mt-4" x-data="{
        activeMode: 'Air',
        shipments: [],
        loading: false,
        async fetchData() {
            if (this.loading) return;
            this.loading = true;
            try {
                const response = await fetch(`{{ route('logistics.manage-booking.data') }}?mode=${this.activeMode}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                this.shipments = Array.isArray(data) ? data : [];
            } catch (error) {
                console.error('Error fetching shipments:', error);
                this.shipments = [];
            } finally {
                this.loading = false;
            }
        },
        async deleteShipment(id) {
            if (!confirm('Are you sure you want to delete this shipment?')) return;
            try {
                const response = await fetch(`{{ url('/logistics/manage-booking/destroy') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                if (response.ok) {
                    this.fetchData();
                }
            } catch (error) {
                console.error('Error deleting shipment:', error);
            }
        },
        init() {
            this.fetchData();
            this.$watch('activeMode', () => this.fetchData());
        }
    }">
        <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.logistics') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['route'] . '*') }}" />
                @endforeach
            </div>
        </div>
        <div class="mx-auto px-3">

            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center">
                    <h2 class="text-stone-950 text-xl font-normal md:mb-0 mb-2">Inbound Booking</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-2 relative">
                        <div></div>
                        <div></div>
                        <!-- Export Dropdown -->
                        <x-common.export-dropdown name="booking" />
                        {{-- hidden input so startExport picks up the active tab mode via statusFilter --}}
                        <input type="hidden" id="statusFilter" :value="activeMode">

                        <!-- Add New Button -->
                        <a href="{{ route('logistics.manage-booking.create') }}"
                            class="px-4 text-center py-2 bg-[#0078C8] text-white text-xs font-normal rounded hover:bg-[#006bb0] flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create New Booking
                        </a>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="mt-6 flex items-center gap-2 border-b border-gray-100 px-2">
                    <button @click="activeMode = 'Air'"
                        :class="activeMode === 'Air' ? 'border-[#0078C8] text-[#0078C8]' :
                            'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-8 py-3 border-b-2 font-bold text-xs uppercase tracking-widest transition-all duration-200 flex items-center gap-2">
                        <i class="fa-solid fa-plane-up text-sm"></i>
                        Air
                    </button>
                    <button @click="activeMode = 'Ocean'"
                        :class="activeMode === 'Ocean' ? 'border-[#0078C8] text-[#0078C8]' :
                            'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-8 py-3 border-b-2 font-bold text-xs uppercase tracking-widest transition-all duration-200 flex items-center gap-2">
                        <i class="fa-solid fa-anchor text-sm"></i>
                        Ocean
                    </button>
                    <button @click="activeMode = 'Inland'"
                        :class="activeMode === 'Inland' ? 'border-[#0078C8] text-[#0078C8]' :
                            'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-8 py-3 border-b-2 font-bold text-xs uppercase tracking-widest transition-all duration-200 flex items-center gap-2">
                        <i class="fa-solid fa-truck text-sm"></i>
                        Inland
                    </button>
                </div>

                <!-- Tab Panes -->
                <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div x-show="activeMode === 'Air' || activeMode === 'Ocean' || activeMode === 'Inland'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0">
                        @include('pages.products.logistics.manage-booking.partials.shipment-table', [
                            'dynamic' => true,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
