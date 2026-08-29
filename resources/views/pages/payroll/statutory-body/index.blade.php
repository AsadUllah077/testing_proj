@extends('layouts.auth')

@section('title', 'Statutory Body Setup')

@section('content')
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto px-6 py-6">
                <div class="flex justify-between items-center">

                    <!-- Left: Title -->
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                            Statutory Body Setup
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Configure regulatory authorities and penalty rules for automated compliance.
                        </p>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <button onclick="openModal('create')"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm font-semibold hover:bg-[#005ea6] transition shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            ADD NEW AUTHORITY
                        </button>

                        <a href="{{ route('payroll.compliance.dashboard') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                            ← Back
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <div class="mx-auto px-6 mt-8">
            {{-- Filters --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                <form method="GET" class="flex flex-wrap justify-end gap-4 items-end w-full">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Search Authority
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="e.g. FBR"
                            class="border-gray-300 rounded text-sm p-2 border w-64 focus:ring-[#0078C8] focus:border-[#0078C8]">
                    </div>

                    <div>
                        <x-default-page />
                    </div>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded text-sm font-bold hover:bg-[#005ea6] transition shadow-sm">
                            Filter
                        </button>

                        @if (request()->filled('search') || request('per_page', 20) != 20)
                            <a href="{{ url()->current() }}"
                                class="px-4 py-2 bg-red-600 text-white rounded text-sm font-bold hover:bg-red-700 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-md">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-gray-50/50 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                            <th class="px-6 py-4">Authority Code</th>
                            <th class="px-6 py-4">Full Name</th>
                            <th class="px-6 py-4 text-center">Country</th>
                            <th class="px-6 py-4 text-center">Grace Days</th>
                            <th class="px-6 py-4 text-center">Penalty Rate</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($bodies as $body)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-5">
                                    <span
                                        class="px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black rounded-lg uppercase tracking-widest border border-blue-100">{{ $body->code }}</span>
                                </td>
                                <td class="px-6 py-5 font-bold text-gray-800 text-sm">{{ $body->name }}</td>
                                <td class="px-6 py-5 text-center text-xs text-gray-500 font-bold uppercase tracking-widest">
                                    {{ $body->country_code }}</td>
                                <td class="px-6 py-5 text-center text-xs font-black text-gray-900">
                                    {{ $body->default_grace_days }} Days</td>
                                <td class="px-6 py-5 text-center text-xs font-black text-red-600">
                                    {{ $body->penalty_rate_per_day }}% / day</td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="editBody({{ json_encode($body) }})"
                                            class="p-2 text-gray-400 hover:text-blue-600 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                                            </svg>
                                        </button>
                                        <button onclick="deleteBody({{ $body->id }})"
                                            class="p-2 text-gray-400 hover:text-red-600 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-6 border-t border-gray-50">
                    {{ $bodies->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- CRUD Modal --}}
    <div id="modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 items-center justify-center p-4">
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
            <div class="p-8">
                <h3 id="modalTitle" class="text-xl font-black text-gray-900 mb-6 uppercase tracking-tight">Create Authority
                </h3>
                <form id="bodyForm" class="space-y-6">
                    @csrf
                    <input type="hidden" id="bodyId" name="bodyId">

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Code</label>
                            <input type="text" name="code" id="code" required placeholder="e.g. FBR"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Country
                                Code</label>
                            <input type="text" name="country_code" id="country_code" required placeholder="PAK"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Full
                            Authority Name</label>
                        <input type="text" name="name" id="name" required placeholder="Federal Board of Revenue"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Grace
                                Days</label>
                            <input type="number" name="default_grace_days" id="default_grace_days" required value="15"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Penalty
                                % / Day</label>
                            <input type="number" name="penalty_rate_per_day" id="penalty_rate_per_day" step="0.001"
                                required value="0.01"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="closeModal()"
                            class="flex-1 px-4 py-4 border border-gray-100 text-gray-400 text-xs font-black rounded-2xl hover:bg-gray-50 transition uppercase tracking-widest">Cancel</button>
                        <button type="submit"
                            class="flex-1 px-4 py-4 bg-[#0078C8] text-white text-xs font-black rounded-2xl hover:bg-[#005ea6] transition shadow-lg shadow-blue-100 uppercase tracking-widest">Save
                            Authority</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        const modal = document.getElementById('modal');
        const form = document.getElementById('bodyForm');

        function openModal(mode, data = null) {
            document.getElementById('modalTitle').innerText = mode === 'create' ? 'Create Authority' : 'Edit Authority';
            document.getElementById('bodyId').value = data ? data.id : '';
            document.getElementById('code').value = data ? data.code : '';
            document.getElementById('name').value = data ? data.name : '';
            document.getElementById('country_code').value = data ? data.country_code : 'PAK';
            document.getElementById('default_grace_days').value = data ? data.default_grace_days : '15';
            document.getElementById('penalty_rate_per_day').value = data ? data.penalty_rate_per_day : '0.01';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function editBody(data) {
            openModal('edit', data);
        }

        function deleteBody(id) {
            if (!confirm('Are you sure you want to delete this authority? This may affect compliance tracking.')) return;

            $.ajax({
                url: `/payroll/statutory-bodies/${id}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('Failed to delete authority');
                }
            });
        }

        form.onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('bodyId').value;
            const url = id ? `/payroll/statutory-bodies/${id}` : '/payroll/statutory-bodies';
            const method = id ? 'PUT' : 'POST';

            const formData = new FormData(form);
            const jsonData = {};
            formData.forEach((value, key) => jsonData[key] = value);

            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(jsonData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        toastr.error(Array.isArray(data.message) ? data.message.join('\n') : data.message);
                    }
                },
                error: function(xhr) {
                    const data = xhr.responseJSON;
                    const msg = data && data.message ? (Array.isArray(data.message) ? data.message.join(
                        '\n') : data.message) : 'Failed to save authority';
                    toastr.error(msg);
                }
            });
        }
    </script>
@endpush
