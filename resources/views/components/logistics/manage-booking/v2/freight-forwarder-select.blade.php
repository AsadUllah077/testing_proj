@props(['selected' => ''])

@php
    $selectedValue = (string) $selected;
    $defaultLabels = [
        'logitrack' => 'LogiTrack (Self)',
        'agility' => 'Agility Logistics',
        'kn' => 'Kuehne+Nagel',
        'dhl' => 'DHL Global',
    ];
    $selectedLabel = $defaultLabels[$selectedValue] ?? $selectedValue;
@endphp

<div class="flex flex-col gap-1.5">
    <label for="freight_forwarder" class="text-slate-700 text-sm font-semibold leading-5">
        Freight Forwarder / Agent
    </label>

    <select id="freight_forwarder" name="freight_forwarder" class="ajax-freight-agent-select w-full"
        data-placeholder="Search freight forwarder / agent...">
        <option value=""></option>
        @if ($selectedValue !== '')
            <option value="{{ $selectedValue }}" selected>{{ $selectedLabel }}</option>
        @endif
    </select>

    <p class="text-xs text-slate-500">
        Open to browse agents, or type at least 2 characters to search. If there is no exact match, choose “Add new agent”.
    </p>

    @error('freight_forwarder')
        <p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>
    @enderror
</div>

<div id="freightAgentModal" class="hidden fixed inset-0 z-[10020] items-center justify-center p-4" role="dialog"
    aria-modal="true" aria-labelledby="freightAgentModalTitle">
    <button type="button" data-agent-modal-close
        class="absolute inset-0 w-full h-full bg-slate-900/50 backdrop-blur-[1px]" aria-label="Close modal"></button>

    <div class="relative w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 id="freightAgentModalTitle" class="text-lg font-semibold text-slate-900">
                    Add Freight Forwarder / Agent
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Add the agent once, then reuse it on future bookings.
                </p>
            </div>
            <button type="button" data-agent-modal-close
                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 px-6 py-5">
            <div>
                <label for="freightAgentName" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Agent Name <span class="text-red-500">*</span>
                </label>
                <input id="freightAgentName" type="text" maxlength="255" autocomplete="organization"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20"
                    placeholder="e.g. ABC Global Logistics">
                <p data-agent-error="name" class="mt-1 hidden text-xs text-red-600"></p>
            </div>

            <div>
                <label for="freightAgentEmail" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Email <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input id="freightAgentEmail" type="email" maxlength="255" autocomplete="email"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20"
                    placeholder="agent@example.com">
                <p data-agent-error="email" class="mt-1 hidden text-xs text-red-600"></p>
            </div>

            <div>
                <label for="freightAgentContact" class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Contact <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input id="freightAgentContact" type="tel" maxlength="50" autocomplete="tel"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0078C8] focus:outline-none focus:ring-2 focus:ring-[#0078C8]/20"
                    placeholder="+92 300 1234567">
                <p data-agent-error="contact" class="mt-1 hidden text-xs text-red-600"></p>
            </div>

            <p id="freightAgentGeneralError" class="hidden rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" data-agent-modal-close
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Cancel
            </button>
            <button id="saveFreightAgent" type="button"
                class="inline-flex min-w-28 items-center justify-center gap-2 rounded-lg bg-[#0078C8] px-4 py-2 text-sm font-semibold text-white hover:bg-[#006bb0] disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Save Agent</span>
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            $(document).ready(function () {
                const $agentSelect = $('.ajax-freight-agent-select');
                const modal = document.getElementById('freightAgentModal');
                const nameInput = document.getElementById('freightAgentName');
                const emailInput = document.getElementById('freightAgentEmail');
                const contactInput = document.getElementById('freightAgentContact');
                const saveButton = document.getElementById('saveFreightAgent');
                const generalError = document.getElementById('freightAgentGeneralError');
                const searchUrl = @json(route('logistics.manage-booking.v2.freight-agents.search'));
                const storeUrl = @json(route('logistics.manage-booking.v2.freight-agents.store'));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                let previousValue = $agentSelect.val() || '';
                let activeTerm = '';
                let cursors = { 1: null };
                let submitting = false;

                function clearErrors() {
                    document.querySelectorAll('[data-agent-error]').forEach(function (node) {
                        node.textContent = '';
                        node.classList.add('hidden');
                    });
                    generalError.textContent = '';
                    generalError.classList.add('hidden');
                }

                function showErrors(errors) {
                    Object.entries(errors || {}).forEach(function ([field, messages]) {
                        const node = document.querySelector(`[data-agent-error="${field}"]`);
                        if (node) {
                            node.textContent = Array.isArray(messages) ? messages[0] : messages;
                            node.classList.remove('hidden');
                        }
                    });
                }

                function openModal(agentName) {
                    clearErrors();
                    nameInput.value = agentName || '';
                    emailInput.value = '';
                    contactInput.value = '';
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
                    $agentSelect.select2('open');
                }

                function formatAgentResult(agent) {
                    if (agent.loading) return agent.text;

                    const wrapper = $('<div>');
                    if (agent.newAgent) {
                        return wrapper
                            .attr('style', 'color: #000000; font-weight: 600;')
                            .append($('<i class="fa-solid fa-circle-plus mr-2">'))
                            .append($('<span>').text(agent.text));
                    }

                    wrapper.append($('<div class="font-medium text-slate-800">').text(agent.text));
                    const details = [agent.email, agent.contact].filter(Boolean).join(' • ');
                    if (details) {
                        wrapper.append($('<div class="mt-0.5 text-xs text-slate-500">').text(details));
                    }

                    return wrapper;
                }

                $agentSelect.select2({
                    width: '100%',
                    placeholder: $agentSelect.data('placeholder'),
                    allowClear: true,
                    minimumInputLength: 0,
                    templateResult: formatAgentResult,
                    templateSelection: function (agent) {
                        return agent.text || '';
                    },
                    language: {
                        searching: () => 'Searching agents...',
                        noResults: () => 'No matching agent found'
                    },
                    ajax: {
                        url: searchUrl,
                        dataType: 'json',
                        delay: 300,
                        cache: true,
                        data: function (params) {
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
                        processResults: function (data, params) {
                            const page = params.page || 1;
                            const term = (params.term || '').trim();
                            const results = data.results || [];

                            cursors[page + 1] = data.next_cursor || null;

                            if (page === 1 && term && !data.exact_match) {
                                results.unshift({
                                    id: '__create_freight_agent__',
                                    text: `Add “${term}” as a new agent`,
                                    agentName: term,
                                    newAgent: true
                                });
                            }

                            return {
                                results: results,
                                pagination: data.pagination || { more: false }
                            };
                        }
                    }
                });

                $agentSelect.on('select2:select', function (event) {
                    const agent = event.params.data;
                    if (agent.newAgent) {
                        $agentSelect.find('option[value="__create_freight_agent__"]').remove();
                        $agentSelect.val(previousValue || null).trigger('change');
                        openModal(agent.agentName);
                        return;
                    }

                    previousValue = agent.id;
                });

                $agentSelect.on('select2:clear', function () {
                    previousValue = '';
                });

                async function saveAgent() {
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
                            body: JSON.stringify({
                                name: nameInput.value.trim(),
                                email: emailInput.value.trim() || null,
                                contact: contactInput.value.trim() || null
                            })
                        });
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            if (response.status === 422 && data.errors) {
                                showErrors(data.errors);
                            } else {
                                generalError.textContent = data.message || 'Unable to save the agent. Please try again.';
                                generalError.classList.remove('hidden');
                            }
                            return;
                        }

                        const option = new Option(data.agent.name, data.agent.name, true, true);
                        $agentSelect.append(option).trigger('change');
                        previousValue = data.agent.name;
                        activeTerm = '';
                        cursors = { 1: null };

                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        document.body.classList.remove('overflow-hidden');

                        if (window.Notification?.success) {
                            Notification.success(data.message);
                        }
                    } catch (error) {
                        generalError.textContent = 'Unable to connect. Please check your connection and try again.';
                        generalError.classList.remove('hidden');
                    } finally {
                        submitting = false;
                        saveButton.disabled = false;
                        saveButton.querySelector('span').textContent = 'Save Agent';
                        saveButton.querySelector('i').className = 'fa-solid fa-plus text-xs';
                    }
                }

                saveButton.addEventListener('click', saveAgent);
                document.querySelectorAll('[data-agent-modal-close]').forEach(function (button) {
                    button.addEventListener('click', closeModal);
                });
                [nameInput, emailInput, contactInput].forEach(function (input) {
                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            saveAgent();
                        }
                    });
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
@endonce
