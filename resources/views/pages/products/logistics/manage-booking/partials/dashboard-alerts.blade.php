<div
    x-data="{
        pickupDue: [],
        deliveryDue: [],
        inTransit: [],
        loading: true,

        /* confirmation modal state */
        modal: null, /* { booking, actionType } */
        reason: '',
        newDate: '',
        submitting: false,

        async fetchAlerts() {
            try {
                const res  = await fetch('{{ route('logistics.manage-booking.pending-actions') }}');
                const data = await res.json();
                this.pickupDue   = data.pickup_due   || [];
                this.deliveryDue = data.delivery_due || [];
                this.inTransit   = data.in_transit   || [];
            } catch(e) {
                console.error('Alert fetch error', e);
            } finally {
                this.loading = false;
            }
        },

        openModal(booking, actionType) {
            this.modal   = { booking, actionType };
            this.reason  = '';
            this.newDate = '';
        },

        isReject() {
            return this.modal && this.modal.actionType.startsWith('reject');
        },

        closeModal() {
            this.modal = null;
        },

        async submitAction() {
            if (!this.modal || this.submitting) return;
            this.submitting = true;
            const { booking, actionType } = this.modal;

            const urlMap = {
                approve_pickup:   `{{ url('/logistics/manage-booking') }}/${booking.db_id}/approve-pickup`,
                reject_pickup:    `{{ url('/logistics/manage-booking') }}/${booking.db_id}/reject-pickup`,
                approve_delivery: `{{ url('/logistics/manage-booking') }}/${booking.db_id}/approve-delivery`,
                reject_delivery:  `{{ url('/logistics/manage-booking') }}/${booking.db_id}/reject-delivery`,
            };

            try {
                await fetch(urlMap[actionType], {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        reason:   this.reason  || null,
                        new_date: this.newDate || null,
                    })
                });
                this.closeModal();
                await this.fetchAlerts();
            } catch(e) {
                console.error('Action error', e);
            } finally {
                this.submitting = false;
            }
        },

        get hasAlerts() {
            return this.pickupDue.length > 0 || this.deliveryDue.length > 0 || this.inTransit.length > 0;
        }
    }"
    x-init="fetchAlerts()"
>
    {{-- Loading skeleton --}}
    <template x-if="loading">
        <div class="flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-400 text-sm animate-pulse">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Checking for action items…</span>
        </div>
    </template>

    <template x-if="!loading && hasAlerts">
        <div class="space-y-3">

            {{-- ── PICKUP CONFIRMATIONS ── --}}
            <template x-for="booking in pickupDue" :key="'pu-' + booking.db_id">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-amber-50 border border-amber-200 rounded-xl shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-truck-ramp-box text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-amber-700">
                                Pickup Confirmation Required
                            </p>
                            <p class="text-sm font-semibold text-slate-800 mt-0.5">
                                <span x-text="booking.id"></span>
                                &nbsp;·&nbsp;
                                <span x-text="booking.carrier"></span>
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <span x-text="booking.origin"></span>
                                <i class="fa-solid fa-arrow-right mx-1 text-[9px]"></i>
                                <span x-text="booking.destination"></span>
                                &nbsp;·&nbsp; Scheduled <span x-text="booking.scheduled_date"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openModal(booking, 'approve_pickup')"
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition">
                            <i class="fa-solid fa-check mr-1"></i> Approve
                        </button>
                        <button @click="openModal(booking, 'reject_pickup')"
                            class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg transition">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Review
                        </button>
                    </div>
                </div>
            </template>

            {{-- ── DELIVERY CONFIRMATIONS ── --}}
            <template x-for="booking in deliveryDue" :key="'del-' + booking.db_id">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-blue-50 border border-blue-200 rounded-xl shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-box-open text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-blue-700">
                                Delivery Confirmation Required
                            </p>
                            <p class="text-sm font-semibold text-slate-800 mt-0.5">
                                <span x-text="booking.id"></span>
                                &nbsp;·&nbsp;
                                <span x-text="booking.carrier"></span>
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <span x-text="booking.origin"></span>
                                <i class="fa-solid fa-arrow-right mx-1 text-[9px]"></i>
                                <span x-text="booking.destination"></span>
                                &nbsp;·&nbsp; ETA <span x-text="booking.scheduled_date"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openModal(booking, 'approve_delivery')"
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition">
                            <i class="fa-solid fa-check mr-1"></i> Delivered
                        </button>
                        <button @click="openModal(booking, 'reject_delivery')"
                            class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg transition">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Review
                        </button>
                    </div>
                </div>
            </template>

            {{-- ── IN TRANSIT NOTIFICATIONS ── --}}
            <template x-if="inTransit.length > 0">
                <div class="px-5 py-4 bg-violet-50 border border-violet-200 rounded-xl shadow-sm">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center">
                            <i class="fa-solid fa-truck-moving text-sm"></i>
                        </div>
                        <p class="text-xs font-black uppercase tracking-widest text-violet-700">
                            In Transit
                            <span class="ml-1 px-1.5 py-0.5 bg-violet-200 text-violet-700 rounded-full" x-text="inTransit.length"></span>
                        </p>
                    </div>
                    <div class="space-y-2">
                        <template x-for="booking in inTransit" :key="'tr-' + booking.db_id">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
                                <span>
                                    <span class="font-bold text-violet-700" x-text="booking.id"></span>
                                    &nbsp;·&nbsp;
                                    <span x-text="booking.carrier"></span>
                                    &nbsp;—&nbsp;
                                    <span x-text="booking.origin"></span>
                                    <i class="fa-solid fa-arrow-right mx-1 text-[9px]"></i>
                                    <span x-text="booking.destination"></span>
                                </span>
                                <span class="text-violet-600 font-semibold">ETA <span x-text="booking.eta"></span></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>
    </template>

    {{-- ── CONFIRMATION MODAL ── --}}
    <template x-if="modal">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">

                {{-- Header --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                        :class="{
                            'bg-emerald-100 text-emerald-600': !isReject(),
                            'bg-red-100 text-red-600':          isReject()
                        }">
                        <i class="fa-solid"
                            :class="{ 'fa-check': !isReject(), 'fa-calendar-xmark': isReject() }"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black text-slate-900 uppercase tracking-wider"
                            x-text="modal.actionType === 'approve_pickup'   ? 'Confirm Pickup'
                                  : modal.actionType === 'reject_pickup'    ? 'Review & Reschedule Pickup'
                                  : modal.actionType === 'approve_delivery' ? 'Confirm Delivery'
                                  :                                           'Review & Reschedule Delivery'">
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span x-text="modal.booking.id"></span>
                            &nbsp;·&nbsp;
                            <span x-text="modal.booking.scheduled_date || modal.booking.eta"></span>
                        </p>
                    </div>
                </div>

                {{-- Route info --}}
                <div class="bg-slate-50 rounded-xl px-4 py-3 text-xs text-slate-600 flex items-center gap-2">
                    <span x-text="modal.booking.origin"></span>
                    <i class="fa-solid fa-arrow-right text-slate-300"></i>
                    <span x-text="modal.booking.destination"></span>
                    <span class="ml-auto text-slate-400" x-text="modal.booking.carrier"></span>
                </div>

                {{-- APPROVE: show the locked-in date from the booking --}}
                <template x-if="!isReject()">
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 flex items-center gap-3">
                        <i class="fa-solid fa-calendar-check text-emerald-500"></i>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Confirming date</p>
                            <p class="text-sm font-black text-slateald-900"
                                x-text="modal.booking.scheduled_date || modal.booking.eta"></p>
                        </div>
                    </div>
                </template>

                {{-- REJECT: date change + reason --}}
                <template x-if="isReject()">
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Current Date
                            </label>
                            <input type="text" readonly
                                :value="modal.booking.scheduled_date || modal.booking.eta"
                                class="w-full py-2 px-3 text-sm rounded-xl border border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                New Rescheduled Date <span class="text-slate-300 font-normal">(optional)</span>
                            </label>
                            <input type="date" x-model="newDate"
                                class="w-full py-2 px-3 text-sm rounded-xl border border-slate-200 bg-slate-50 focus:ring-[#0078C8]">
                        </div>
                    </div>
                </template>

                {{-- Reason (both modes) --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        Reason <span class="text-slate-300 font-normal">(optional)</span>
                    </label>
                    <textarea x-model="reason" rows="3"
                        class="w-full text-sm px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:ring-[#0078C8] resize-none"
                        :placeholder="isReject() ? 'Why is this being reviewed?' : 'Add a note…'"></textarea>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-1">
                    <button @click="closeModal()"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button @click="submitAction()" :disabled="submitting"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white transition"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': !isReject(),
                            'bg-red-500 hover:bg-red-600':          isReject(),
                            'opacity-60 cursor-not-allowed':        submitting
                        }"
                        x-text="submitting ? 'Saving…' : (isReject() ? 'Review' : 'Confirm')">
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
