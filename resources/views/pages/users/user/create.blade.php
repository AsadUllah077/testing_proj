@extends('layouts.auth')

@section('title', 'Create User')

@section('content')
    <div class="mt-4">
        <div class="">
            <div class="mx-auto px-3">
                <div class="overflow-hidden">

                    <!-- Header -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6 mb-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold text-black">User Information</h2>
                            <a href="javascript:void(0)" onclick="window.history.back(); return false;"
                                class="px-4 py-2 border bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                Back
                            </a>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mx-auto bg-white border border-gray-200 rounded-md shadow-sm p-6">
                        <form class="space-y-8" method="post" action_url="{{ route('users.user.store') }}" id="formSubmit"
                            back_url="{{ route('users.user.index') }}">
                            @csrf

                            <!-- ================= BASIC DETAILS ================= -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Basic
                                    Details</h3>
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">First Name</label>
                                        <input type="text" name="first_name"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-[#0078C8]" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Last Name</label>
                                        <input type="text" name="last_name"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                                        <input type="email" name="email"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Phone Number</label>
                                        <input type="text" name="phone_number"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Select Companies</label>
                                        <select name="companies[]" multiple="multiple"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                            @foreach (companies() as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <input type="hidden" name="company_id" id="primary_company_id">

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Profile Picture</label>
                                        <input type="file" name="profile_pic"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Designation</label>
                                        <input type="text" name="designation"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Select Role</label>
                                        <select name="role_id"
                                            class="select2 w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- ================= LOGIN & SECURITY ================= -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2 border-[#0078C8]">Login &
                                    Security</h3>
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Username</label>
                                        <input type="text" name="username"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>

                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Password</label>
                                        <input type="password" name="password"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-[#0078C8] focus:ring focus:ring-blue-100" />
                                    </div>
                                    <!-- Status Toggle -->
                                    <div class="flex flex-col">
                                        <label class="block text-sm text-gray-600 mb-2">Status</label>

                                        <div class="flex items-center space-x-3">
                                            <!-- Hidden input value (optional if you handle in backend with has('status')) -->
                                            <input type="hidden" name="status" value="0" id="statusHidden">

                                            <label class="inline-flex items-center cursor-pointer">
                                                <!-- The actual checkbox peer (keeps name on checkbox if you prefer backend's has() logic) -->
                                                <input type="checkbox" name="status" value="1" class="sr-only peer" />

                                                <!-- Track -->
                                                <div
                                                    class="w-11 h-6 rounded-full bg-gray-300 peer-checked:bg-[#0078C8] relative transition-colors">
                                                    <!-- Knob -->
                                                    <div
                                                        class="absolute left-1 right-1 top-1 w-4 h-4 rounded-full bg-white transition-transform transform translate-x-0 peer-checked:translate-x-5">
                                                    </div>
                                                </div>

                                                <!-- Label text -->
                                                <span id="toggleStatus"
                                                    class="ml-2 text-sm font-medium text-gray-700 peer-checked:text-[#0078C8]">Disabled</span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                                <button type="button"
                                    class="px-4 py-2 border border-[#0078C8] text-[#0078C8] text-sm font-medium rounded-md hover:bg-[#e6f4ff]">
                                    CANCEL
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-[#0078C8] text-white text-sm font-medium rounded-md hover:bg-[#006bb0]">
                                    ADD NEW
                                </button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('input[type="checkbox"][name="status"]').forEach(cb => {
            cb.addEventListener('change', e => {
                const trackKnob = e.target.closest('label').querySelector('div > div');
                if (e.target.checked) {
                    document.getElementById('toggleStatus').innerText = "Enabled"
                    trackKnob.style.transform = 'translateX(20px)';
                } else {
                    document.getElementById('toggleStatus').innerText = "Disabled"
                    trackKnob.style.transform = 'translateX(0)';
                }
            });
        });

        $(document).ready(function() {
            $('select[name="companies[]"]').on('change', function() {
                const selected = $(this).val();
                if (selected && selected.length > 0) {
                    $('#primary_company_id').val(selected[0]);
                } else {
                    $('#primary_company_id').val('');
                }
            });
        });
    </script>
@endpush
