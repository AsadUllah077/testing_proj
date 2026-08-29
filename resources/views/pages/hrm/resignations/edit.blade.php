@extends('layouts.auth')

@section('title', 'Edit Resignation')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Edit Resignation</h2>
                        <p class="text-gray-600 text-sm mt-1">Update resignation record details</p>
                    </div>
                    <div>
                        <a href="{{ route('hrm.resignations.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <form id="formSubmit" method="post" action_url="{{ route('hrm.resignations.update', $resignation->id) }}" back_url="{{ route('hrm.resignations.index') }}"
                    class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee *</label>
                            <select name="employee_id" required
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                                <option value="">Select Employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ $resignation->employee_id == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} - {{ $employee->employee_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resignation Date *</label>
                            <input type="date" name="resign_date" value="{{ $resignation->resign_date ? $resignation->resign_date->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Working Day *</label>
                            <input type="date" name="last_working_day" value="{{ $resignation->last_working_day ? $resignation->last_working_day->format('Y-m-d') : '' }}"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input type="text" name="title" value="{{ $resignation->title }}" placeholder="e.g. Resignation Letter Submission, Voluntary Separation"
                            class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="5" placeholder="Include comments, reason for leaving, or handover notes..."
                            class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">{{ $resignation->description }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('hrm.resignations.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">Cancel</a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select Employee",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
