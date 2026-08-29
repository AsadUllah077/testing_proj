@extends('layouts.auth')

@section('title', 'Salary Benchmarks')

@section('content')
    <div class="p-6 min-h-screen bg-[#f8fafc] pb-12">
        <div class="bg-white border-b border-gray-200">
            <div class="mx-auto px-6 py-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Salary Benchmarks</h1>
                        <p class="text-gray-600">Maintain market salary standards for various designations.</p>
                    </div>
                    <!-- Right: Actions -->
                    <div class="flex items-center gap-3">
                        <button onclick="openModal()"
                            class="px-4 py-2 bg-[#0078C8] text-white rounded-lg text-sm font-semibold hover:bg-[#005ea6] transition shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add New Benchmark
                        </button>

                        <a href="{{ route('payroll.dashboard') }}"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition shadow-sm">
                            ← Back
                        </a>
                    </div>

                </div>
            </div>
        </div>

        {{-- Filters --}}
        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-6">

            <form method="GET" class="flex flex-wrap justify-end gap-4 items-end w-full">

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Search Designation
                    </label>

                    <input type="text" name="designation" value="{{ request('designation') }}"
                        placeholder="e.g. Software Engineer"
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

                    @if (request()->filled('designation') || request('per_page', 20) != 20)
                        <a href="{{ route('payroll.salary-benchmarks.index') }}"
                            class="px-4 py-2 bg-red-600 text-white rounded text-sm font-bold hover:bg-red-700 transition">
                            Reset
                        </a>
                    @endif

                </div>

            </form>

        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Designation</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Min Salary
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Median
                            Salary</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Max Salary
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($benchmarks as $benchmark)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $benchmark->designation }}</td>
                            <td class="px-6 py-4 text-right text-gray-600">{{ number_format($benchmark->min_salary, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-indigo-600 font-bold">
                                {{ number_format($benchmark->median_salary, 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-600">{{ number_format($benchmark->max_salary, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick="editBenchmark({{ json_encode($benchmark) }})"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button onclick="deleteBenchmark({{ $benchmark->id }})"
                                    class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                No benchmarks found. Start by adding one!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <x-data-table-paginatior :paginator="$benchmarks" />
        </div>
    </div>

    <!-- Modal -->
    <div id="benchmarkModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 items-center justify-center p-4">
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
            <div class="p-8">
                <h2 id="modalTitle" class="text-xl font-black text-gray-900 mb-6 uppercase tracking-tight">Add Benchmark
                </h2>
                <form id="benchmarkForm" class="space-y-6">
                    @csrf
                    <input type="hidden" id="benchmarkId">
                    <div class="space-y-4">
                        <div>
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Designation</label>
                            <input list="designationList" name="designation" id="designation" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold"
                                placeholder="Select or type designation">

                            <datalist id="designationList">
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Min</label>
                                <input type="number" name="min_salary" id="min_salary" required step="0.01"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Median</label>
                                <input type="number" name="median_salary" id="median_salary" required step="0.01"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Max</label>
                                <input type="number" name="max_salary" id="max_salary" required step="0.01"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="closeModal()"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0078C8] text-white rounded-lg hover:bg-[#005ea6]">Save
                            Benchmark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openModal() {
                document.getElementById('benchmarkForm').reset();
                document.getElementById('benchmarkId').value = '';
                document.getElementById('modalTitle').innerText = 'Add Benchmark';
                document.getElementById('benchmarkModal').classList.remove('hidden');
                document.getElementById('benchmarkModal').classList.add('flex');
            }

            function closeModal() {
                document.getElementById('benchmarkModal').classList.add('hidden');
                document.getElementById('benchmarkModal').classList.remove('flex');
            }

            function editBenchmark(benchmark) {
                openModal();
                document.getElementById('modalTitle').innerText = 'Edit Benchmark';
                document.getElementById('benchmarkId').value = benchmark.id;
                document.getElementById('designation').value = benchmark.designation;
                document.getElementById('min_salary').value = benchmark.min_salary;
                document.getElementById('median_salary').value = benchmark.median_salary;
                document.getElementById('max_salary').value = benchmark.max_salary;
            }

            document.getElementById('benchmarkForm').onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData.entries());

                try {
                    const res = await fetch('{{ route('payroll.salary-benchmarks.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    if (result.success) {
                        location.reload();
                    }
                } catch (err) {
                    alert('Error saving benchmark');
                }
            };

            async function deleteBenchmark(id) {
                if (!confirm('Are you sure you want to delete this benchmark?')) return;

                try {
                    const res = await fetch(`{{ url('payroll/salary-benchmarks') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const result = await res.json();
                    if (result.success) {
                        location.reload();
                    }
                } catch (err) {
                    alert('Error deleting benchmark');
                }
            }
        </script>
    @endpush
@endsection
