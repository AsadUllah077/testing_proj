@extends('layouts.auth')
@section('title', 'New Team')

@section('content')
    <x-acccount.loader />
    <div class="mt-4 mx-auto px-3 max-w-12xl">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('project.teams.index') }}" class="hover:text-[#0078C8]">Teams</a>
            <span>/</span>
            <span class="text-gray-800">New Team</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">New Team</h2>
            </div>

            <form id="formSubmit" method="post"
                  action_url="{{ route('project.teams.store') }}"
                  back_url="{{ route('project.teams.index') }}"
                  class="p-6 space-y-6">
                @csrf

                <!-- Section: Team Details -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        Team Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Team Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Enter team name"
                                class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Assign to Project</label>
                            <select name="project_id" class="select2 w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                                <option value="">— No Project —</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section: Members -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-[#0078C8]">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        Assign Employees
                    </h3>
                    @if($employees->isEmpty())
                        <p class="text-sm text-gray-400">No employees found.</p>
                    @else
                        <div class="mb-3">
                            <input type="text" id="memberSearch" placeholder="Search employees..."
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#0078C8] focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="border border-gray-200 rounded-lg overflow-y-auto" style="max-height: 280px;">
                            @foreach($employees as $employee)
                                @php
                                    $initials = collect(explode(' ', $employee->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
                                    $colors = ['bg-blue-500','bg-green-500','bg-purple-500','bg-orange-500','bg-pink-500','bg-teal-500'];
                                    $color = $colors[$loop->index % count($colors)];
                                @endphp
                                <label class="member-row flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors">
                                    <input type="checkbox" name="members[]" value="{{ $employee->id }}"
                                        class="rounded border-gray-300 text-[#0078C8] focus:ring-[#0078C8]"
                                        {{ in_array($employee->id, old('members', [])) ? 'checked' : '' }}>
                                    <div class="w-7 h-7 rounded-full {{ $color }} text-white text-xs font-semibold flex items-center justify-center flex-shrink-0">
                                        {{ $initials }}
                                    </div>
                                    <span class="text-sm text-gray-800 member-name">{{ $employee->full_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Select one or more employees to add to this team.</p>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <a href="{{ route('project.teams.index') }}"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn"
                        class="px-5 py-2.5 bg-[#0078C8] text-white text-sm rounded-lg hover:bg-[#006bb0] transition-colors text-center">
                        Save Team
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const memberSearch = document.getElementById('memberSearch');
    memberSearch?.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.member-row').forEach(row => {
            const name = row.querySelector('.member-name')?.textContent.toLowerCase() ?? '';
            row.style.display = name.includes(q) ? '' : 'none';
        });
    });
</script>
@endpush
