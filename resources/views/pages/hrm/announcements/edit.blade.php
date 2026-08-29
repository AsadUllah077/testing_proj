@extends('layouts.auth')

@section('title', 'Edit Announcement')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Edit Announcement</h2>
                        <p class="text-gray-600 text-sm mt-1">{{ $announcement->title }}</p>
                    </div>
                    <div>
                        <a href="{{ route('hrm.announcements.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <form action="{{ route('hrm.announcements.update', $announcement->id) }}" method="post"
                    class="bg-white rounded-lg border border-gray-200 p-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" value="{{ old('title', $announcement->title) }}"
                                class="w-full px-3 py-2 border rounded text-sm" required>
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input type="text" name="category" value="{{ old('category', $announcement->category) }}"
                                class="w-full px-3 py-2 border rounded text-sm">
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority *</label>
                            <select name="priority" class="select2 w-full px-3 py-2 border rounded text-sm" required>
                                @php $p = old('priority', $announcement->priority); @endphp
                                <option value="low" {{ $p === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $p === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $p === 'high' ? 'selected' : '' }}>High</option>
                            </select>
                            @error('priority')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                            <textarea name="content" rows="6" class="w-full px-3 py-2 border rounded text-sm" required>{{ old('content', $announcement->content) }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- ===== SEND TO SECTION ===== --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Send Notification To (Email &
                                FCM)</label>
                            @php
                                $currentType = old('send_to_type', $announcement->send_to_type);
                            @endphp
                            <div class="flex items-center gap-6 mb-4">
                                {{-- Radio: Department --}}
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="send_to_type" value="department" id="send_to_department"
                                        class="accent-[#0078C8] w-4 h-4"
                                        {{ $currentType === 'department' ? 'checked' : '' }}
                                        onchange="toggleSendToPanel(this.value)">
                                    <span class="text-sm text-gray-700 font-medium">By Department</span>
                                </label>
                                {{-- Radio: Reporting To --}}
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="send_to_type" value="reporting_to" id="send_to_reporting"
                                        class="accent-[#0078C8] w-4 h-4"
                                        {{ $currentType === 'reporting_to' ? 'checked' : '' }}
                                        onchange="toggleSendToPanel(this.value)">
                                    <span class="text-sm text-gray-700 font-medium">By Reporting To (Manager)</span>
                                </label>
                                {{-- Radio: None (no notification) --}}
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="send_to_type" value="" id="send_to_none"
                                        class="accent-[#0078C8] w-4 h-4" {{ !$currentType ? 'checked' : '' }}
                                        onchange="toggleSendToPanel('')">
                                    <span class="text-sm text-gray-700">No Notification</span>
                                </label>
                            </div>

                            {{-- Department picker --}}
                            <div id="panel_department" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Department *</label>
                                <select name="send_to_department_id[]" id="send_to_department_id"
                                    class="select2 w-full px-3 py-2 border rounded text-sm" multiple>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ in_array($dept->id, (array) old('send_to_department_id', $announcement->send_to_department_id)) ? 'selected' : '' }}>
                                            {{ $dept->department }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('send_to_department_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">All active employees in this department will receive
                                    email & FCM notifications.</p>
                            </div>

                            {{-- Reporting-To employee picker --}}
                            <div id="panel_reporting" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Select Manager (Reporting To)
                                    *</label>
                                <select name="send_to_employee_id[]" id="send_to_employee_id"
                                    class="select2 w-full px-3 py-2 border rounded text-sm" multiple>
                                    @foreach ($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ in_array($emp->id, (array) old('send_to_employee_id', $announcement->send_to_employee_id)) ? 'selected' : '' }}>
                                            {{ $emp->full_name }} @if ($emp->designation)
                                                ({{ $emp->designation }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('send_to_employee_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">All active employees who have this person set as their
                                    "Reporting To" will receive notifications.</p>
                            </div>
                        </div>
                        {{-- ===== END SEND TO SECTION ===== --}}
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('hrm.announcements.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">Cancel</a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleSendToPanel(type) {
            document.getElementById('panel_department').classList.add('hidden');
            document.getElementById('panel_reporting').classList.add('hidden');

            if (type === 'department') {
                document.getElementById('panel_department').classList.remove('hidden');
            } else if (type === 'reporting_to') {
                document.getElementById('panel_reporting').classList.remove('hidden');
            }
        }

        // On page load, restore panel based on the saved/old value
        document.addEventListener('DOMContentLoaded', function() {
            const checked = document.querySelector('input[name="send_to_type"]:checked');
            if (checked) toggleSendToPanel(checked.value);

            // Initialize Select2 for multiple selects
            $('.select2').select2({
                placeholder: "-- Select Options --",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
