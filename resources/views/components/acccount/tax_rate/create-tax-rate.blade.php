<div id="taxRateModal"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 transition-opacity duration-300">

    <div id="taxRateBox"
        class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 transform scale-95 transition-all duration-300">

        <div
            class="border-b border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 px-6 py-4 bg-gradient-to-r from-[#0078C8] to-[#006bb0] rounded-t-lg">
            <h2 id="taxRateTitle" class="text-lg font-semibold text-white">Create Tax Rate</h2>
        </div>

        <form id="taxForm" class="px-6 py-5 space-y-4">
            @csrf
            <input type="hidden" id="tax_id">
            <input type="hidden" id="company_id" value="{{ auth()->user()->company_id ?? 1 }}">

            <div>
                <label class="text-sm font-medium">Name <span class="text-red-500">*</span></label>
                <input type="text" id="name"
                    class="w-full  px-4 border border-gray-300 outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 py-2 rounded mt-1">
                <small id="nameError" class="text-red-500 hidden"></small>
            </div>

            <div>
                <label class="text-sm font-medium">Code <span class="text-red-500">*</span></label>
                <input type="text" id="code"
                    class="w-full  px-4 border border-gray-300 outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 py-2 rounded mt-1">
                <small id="codeError" class="text-red-500 hidden"></small>
            </div>

            <div>
                <label class="text-sm font-medium">Tax Rate (%) <span class="text-red-500">*</span></label>
                <input type="number" id="tax_rate"
                    class="w-full  border border-gray-300 outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 px-4 py-2 rounded mt-1">
                <small id="rateError" class="text-red-500 hidden"></small>
            </div>

            <div>
                <label class="text-sm font-medium">Tax Type <span class="text-red-500">*</span></label>
                <select id="tax_type"
                    class="w-full border border-gray-300 outline-none focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 px-4 py-2 rounded mt-1">
                    <option value="">-- Select Tax Type --</option>
                    @foreach (config('default.default_tax') as $key => $tax_type)
                        <option value="{{ $key }}">{{ $tax_type }}</option>
                    @endforeach
                </select>
                <small id="typeError" class="text-red-500 hidden"></small>
            </div>

        </form>

        <div
            class="border-t px-6 py-3 flex justify-end gap-3 border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20">
            <button onclick="closeTaxRateModal()"
                class="px-5 py-2 border border-gray-300 focus:border-[#0078C8] focus:ring-2 focus:ring-[#0078C8]/20 rounded">Cancel</button>
            <button onclick="submitTaxRateForm()" class="px-5 py-2 bg-[#0078C8] text-white rounded hover:bg-[#006bb0]">
                <span id="submitBtnText">Add Tax Rate</span>
            </button>
        </div>

    </div>
</div>


@push('scripts')
    <script>
        let taxModalMode = 'create';

        function openTaxRateModal(mode = 'create', data = {}) {
            taxModalMode = mode;

            document.getElementById('taxForm').reset();
            clearErrors();

            const modal = document.getElementById('taxRateModal');
            const modalBox = document.getElementById('taxRateBox');
            const title = document.getElementById('taxRateTitle');
            const btnText = document.getElementById('submitBtnText');

            if (mode === 'edit') {
                title.textContent = 'Edit Tax Rate';
                btnText.textContent = 'Update Tax Rate';

                document.getElementById('tax_id').value = data.id;
                document.getElementById('name').value = data.name;
                document.getElementById('code').value = data.code;
                document.getElementById('tax_rate').value = data.tax_rate;
                document.getElementById('tax_type').value = data.tax_type;
            } else {
                title.textContent = 'Create Tax Rate';
                btnText.textContent = 'Add Tax Rate';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => modalBox.classList.add('scale-100'), 10);
        }

        function closeTaxRateModal() {
            const modal = document.getElementById('taxRateModal');
            const modalBox = document.getElementById('taxRateBox');

            modalBox.classList.remove('scale-100');

            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(e => {
                e.classList.add('hidden');
                e.textContent = '';
            });
        }

        const COMPANY_ID = {{ auth()->user()->company_id ?? 1 }};

        async function submitTaxRateForm() {
            clearErrors();

            let id = document.getElementById('tax_id').value;

            let payload = {
                company_id: COMPANY_ID ?? document.getElementById('company_id')?.value,
                name: document.getElementById('name').value,
                code: document.getElementById('code').value,
                tax_rate: document.getElementById('tax_rate').value,
                tax_type: document.getElementById('tax_type').value,
            };

            let url = taxModalMode === 'create' ?
                "{{ route('account.organization-settings.tax_rate.store') }}" :
                `/settings/tax-rate/update/${id}`;

            let method = taxModalMode === 'create' ? 'POST' : 'PUT';

            const res = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (res.status === 422 || data.success === false) {
                const errors = data.errors || data.message || {};

                if (Array.isArray(errors)) {
                    alert(errors.join('\n'));
                    return;
                }

                Object.entries(errors).forEach(([field, messages]) => {
                    const errorFieldMap = {
                        name: 'nameError',
                        code: 'codeError',
                        tax_rate: 'rateError',
                        tax_type: 'typeError',
                    };
                    const errorField = errorFieldMap[field] || `${field}Error`;
                    const target = document.getElementById(errorField);

                    if (target) {
                        target.textContent = Array.isArray(messages) ? messages[0] : messages;
                        target.classList.remove('hidden');
                    }
                });
                return;
            }

            alert(data.message);
            closeTaxRateModal();

            if (typeof window.handleTaxRateSaved === 'function') {
                const shouldReload = window.handleTaxRateSaved(data.tax_rate, data);
                if (shouldReload === false) {
                    return;
                }
            }

            location.reload();
        }
    </script>
@endpush
