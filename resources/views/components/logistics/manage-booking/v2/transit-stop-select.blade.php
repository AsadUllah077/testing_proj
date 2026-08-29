@props(['selected' => ''])

@php
    $selectedValue = (string) $selected;
    $defaultLabels = [
        'peshawar' => 'Peshawar Dry Port',
        'lahore' => 'Lahore Dry Port',
    ];
    $selectedLabel = $defaultLabels[$selectedValue] ?? $selectedValue;
@endphp

<div class="fl">Transit Stop (Dry Port)</div>
<select id="transit_stop" name="transit_stop" class="ajax-transit-stop-select fi w-full">
    <option value=""></option>
    @if ($selectedValue !== '')
        <option value="{{ $selectedValue }}" selected>{{ $selectedLabel }}</option>
    @endif
</select>

<div id="transitStopModal" class="hidden fixed inset-0 z-[10020] items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-labelledby="transitStopModalTitle">
    <button type="button" data-transit-stop-modal-close
        class="absolute inset-0 h-full w-full bg-slate-900/50 backdrop-blur-[1px]"
        aria-label="Close modal"></button>

    <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white text-left shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 id="transitStopModalTitle" class="text-lg font-semibold text-slate-900">Add Transit Stop</h2>
                <p class="mt-1 text-xs text-slate-500">Enter the transit-stop name to reuse it on child bookings.</p>
            </div>
            <button type="button" data-transit-stop-modal-close
                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="px-6 py-5">
            <label for="transitStopName" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Transit Name <span class="text-red-500">*</span>
            </label>
            <input id="transitStopName" type="text" maxlength="255" autocomplete="off"
                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20"
                placeholder="e.g. Karachi Dry Port">
            <p data-transit-stop-error="name" class="mt-1 hidden text-xs text-red-600"></p>
            <p id="transitStopGeneralError"
                class="mt-3 hidden rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" data-transit-stop-modal-close
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Cancel
            </button>
            <button id="saveTransitStop" type="button"
                class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-[#0078C8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Save Stop</span>
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            $(document).ready(function() {
                const $transitStop = $('.ajax-transit-stop-select');
                const modal = document.getElementById('transitStopModal');
                const nameInput = document.getElementById('transitStopName');
                const saveButton = document.getElementById('saveTransitStop');
                const nameError = document.querySelector('[data-transit-stop-error="name"]');
                const generalError = document.getElementById('transitStopGeneralError');
                const searchUrl = @json(route('logistics.manage-booking.v2.transit-stops.search'));
                const storeUrl = @json(route('logistics.manage-booking.v2.transit-stops.store'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                let previousValue = $transitStop.val() || '';
                let activeTerm = '';
                let cursors = { 1: null };
                let submitting = false;

                function clearErrors() {
                    nameError.textContent = '';
                    nameError.classList.add('hidden');
                    generalError.textContent = '';
                    generalError.classList.add('hidden');
                }

                function openModal(name) {
                    clearErrors();
                    nameInput.value = name || '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    window.setTimeout(() => nameInput.focus(), 50);
                }

                function closeModal() {
                    if (submitting) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                    $transitStop.next('.select2-container').find('.select2-selection').trigger('focus');
                }

                function formatResult(stop) {
                    if (stop.loading) return stop.text;
                    if (!stop.newTransitStop) return stop.text;

                    return $('<div>')
                        .attr('style', 'color: #000; font-weight: 600;')
                        .append($('<i class="fa-solid fa-circle-plus mr-2">'))
                        .append($('<span>').text(stop.text));
                }

                $transitStop.select2({
                    width: '100%',
                    placeholder: 'No Stop',
                    allowClear: true,
                    minimumInputLength: 0,
                    templateResult: formatResult,
                    ajax: {
                        url: searchUrl,
                        dataType: 'json',
                        delay: 300,
                        cache: true,
                        data: function(params) {
                            const term = (params.term || '').trim();
                            const page = params.page || 1;

                            if (term !== activeTerm) {
                                activeTerm = term;
                                cursors = { 1: null };
                            }

                            return {
                                q: term,
                                cursor: cursors[page] || ''
                            };
                        },
                        processResults: function(data, params) {
                            const page = params.page || 1;
                            const term = (params.term || '').trim();
                            const results = data.results || [];

                            cursors[page + 1] = data.next_cursor || null;

                            if (page === 1 && term && !data.exact_match) {
                                results.unshift({
                                    id: '__create_transit_stop__',
                                    text: `Add “${term}” as a new transit stop`,
                                    transitStopName: term,
                                    newTransitStop: true
                                });
                            }

                            return {
                                results: results,
                                pagination: data.pagination || { more: false }
                            };
                        }
                    }
                });

                $transitStop.on('select2:select', function(event) {
                    const stop = event.params.data;
                    if (stop.newTransitStop) {
                        $transitStop.find('option[value="__create_transit_stop__"]').remove();
                        $transitStop.val(previousValue || null).trigger('change');
                        openModal(stop.transitStopName);
                        return;
                    }

                    previousValue = stop.id;
                });

                $transitStop.on('select2:clear', function() {
                    previousValue = '';
                });

                async function saveTransitStop() {
                    if (submitting) return;

                    clearErrors();
                    submitting = true;
                    saveButton.disabled = true;
                    saveButton.querySelector('span').textContent = 'Saving...';
                    saveButton.querySelector('i').className = 'fa-solid fa-spinner fa-spin text-xs';

                    try {
                        const response = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ name: nameInput.value.trim() })
                        });
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            if (response.status === 422 && data.errors?.name) {
                                nameError.textContent = data.errors.name[0];
                                nameError.classList.remove('hidden');
                            } else {
                                generalError.textContent = data.message || 'Unable to save the transit stop.';
                                generalError.classList.remove('hidden');
                            }
                            return;
                        }

                        const option = new Option(
                            data.transit_stop.name,
                            data.transit_stop.name,
                            true,
                            true
                        );
                        $transitStop.append(option).trigger('change');
                        previousValue = data.transit_stop.name;
                        activeTerm = '';
                        cursors = { 1: null };
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        document.body.classList.remove('overflow-hidden');

                        if (window.Notification?.success) {
                            Notification.success(data.message);
                        }
                    } catch (error) {
                        generalError.textContent = 'Unable to connect. Please try again.';
                        generalError.classList.remove('hidden');
                    } finally {
                        submitting = false;
                        saveButton.disabled = false;
                        saveButton.querySelector('span').textContent = 'Save Stop';
                        saveButton.querySelector('i').className = 'fa-solid fa-plus text-xs';
                    }
                }

                saveButton.addEventListener('click', saveTransitStop);
                nameInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        saveTransitStop();
                    }
                });
                document.querySelectorAll('[data-transit-stop-modal-close]').forEach(function(button) {
                    button.addEventListener('click', closeModal);
                });
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
@endonce
