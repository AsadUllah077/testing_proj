@extends('layouts.auth')

@section('title', 'Employee Tenure Milestones')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="max-w-12xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Employee Tenure Milestones</h2>
                <p class="text-sm text-gray-600 mt-1">Acknowledging employee work anniversaries and milestones.</p>
            </div>
            <a href="{{ route('hrm.index') }}" class="p-2 bg-white border border-gray-200 rounded-lg hover:border-primary transition-colors flex items-center gap-2 text-sm font-bold ">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                Back to Dashboard
            </a>
        </div>

        {{-- Main Content Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            {{-- Filters Section --}}
            <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                <form method="GET" id="filter-form" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">From Date</label>
                        <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">To Date</label>
                        <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                            class="date w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Department</label>
                        <select name="department_id" id="department_id"
                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                    {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->department }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Milestone Type</label>
                        <select name="milestone" id="milestone"
                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Milestones</option>
                            <option value="6_months"  {{ request('milestone') === '6_months'  ? 'selected' : '' }}>6 Months</option>
                            <option value="1_year"    {{ request('milestone') === '1_year'    ? 'selected' : '' }}>1 Year</option>
                            <option value="2_years"   {{ request('milestone') === '2_years'   ? 'selected' : '' }}>2 Years</option>
                            <option value="3_years"   {{ request('milestone') === '3_years'   ? 'selected' : '' }}>3 Years</option>
                            <option value="5_years"   {{ request('milestone') === '5_years'   ? 'selected' : '' }}>5 Years</option>
                            <option value="10_years"  {{ request('milestone') === '10_years'  ? 'selected' : '' }}>10 Years</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                        <select name="status" id="status"
                            class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                            <option value="">All Status</option>
                            <option value="pending"      {{ request('status') === 'pending'      ? 'selected' : '' }}>Pending</option>
                            <option value="acknowledged" {{ request('status') === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Search</label>
                        <input type="text" name="q" placeholder="Search by employee name..."
                            value="{{ request('q') }}"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <x-default-page />
                    </div>
                    <x-filter-button />
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Milestone</th>

                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($milestones as $milestone)
                        <tr class="{{ $milestone->is_read ? 'opacity-75' : 'bg-primary/5' }} hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600">
                                        {{ substr($milestone->employee->full_name, 0, 1) }}
                                    </div>
                                    <span class="font-bold text-gray-900">{{ $milestone->employee->full_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $milestone->employee->department->department ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $label = str_replace('_', ' ', $milestone->milestone);
                                    $color = $milestone->milestone === '1_year' ? 'bg-orange-100 text-orange-700' : ($milestone->milestone === '6_months' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700');
                                @endphp
                                <div class="flex flex-col">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wide {{ $color }} w-fit">
                                        {{ $label }}
                                    </span>
                                    <span class="text-xs text-gray-500 mt-1 font-bold">
                                        {{ \Carbon\Carbon::parse($milestone->milestone_date)->format('d M, Y') }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                @if(!$milestone->is_read)
                                <button onclick="markAsRead(this, {{ $milestone->id }})" class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-all" title="Mark as Acknowledged">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </button>
                                @else
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Acknowledged</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <span class="material-symbols-outlined text-4xl mb-2">cake</span>
                                <p class="text-sm">No milestones found yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($milestones->hasPages())
            <div id="pagination-wrapper" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                {{ $milestones->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
async function markAsRead(btn, id) {
    try {
        const response = await fetch(`/hrm/tenure-milestones/${id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (data.success) {
            const row = btn.closest('tr');
            row.classList.remove('bg-primary/5');
            row.classList.add('opacity-75');
            btn.parentElement.innerHTML = '<span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Acknowledged</span>';
        }
    } catch (e) {
        console.error(e);
    }
}
</script>
@endsection
