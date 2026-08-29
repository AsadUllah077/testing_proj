@extends('layouts.auth')

@section('title', 'Edit Leave Type')

@section('content')
    <div class="mt-4">
         <div class="p-3 mb-2">
            <div class="flex items-center space-x-3 flex-wrap space-y-3 md:space-y-0">
                @foreach (config('modules.leave-management') as $module)
                    <x-sub_module_tab url="{{ route($module['route']) }}" name="{{ $module['name'] }}"
                        active="{{ Request::routeIs($module['is_active']) }}" />
                @endforeach
            </div>
        </div>
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">Edit Leave Type</h2>
                            <a href="{{ route('hrm.leave-types.index') }}"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <form class="space-y-8" method="post"
                            action_url="{{ route('hrm.leave-types.update', $leaveType->id) }}"
                            back_url="{{ route('hrm.leave-types.index') }}" id="formSubmit">
                            @csrf
                            @method('PUT')

                            <!-- Basic Details -->
                            <div>
                                <div class="grid md:grid-cols-3 gap-6">
                                    {{-- Leave Type Name --}}
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Leave Type Name *</label>
                                        <input type="text" name="name" id="name"
                                            value="{{ old('name', $leaveType->name) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                          focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required>
                                        <div id="name_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    {{-- Days Count --}}
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Entitlement *</label>
                                        <input type="number" name="count" id="count" min="0"
                                            value="{{ old('count', $leaveType->count) }}"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                          focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required>
                                        <div id="count_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>

                                    {{-- Duration --}}
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Duration *</label>
                                        <select name="per" id="per"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                           focus:border-[#0078C8] focus:ring focus:ring-[#0078C8] focus:outline-none"
                                            required>
                                            <option value="">Select Duration</option>
                                            <option value="per_week"
                                                {{ old('per', $leaveType->per) === 'per_week' ? 'selected' : '' }}>
                                                Per Week
                                            </option>
                                            <option value="per_month"
                                                {{ old('per', $leaveType->per) === 'per_month' ? 'selected' : '' }}>
                                                Per Month
                                            </option>
                                            <option value="per_year"
                                                {{ old('per', $leaveType->per) === 'per_year' ? 'selected' : '' }}>
                                                Per Year
                                            </option>
                                        </select>
                                        <div id="per_error" class="text-red-500 text-xs mt-1"></div>
                                    </div>
                                </div>
                            </div>


                            <!-- Buttons -->
                            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                <a href="{{ route('hrm.leave-types.index') }}"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </a>
                                <button type="submit" id="submitBtn"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    UPDATE
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"
        class="fixed top-4 right-4 z-[99999] hidden min-w-[260px] max-w-[360px] bg-white border border-gray-200 shadow-lg rounded-lg p-3">
        <div id="toast-content" class="text-sm text-gray-800"></div>
    </div>
@endsection

@push('scripts')
@endpush
