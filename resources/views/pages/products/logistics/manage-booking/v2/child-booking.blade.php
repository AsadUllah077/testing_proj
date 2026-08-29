@extends('layouts.auth')

@section('title', 'Child Bookings')

@section('content')
    <div class="mt-4">
        @if (session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 mx-3" role="alert">
                <span class="font-medium">Success!</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 mx-3" role="alert">
                <span class="font-medium">Error!</span> {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 px-3">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.manage-booking') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>

        <div class="mx-auto px-3">
            <div id="actionAlertsWrapper" class="mb-4 space-y-3"></div>

            <div class="bg-white rounded-xl border border-gray-200 mb-6">
                <div class="px-6 py-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-lg font-semibold text-gray-900">Child Bookings</h1>
                            <span
                                class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-md">{{ $childBookings->count() }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Parent Booking:
                            <span class="font-semibold text-gray-700">{{ $booking->booking_number ?? $booking->id }}</span>
                            <span class="mx-1 text-gray-300">|</span>
                            {{ $booking->freight_mode ?? 'N/A' }}
                            <span class="mx-1 text-gray-300">|</span>
                            {{ $booking->origin_port ?? 'N/A' }} to {{ $booking->dest_port ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('logistics.manage-booking.v2.create', ['is_child' => 1, 'parent_id' => $booking->id]) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-white bg-[#0078C8] rounded-lg hover:bg-[#006bb0]">
                            <i class="fa-solid fa-plus text-[10px]"></i> New Child Booking
                        </a>
                        <a href="{{ route('logistics.manage-booking.v2.show', $booking->id) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                            <i class="fa-solid fa-eye text-[10px]"></i> View Parent
                        </a>
                        <x-common.back-button url="{{ route('logistics.manage-booking.v2.index') }}" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-sm font-semibold text-gray-800">Related Child Booking Details</h2>
                    <p class="text-xs text-gray-500 mt-1">Stage-wise inland child bookings for this parent booking.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Booking No</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Mode</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Carrier</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Vehicle</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Driver</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">ETD</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">ETA</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Delivery Date</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Return Deadline</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase text-gray-500">Destination</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($childBookings as $childBookingNo => $child)
                                @php
                                    $childStatus = $child['booking_identity']['status'] ?? 'Booked';
                                    $statusClasses = match ($childStatus) {
                                        'Delivered' => 'bg-green-50 text-green-700 border-green-200',
                                        'In-Transit' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Pickup' => 'bg-orange-50 text-orange-700 border-orange-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $childBookingNo }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-tight {{ $statusClasses }}">
                                            {{ $childStatus }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $child['booking_identity']['freight_mode'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $child['inland_carrier']['carrier'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $child['vehicle_driver_assignment']['vehicle_reg'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $child['vehicle_driver_assignment']['driver_name'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ !empty($child['inland_carrier']['etd']) ? \Carbon\Carbon::parse($child['inland_carrier']['etd'])->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ !empty($child['inland_carrier']['eta']) ? \Carbon\Carbon::parse($child['inland_carrier']['eta'])->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ !empty($child['inland_carrier']['delivery_date']) ? \Carbon\Carbon::parse($child['inland_carrier']['delivery_date'])->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {{ !empty($child['inland_carrier']['return_deadline']) ? \Carbon\Carbon::parse($child['inland_carrier']['return_deadline'])->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $child['final_delivery_details']['delivery_address'] ?? ($child['inland_carrier']['final_destination'] ?? 'N/A') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('logistics.manage-booking.v2.child-booking.edit', [$booking->id, $childBookingNo]) }}"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#0078C8] border border-[#0078C8]/20 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                            <i class="fa-solid fa-pen text-[10px]"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-5 py-12 text-center text-sm text-gray-400">
                                        <i class="fa-solid fa-box-open text-2xl mb-2 block text-gray-300"></i>
                                        No child bookings found for this parent booking.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const childConfirmReasons = @json($confirmReasons->pluck('reason_text')->values());
        const childRescheduleReasons = @json($rescheduleReasons->pluck('reason_text')->values());

        document.addEventListener('DOMContentLoaded', function() {
            loadChildPendingAlerts();
        });

        function buildReasonOptions(reasons) {
            return ['<option value="">Select a reason...</option>']
                .concat(reasons.map(reason => `<option value="${reason}">${reason}</option>`))
                .concat('<option value="Other">Other</option>')
                .join('');
        }

        function loadChildPendingAlerts() {
            fetch(`{{ route('logistics.manage-booking.v2.pending-actions') }}?parent_id={{ $booking->id }}`)
                .then(r => r.json())
                .then(data => {
                    const childArrivals = data.child_arrival_due || [];
                    const childDeliveries = data.child_delivery_due || [];
                    const totalPending = childArrivals.length + childDeliveries.length;
                    const wrapper = document.getElementById('actionAlertsWrapper');
                    wrapper.innerHTML = '';

                    if (!totalPending) {
                        return;
                    }

                    wrapper.insertAdjacentHTML('beforeend', `
                        <div class="flex items-center justify-between mb-3 px-1">
                            <span class="text-sm font-semibold text-gray-700">Pending Child Actions (<span class="font-bold">${totalPending}</span>)</span>
                        </div>
                    `);

                    childArrivals.forEach(child => {
                        wrapper.insertAdjacentHTML('beforeend', `
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-sky-50 border border-sky-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-location-dot text-sky-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-sky-800">Child Arrival Due - <span class="font-bold">${child.child_booking_number}</span></p>
                                        <p class="text-xs text-sky-600 mt-0.5">${child.origin_port} -> ${child.destination_port} | Status: ${child.status}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button onclick="openChildActionModal(${child.parent_id}, '${child.child_booking_number}', 'child_arrival', 'approve')"
                                        class="px-3 py-1.5 text-xs font-semibold text-white bg-sky-500 hover:bg-sky-600 rounded-lg transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[10px]"></i> Confirm Arrival
                                    </button>
                                    <button onclick="openChildActionModal(${child.parent_id}, '${child.child_booking_number}', 'child_arrival', 'reject')"
                                        class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                                    </button>
                                </div>
                            </div>
                        `);
                    });

                    childDeliveries.forEach(child => {
                        wrapper.insertAdjacentHTML('beforeend', `
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5 bg-fuchsia-50 border border-fuchsia-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-fuchsia-100 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-box-open text-fuchsia-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-fuchsia-800">Child Delivery Due - <span class="font-bold">${child.child_booking_number}</span></p>
                                        <p class="text-xs text-fuchsia-600 mt-0.5">${child.origin_port} -> ${child.destination_port} | Status: ${child.status}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button onclick="openChildActionModal(${child.parent_id}, '${child.child_booking_number}', 'child_delivery', 'approve')"
                                        class="px-3 py-1.5 text-xs font-semibold text-white bg-fuchsia-500 hover:bg-fuchsia-600 rounded-lg transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[10px]"></i> Confirm Delivered
                                    </button>
                                    <button onclick="openChildActionModal(${child.parent_id}, '${child.child_booking_number}', 'child_delivery', 'reject')"
                                        class="px-3 py-1.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1.5">
                                        <i class="fa-solid fa-xmark text-[10px]"></i> Reschedule
                                    </button>
                                </div>
                            </div>
                        `);
                    });
                })
                .catch(err => console.error('Child pending actions error:', err));
        }

        function openChildActionModal(parentId, childBookingNumber, type, mode) {
            const isApprove = mode === 'approve';
            const isArrival = type === 'child_arrival';
            const reasons = isApprove ? childConfirmReasons : childRescheduleReasons;

            Swal.fire({
                title: isApprove ?
                    (isArrival ? 'Confirm Child Arrival' : 'Confirm Child Delivery') :
                    (isArrival ? 'Reschedule Child Arrival' : 'Reschedule Child Delivery'),
                html: `
                    <div class="text-left">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Reason</label>
                        <select id="child_reason_select" class="swal2-input !mt-0 !w-full !text-sm">${buildReasonOptions(reasons)}</select>
                        <textarea id="child_reason_other" class="swal2-textarea !w-full !text-sm hidden" placeholder="Write custom reason..."></textarea>
                        ${!isApprove ? `
                                <label class="block text-xs font-medium text-gray-600 mt-3 mb-2">${isArrival ? 'New Arrival Date' : 'New Delivery Date'}</label>
                                <input type="date" id="child_new_date" class="swal2-input !mt-0 !w-full !text-sm" min="${new Date().toISOString().split('T')[0]}">
                            ` : ''}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: isApprove ? 'Confirm' : 'Reschedule',
                confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
                didOpen: () => {
                    const select = document.getElementById('child_reason_select');
                    const other = document.getElementById('child_reason_other');
                    select.addEventListener('change', function() {
                        other.classList.toggle('hidden', this.value !== 'Other');
                    });
                },
                preConfirm: () => {
                    let reason = document.getElementById('child_reason_select').value;
                    if (reason === 'Other') {
                        reason = document.getElementById('child_reason_other').value.trim();
                    }

                    if (!reason) {
                        Swal.showValidationMessage('Reason is required.');
                        return false;
                    }

                    const payload = {
                        reason,
                        child_booking_number: childBookingNumber
                    };

                    if (!isApprove) {
                        const newDate = document.getElementById('child_new_date').value;
                        if (!newDate) {
                            Swal.showValidationMessage('Please select a new date.');
                            return false;
                        }

                        if (isArrival) {
                            payload.new_arrival_date = newDate;
                        } else {
                            payload.new_delivery_date = newDate;
                        }
                    }

                    return payload;
                }
            }).then(result => {
                if (!result.isConfirmed) {
                    return;
                }

                const url = isApprove ?
                    (isArrival ?
                        `/logistics/manage-booking/v2/${parentId}/child-actions/approve-arrival` :
                        `/logistics/manage-booking/v2/${parentId}/child-actions/approve-delivery`) :
                    (isArrival ?
                        `/logistics/manage-booking/v2/${parentId}/child-actions/reject-arrival` :
                        `/logistics/manage-booking/v2/${parentId}/child-actions/reject-delivery`);

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(result.value),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Action failed.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Communication error with server', 'error');
                    });
            });
        }
    </script>
@endpush
