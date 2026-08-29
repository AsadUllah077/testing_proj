@extends('layouts.auth')

@section('title', 'Log Complaint')

@section('content')
    <div class="mt-4">
        <div class="mx-auto px-3 max-w-4xl">
            <div class="overflow-hidden">
                <div class="p-5 bg-white rounded-lg border border-black/10 md:flex block justify-between items-center mb-4">
                    <div>
                        <h2 class="text-stone-950 text-xl font-normal">Log Complaint</h2>
                        <p class="text-gray-600 text-sm mt-1">Record a new employee grievance or complaint</p>
                    </div>
                    <div>
                        <a href="{{ route('hrm.complaints.index') }}"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 text-xs font-normal">Back</a>
                    </div>
                </div>

                <form id="formSubmit" method="post" action_url="{{ route('hrm.complaints.store') }}" back_url="{{ route('hrm.complaints.index') }}" enctype="multipart/form-data"
                    class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Harassment Grievance, Unprofessional Behavior"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Incident/Filing *</label>
                            <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Employee *</label>
                            <select name="from_employee_id" required
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                                <option value="">Select Complainant</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('from_employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} - {{ $employee->employee_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Against Employee *</label>
                            <select name="against_employee_id" required
                                class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none select2">
                                <option value="">Select Accused</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('against_employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} - {{ $employee->employee_id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload File (Optional)</label>
                        <input type="file" name="file"
                            class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">
                        <p class="mt-1 text-xs text-gray-500">Allowed formats: pdf, doc, docx, png, jpg, jpeg (Max 10MB)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description / Detailed Grievance</label>
                        <textarea name="description" rows="5" placeholder="Detail the complaint incident, dates, witnesses..."
                            class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-[#0078C8] focus:outline-none">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('hrm.complaints.index') }}"
                            class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all duration-200 font-medium text-sm">Cancel</a>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#0078C8] border-2 border-[#0078C8] text-white rounded-lg hover:bg-[#006bb0] transition-all duration-200 font-medium text-sm">Log Complaint</button>
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
